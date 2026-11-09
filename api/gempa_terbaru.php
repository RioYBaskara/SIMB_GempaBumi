<?php
/**
 * api/gempa_terbaru.php
 * Endpoint JSON: ambil data 1 gempa terbaru dari BMKG
 * Cache minimal 60 detik untuk menghindari membebani API BMKG
 *
 * Respons: { "gempa": {...}, "sumber": "bmkg"|"database", "pesan": "..." }
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../includes/fungsi.php';

$url_bmkg = 'https://data.bmkg.go.id/DataMKG/TEWS/autogempa.json';
$cache_key = 'autogempa';

// --- Coba ambil dari cache (valid 60 detik) ---
$cache = ambilCache($cache_key, 60);
if ($cache) {
    $data = json_decode($cache, true);
    if (isset($data['Infogempa']['gempa'])) {
        echo json_encode([
            'gempa'  => $data['Infogempa']['gempa'],
            'sumber' => 'cache',
            'pesan'  => ''
        ]);
        exit;
    }
}

// --- Ambil langsung dari BMKG ---
$raw = ambilDataCurl($url_bmkg);

if ($raw !== false) {
    $data = json_decode($raw, true);
    if (isset($data['Infogempa']['gempa'])) {
        // Simpan ke cache
        simpanCache($cache_key, $raw);

        // Simpan juga ke database
        simpanGempa($data['Infogempa']['gempa'], 'autogempa');

        echo json_encode([
            'gempa'  => $data['Infogempa']['gempa'],
            'sumber' => 'bmkg',
            'pesan'  => ''
        ]);
        exit;
    }
}

// --- Fallback: ambil dari database ---
$stmt = $koneksi->prepare(
    "SELECT * FROM gempa ORDER BY datetime DESC LIMIT 1"
);
$stmt->execute();
$baris = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($baris) {
    // Ubah format DB ke format mirip BMKG agar JS bisa pakai field yang sama
    $gempa_db = [
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
    echo json_encode([
        'gempa'  => $gempa_db,
        'sumber' => 'database',
        'pesan'  => 'Data BMKG sedang tidak dapat diakses, menampilkan data tersimpan.'
    ]);
} else {
    http_response_code(503);
    echo json_encode([
        'gempa'  => null,
        'sumber' => 'none',
        'pesan'  => 'Data tidak tersedia saat ini.'
    ]);
}
