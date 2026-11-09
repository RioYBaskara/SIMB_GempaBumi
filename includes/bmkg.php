<?php
/**
 * Integrasi API Data Gempa Bumi Terbuka BMKG
 * Sumber: https://data.bmkg.go.id/gempabumi
 *
 * Alur:
 *   bmkg_sinkron_semua()  ->  ambil JSON  ->  normalisasi  ->  simpan ke MySQL
 *   (dengan throttle supaya server BMKG tidak dipanggil berlebihan)
 */

const BMKG_BASE           = 'https://data.bmkg.go.id/DataMKG/TEWS/';
const BMKG_SHAKEMAP_BASE  = 'https://static.bmkg.go.id/';

// interval = jeda minimum (detik) antar pemanggilan sukses
const BMKG_ENDPOINT = [
    'autogempa'      => ['file' => 'autogempa.json',      'jenis' => 'terbaru',    'interval' => 60],
    'gempaterkini'   => ['file' => 'gempaterkini.json',   'jenis' => 'terkini',    'interval' => 300],
    'gempadirasakan' => ['file' => 'gempadirasakan.json', 'jenis' => 'dirasakan',  'interval' => 120],
];

const BMKG_JEDA_SETELAH_GAGAL = 30; // detik

/* ------------------------------------------------------------------
 * 1. Ambil JSON dari BMKG
 * ------------------------------------------------------------------ */
function bmkg_ambil_json(string $file): array
{
    $ch = curl_init(BMKG_BASE . $file);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_USERAGENT      => 'SIMB-GempaBumi/1.0 (proyek edukasi kebencanaan)',
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $body = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    curl_close($ch);

    if ($body === false) {
        throw new RuntimeException("Gagal terhubung ke BMKG: $err");
    }
    if ($code !== 200) {
        throw new RuntimeException("BMKG membalas HTTP $code");
    }

    $json = json_decode($body, true);
    if (!is_array($json) || !isset($json['Infogempa']['gempa'])) {
        throw new RuntimeException('Format JSON BMKG tidak dikenali');
    }

    $g = $json['Infogempa']['gempa'];
    // autogempa -> 1 objek, lainnya -> array of objek. Seragamkan jadi array.
    return isset($g['DateTime']) ? [$g] : $g;
}

/* ------------------------------------------------------------------
 * 2. Normalisasi satu record BMKG -> format tabel `gempa`
 * ------------------------------------------------------------------ */
function bmkg_normalisasi(array $g): ?array
{
    if (empty($g['DateTime']) || empty($g['Coordinates']) || !isset($g['Magnitude'])) {
        return null;
    }

    try {
        // DateTime dari BMKG dalam UTC (ada offset) -> ubah ke WIB
        $dt = new DateTime($g['DateTime']);
        $dt->setTimezone(new DateTimeZone('Asia/Jakarta'));
    } catch (Exception $e) {
        return null;
    }

    $koor = explode(',', $g['Coordinates']);     // "lintang,bujur" (negatif = LS / BB)
    if (count($koor) !== 2) {
        return null;
    }

    $vacuum = fn($v) => ($v === null || trim((string)$v) === '') ? null : trim((string)$v);

    return [
        'kode_unik' => sha1($g['DateTime'] . '|' . $g['Coordinates']),
        'waktu'     => $dt->format('Y-m-d H:i:s'),
        'lintang'   => (float) $koor[0],
        'bujur'     => (float) $koor[1],
        'magnitudo' => (float) $g['Magnitude'],
        'kedalaman' => (int) preg_replace('/[^0-9]/', '', $g['Kedalaman'] ?? '0'),
        'wilayah'   => trim($g['Wilayah'] ?? '-'),
        'potensi'   => $vacuum($g['Potensi'] ?? null),
        'dirasakan' => $vacuum($g['Dirasakan'] ?? null),
        'shakemap'  => $vacuum($g['Shakemap'] ?? null),
    ];
}

/* ------------------------------------------------------------------
 * 3. Simpan ke MySQL (anti-duplikat via kode_unik)
 *    Jika data sudah ada, lengkapi kolom yang tadinya kosong
 *    (mis. gempa yang awalnya dari "terkini", lalu muncul di "dirasakan").
 *    Return: jumlah data BARU yang masuk.
 * ------------------------------------------------------------------ */
function bmkg_simpan(mysqli $db, array $records, string $jenis): int
{
    $sql = "INSERT INTO gempa
              (kode_unik, waktu, lintang, bujur, magnitudo, kedalaman, wilayah, potensi, dirasakan, jenis, shakemap)
            VALUES (?,?,?,?,?,?,?,?,?,?,?)
            ON DUPLICATE KEY UPDATE
              potensi   = COALESCE(NULLIF(VALUES(potensi),''),   potensi),
              dirasakan = COALESCE(NULLIF(VALUES(dirasakan),''), dirasakan),
              shakemap  = COALESCE(NULLIF(VALUES(shakemap),''),  shakemap)";
    $stmt = $db->prepare($sql);
    $baru = 0;

    foreach ($records as $raw) {
        $r = bmkg_normalisasi($raw);
        if ($r === null) {
            continue;
        }
        $stmt->bind_param(
            'ssdddisssss',
            $r['kode_unik'], $r['waktu'], $r['lintang'], $r['bujur'],
            $r['magnitudo'], $r['kedalaman'], $r['wilayah'],
            $r['potensi'], $r['dirasakan'], $jenis, $r['shakemap']
        );
        $stmt->execute();
        if ($stmt->affected_rows === 1) {   // 1 = insert baru, 2 = update, 0 = tidak berubah
            $baru++;
        }
    }
    $stmt->close();
    return $baru;
}

