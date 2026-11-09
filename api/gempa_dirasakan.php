<?php
/**
 * api/gempa_dirasakan.php
 * Endpoint JSON: 15 gempa yang dirasakan terkini dari BMKG
 * Cache 5 menit (300 detik)
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../includes/fungsi.php';

$url_bmkg  = 'https://data.bmkg.go.id/DataMKG/TEWS/gempadirasakan.json';
$cache_key = 'gempa_dirasakan';

// --- Coba cache ---
$cache = ambilCache($cache_key, 300);
if ($cache) {
    $data = json_decode($cache, true);
    if (isset($data['Infogempa']['gempa'])) {
        $list = $data['Infogempa']['gempa'];
        if (isset($list['Tanggal'])) $list = [$list];
        echo json_encode(['gempa' => $list, 'sumber' => 'cache', 'total' => count($list), 'pesan' => '']);
        exit;
    }
}

// --- Ambil dari BMKG ---
$raw = ambilDataCurl($url_bmkg);

if ($raw !== false) {
    $data = json_decode($raw, true);
    if (isset($data['Infogempa']['gempa'])) {
        simpanCache($cache_key, $raw);

        $list = $data['Infogempa']['gempa'];
        if (isset($list['Tanggal'])) $list = [$list];

        foreach ($list as $g) {
            simpanGempa($g, 'dirasakan');
        }

        echo json_encode(['gempa' => $list, 'sumber' => 'bmkg', 'total' => count($list), 'pesan' => '']);
        exit;
    }
}

// --- Fallback dari database ---
$stmt = $koneksi->prepare(
    "SELECT * FROM gempa WHERE dirasakan IS NOT NULL AND dirasakan != '' ORDER BY datetime DESC LIMIT 15"
);
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$list = [];
foreach ($rows as $baris) {
    $list[] = [
        'Tanggal'     => $baris['tanggal'],
        'Jam'         => $baris['jam'],
        'DateTime'    => $baris['datetime'],
        'Coordinates' => $baris['lintang'] . ',' . $baris['bujur'],
        'Lintang'     => (string)$baris['lintang'],
        'Bujur'       => (string)$baris['bujur'],
        'Magnitude'   => (string)$baris['magnitudo'],
        'Kedalaman'   => $baris['kedalaman_km'] . ' km',
        'Wilayah'     => $baris['wilayah'],
        'Potensi'     => $baris['potensi'],
        'Dirasakan'   => $baris['dirasakan'],
        'Shakemap'    => $baris['shakemap'],
    ];
}

$pesan = count($list) ? 'Data BMKG sedang tidak dapat diakses, menampilkan data tersimpan.' : 'Data tidak tersedia.';
echo json_encode(['gempa' => $list, 'sumber' => 'database', 'total' => count($list), 'pesan' => $pesan]);
