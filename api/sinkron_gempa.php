<?php
/**
 * Pemicu sinkronisasi data BMKG -> MySQL (untuk cron / Task Scheduler).
 *
 * Cara pakai:
 *   1) Lewat terminal / cron (disarankan, tiap 1-5 menit):
 *        php /path/ke/SIMB_GempaBumi/api/sinkron_gempa.php
 *      Windows (Task Scheduler): jalankan php.exe dengan argumen file ini.
 *
 *   2) Lewat URL (untuk layanan cron online), wajib pakai kunci:
 *        http://domainmu/api/sinkron_gempa.php?key=KUNCI_RAHASIA
 *      Tambahkan &paksa=1 untuk mengabaikan throttle.
 */
define('API_MODE', true);
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../includes/bmkg.php';

$cli = (PHP_SAPI === 'cli');

if (!$cli) {
    header('Content-Type: application/json; charset=utf-8');
    $key = $_GET['key'] ?? '';
    if (!hash_equals(SINKRON_KEY, (string) $key)) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'pesan' => 'Kunci tidak valid']);
        exit;
    }
}

$paksa = $cli ? in_array('--paksa', $argv ?? [], true) : !empty($_GET['paksa']);
$hasil = bmkg_sinkron_semua($koneksi, $paksa);

if ($cli) {
    foreach ($hasil as $h) {
        echo str_pad($h['endpoint'], 16) . ' : ' . $h['status']
           . ' (baru: ' . $h['baru'] . ')'
           . (isset($h['pesan']) ? ' - ' . $h['pesan'] : '') . PHP_EOL;
    }
} else {
    echo json_encode(['ok' => true, 'hasil' => $hasil], JSON_UNESCAPED_UNICODE);
}
