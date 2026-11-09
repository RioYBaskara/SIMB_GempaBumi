<?php
/**
 * includes/header.php
 * Template HTML head — diinclude di awal setiap halaman
 * Variabel yang bisa di-set sebelum include:
 *   $judul_halaman  (default: 'SIMB GempaBumi')
 *   $deskripsi      (meta description)
 */

if (!isset($judul_halaman)) $judul_halaman = 'SIMB GempaBumi';
if (!isset($deskripsi))     $deskripsi = 'Sistem Informasi Manajemen Bencana: Edukasi dan Pemantauan Gempa Bumi Indonesia';

// BASE_URL: sesuaikan jika folder berbeda dari SIMB_GempaBumi
if (!defined('BASE_URL')) {
    define('BASE_URL', '/SIMB_GempaBumi');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= htmlspecialchars($deskripsi) ?>">
    <title><?= htmlspecialchars($judul_halaman) ?> | SIMB GempaBumi</title>

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="<?= BASE_URL ?>/assets/img/logo.svg">

    <!-- Bootstrap 5 CSS -->
    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"
          integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN"
          crossorigin="anonymous">

    <!-- Leaflet CSS untuk peta -->
    <link rel="stylesheet"
          href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
          integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
          crossorigin="">

    <!-- CSS kustom aplikasi -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
<?php require_once __DIR__ . '/navbar.php'; ?>
