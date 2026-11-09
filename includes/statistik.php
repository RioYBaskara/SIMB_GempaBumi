<?php
/**
 * Perhitungan statistik gempa dari data yang tersimpan di database.
 */

/** Ambil nama lokasi terdekat dari teks wilayah BMKG.
 *  "Pusat gempa berada di laut 33 km Tenggara Pacitan" -> "Pacitan"
 *  "81 km BaratDaya PULAUSIMEULUE-ACEH"                -> "Pulausimeulue"   */
function statistik_lokasi_ringkas(string $wilayah): string
{
    $w = trim($wilayah);
    if (preg_match('/\bkm\s+(?:Utara|Selatan|Timur|Barat|TimurLaut|BaratLaut|Tenggara|BaratDaya)\s+(.+)$/iu', $w, $m)) {
        $w = trim($m[1]);
    }
    // Format huruf kapital "KOTA-PROVINSI": ambil nama kota saja
    if (mb_strtoupper($w) === $w && strpos($w, '-') !== false) {
        $w = explode('-', $w)[0];
    }
    return mb_convert_case(mb_strtolower($w), MB_CASE_TITLE);
}

/**
 * @param string $dari   Y-m-d (WIB)
 * @param string $sampai Y-m-d (WIB)
 */
function statistik_hitung(mysqli $db, string $dari, string $sampai): array
{
    $stmt = $db->prepare(
        "SELECT waktu, magnitudo, kedalaman, wilayah, dirasakan
           FROM gempa
          WHERE waktu >= ? AND waktu < DATE_ADD(?, INTERVAL 1 DAY)
          ORDER BY waktu"
    );
    $stmt->bind_param('ss', $dari, $sampai);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // ---- kerangka periode (hari, atau bulan bila rentang > 92 hari) ----
    $d1 = new DateTime($dari);
    $d2 = new DateTime($sampai);
    $selisih  = $d1->diff($d2)->days + 1;
    $perBulan = $selisih > 92;
    $fmtKunci = $perBulan ? 'Y-m' : 'Y-m-d';

    $jumlah = [];
    $magMaks = [];
    $cur = clone $d1;
    if ($perBulan) {
        $cur->modify('first day of this month');
    }
    while ($cur <= $d2) {
        $k = $cur->format($fmtKunci);
        $jumlah[$k]  = 0;
        $magMaks[$k] = null;
        $cur->modify($perBulan ? '+1 month' : '+1 day');
    }

    // ---- hitung ----
    $total = count($rows);
    $sumMag = 0.0;
    $maks = null;
    $m5 = 0;
    $dirasakan = 0;
    $distMag = [0, 0, 0, 0, 0];     // <3 | 3-3.9 | 4-4.9 | 5-5.9 | >=6
    $distDalam = [0, 0, 0];         // dangkal <60 | menengah 60-300 | dalam >300
    $lokasi = [];

    foreach ($rows as $r) {
        $mag = (float) $r['magnitudo'];
        $dlm = (int) $r['kedalaman'];
        $k   = (new DateTime($r['waktu']))->format($fmtKunci);

        if (isset($jumlah[$k])) {
            $jumlah[$k]++;
            if ($magMaks[$k] === null || $mag > $magMaks[$k]) {
                $magMaks[$k] = $mag;
            }
        }

        $sumMag += $mag;
        if ($maks === null || $mag > $maks['magnitudo']) {
            $maks = ['magnitudo' => $mag, 'wilayah' => $r['wilayah'], 'waktu' => $r['waktu']];
        }
        if ($mag >= 5.0) { $m5++; }
        if (!empty($r['dirasakan'])) { $dirasakan++; }

        $distMag[$mag < 3 ? 0 : ($mag < 4 ? 1 : ($mag < 5 ? 2 : ($mag < 6 ? 3 : 4)))]++;
        $distDalam[$dlm < 60 ? 0 : ($dlm <= 300 ? 1 : 2)]++;

        $lok = statistik_lokasi_ringkas($r['wilayah']);
        $lokasi[$lok] = ($lokasi[$lok] ?? 0) + 1;
    }

    arsort($lokasi);
    $top = [];
    foreach (array_slice($lokasi, 0, 5, true) as $nama => $n) {
        $top[] = ['lokasi' => $nama, 'jumlah' => $n];
    }

    $dataSejak = $db->query("SELECT MIN(waktu) AS t FROM gempa")->fetch_assoc()['t'];

    return [
        'rentang' => ['dari' => $dari, 'sampai' => $sampai, 'per' => $perBulan ? 'bulan' : 'hari'],
        'data_sejak' => $dataSejak,
        'ringkasan' => [
            'total'     => $total,
            'rata_mag'  => $total ? round($sumMag / $total, 2) : null,
            'terkuat'   => $maks,
            'jumlah_m5' => $m5,
            'dirasakan' => $dirasakan,
        ],
        'waktu' => [
            'label'    => array_keys($jumlah),
            'jumlah'   => array_values($jumlah),
            'mag_maks' => array_values($magMaks),
        ],
        'magnitudo' => [
            'label'  => ['< M3', 'M3 - 3,9', 'M4 - 4,9', 'M5 - 5,9', 'M6 ke atas'],
            'jumlah' => $distMag,
        ],
        'kedalaman' => [
            'label'  => ['Dangkal (< 60 km)', 'Menengah (60-300 km)', 'Dalam (> 300 km)'],
            'jumlah' => $distDalam,
        ],
        'top_lokasi' => $top,
    ];
}
