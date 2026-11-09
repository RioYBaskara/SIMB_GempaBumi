<?php
/**
 * Konfigurasi koneksi database & pengaturan umum SIMB
 */
date_default_timezone_set('Asia/Jakarta');

$host = "localhost";
$user = "root";
$pass = "";
$db   = "db_simb";

// Kunci rahasia untuk memicu sinkronisasi lewat URL (cron eksternal).
// GANTI dengan string acak milikmu sendiri.
define('SINKRON_KEY', 'ganti-dengan-kunci-rahasia-kamu');

// Agar error mysqli menjadi exception (lebih mudah ditangani)
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $koneksi = mysqli_connect($host, $user, $pass, $db);
    mysqli_set_charset($koneksi, 'utf8mb4');
    $koneksi->query("SET time_zone = '+07:00'");   // samakan dengan WIB
} catch (mysqli_sql_exception $e) {
    error_log('Koneksi DB gagal: ' . $e->getMessage());
    http_response_code(500);
    if (defined('API_MODE')) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'pesan' => 'Koneksi database gagal.']);
    } else {
        echo 'Koneksi database gagal. Periksa config/koneksi.php';
    }
    exit;
}