/* ------------------------------------------------------------------
 * 4. Throttle & log
 * ------------------------------------------------------------------ */
function bmkg_perlu_sinkron(mysqli $db, string $endpoint, int $interval): bool
{
    $stmt = $db->prepare(
        "SELECT status, TIMESTAMPDIFF(SECOND, created_at, NOW()) AS selisih
           FROM log_sinkron_api WHERE endpoint = ? ORDER BY id DESC LIMIT 1"
    );
    $stmt->bind_param('s', $endpoint);
    $stmt->execute();
    $last = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$last) {
        return true;                                   // belum pernah sinkron
    }
    $batas = ($last['status'] === 'sukses') ? $interval : BMKG_JEDA_SETELAH_GAGAL;
    return (int) $last['selisih'] >= $batas;
}

function bmkg_catat_log(mysqli $db, string $endpoint, string $status, int $baru, ?string $pesan): void
{
    $stmt = $db->prepare(
        "INSERT INTO log_sinkron_api (endpoint, status, jumlah_baru, pesan) VALUES (?,?,?,?)"
    );
    $stmt->bind_param('ssis', $endpoint, $status, $baru, $pesan);
    $stmt->execute();
    $stmt->close();
}

/* ------------------------------------------------------------------
 * 5. Sinkron satu endpoint / semua endpoint
 * ------------------------------------------------------------------ */
function bmkg_sinkron(mysqli $db, string $endpoint, bool $paksa = false): array
{
    $cfg = BMKG_ENDPOINT[$endpoint] ?? null;
    if (!$cfg) {
        return ['endpoint' => $endpoint, 'status' => 'tidak_dikenal', 'baru' => 0];
    }

    // Kunci agar tidak ada 2 proses sinkron endpoint yang sama bersamaan
    $lock = 'simb_' . $endpoint;
    $got  = (int) $db->query("SELECT GET_LOCK('$lock', 0) AS l")->fetch_assoc()['l'];
    if (!$got) {
        return ['endpoint' => $endpoint, 'status' => 'sedang_berjalan', 'baru' => 0];
    }

    try {
        if (!$paksa && !bmkg_perlu_sinkron($db, $endpoint, $cfg['interval'])) {
            return ['endpoint' => $endpoint, 'status' => 'dilewati', 'baru' => 0];
        }

        $records = bmkg_ambil_json($cfg['file']);
        $baru    = bmkg_simpan($db, $records, $cfg['jenis']);
        bmkg_catat_log($db, $endpoint, 'sukses', $baru, null);
        return ['endpoint' => $endpoint, 'status' => 'sukses', 'baru' => $baru];

    } catch (Throwable $e) {
        bmkg_catat_log($db, $endpoint, 'gagal', 0, mb_substr($e->getMessage(), 0, 250));
        return ['endpoint' => $endpoint, 'status' => 'gagal', 'baru' => 0, 'pesan' => $e->getMessage()];

    } finally {
        $db->query("SELECT RELEASE_LOCK('$lock')");
    }
}

function bmkg_sinkron_semua(mysqli $db, bool $paksa = false): array
{
    $hasil = [];
    foreach (array_keys(BMKG_ENDPOINT) as $ep) {
        $hasil[] = bmkg_sinkron($db, $ep, $paksa);
    }
    // bersihkan log lama (>7 hari) supaya tabel tidak membengkak
    $db->query("DELETE FROM log_sinkron_api WHERE created_at < NOW() - INTERVAL 7 DAY");
    return $hasil;
}

/* ------------------------------------------------------------------
 * 6. Helper tampilan
 * ------------------------------------------------------------------ */

/** Tingkat peringatan untuk warna alert bar: bahaya | waspada | info */
function bmkg_level_peringatan(float $mag, ?string $potensi): string
{
    $tsunami = $potensi !== null
        && stripos($potensi, 'tsunami') !== false
        && stripos($potensi, 'tidak') === false;

    if ($tsunami || $mag >= 6.0) return 'bahaya';
    if ($mag >= 5.0)             return 'waspada';
    return 'info';
}

/** Ubah baris DB -> array siap dikirim sebagai JSON */
function bmkg_format_baris(array $r): array
{
    $mag = (float) $r['magnitudo'];
    return [
        'id'          => (int) $r['id'],
        'waktu'       => $r['waktu'],                                   // WIB
        'waktu_iso'   => (new DateTime($r['waktu']))->format('c'),      // untuk JS
        'lintang'     => (float) $r['lintang'],
        'bujur'       => (float) $r['bujur'],
        'magnitudo'   => $mag,
        'kedalaman'   => (int) $r['kedalaman'],
        'wilayah'     => $r['wilayah'],
        'potensi'     => $r['potensi'],
        'dirasakan'   => $r['dirasakan'],
        'level'       => bmkg_level_peringatan($mag, $r['potensi']),
        'shakemap_url'=> $r['shakemap'] ? BMKG_SHAKEMAP_BASE . $r['shakemap'] : null,
    ];
}
