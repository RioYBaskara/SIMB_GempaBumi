<?php
/**
 * config/koneksi.php
 * Konfigurasi koneksi database MySQL
 */

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'db_simb');

// Buat koneksi dengan mysqli
$koneksi = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Cek jika koneksi gagal
if ($koneksi->connect_error) {
    // Jangan tampilkan error mentah ke user
    error_log('Koneksi DB gagal: ' . $koneksi->connect_error);
    die('<div class="alert alert-danger m-3">Tidak dapat terhubung ke database. Silakan coba lagi nanti.</div>');
}

// Set charset ke utf8mb4 agar mendukung karakter Unicode penuh
$koneksi->set_charset('utf8mb4');

// Set timezone PHP ke WIB
date_default_timezone_set('Asia/Jakarta');
