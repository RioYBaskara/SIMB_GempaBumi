<?php
/**
 * Header bersama. Variabel opsional dari halaman pemanggil:
 *   $judul  = judul halaman
 *   $base   = path relatif ke root project (default '' untuk index.php,
 *             '../' untuk halaman di subfolder)
 *   $aktif  = menu aktif: beranda | orang-hilang | edukasi
 *   $tanpaAlert = true untuk menyembunyikan alert bar
 */
$judul = $judul ?? 'SIMB - Pemantauan & Edukasi Gempa Bumi';
$base  = $base  ?? '';
$aktif = $aktif ?? 'beranda';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($judul) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" rel="stylesheet">
  <link href="<?= $base ?>assets/css/style.css" rel="stylesheet">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top shadow-sm">
  <div class="container-xl">
    <a class="navbar-brand fw-bold" href="<?= $base ?>index.php">
      <i class="bi bi-activity text-danger"></i> SIMB
    </a>

    <!-- Tombol DARURAT selalu terlihat, termasuk di HP -->
    <a class="btn btn-danger btn-sm fw-bold ms-auto me-2 d-lg-none" href="<?= $base ?>index.php#kontak">
      <i class="bi bi-telephone-fill"></i> DARURAT
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menuUtama"
            aria-controls="menuUtama" aria-expanded="false" aria-label="Buka menu">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="menuUtama">
      <ul class="navbar-nav me-auto">
        <li class="nav-item"><a class="nav-link <?= $aktif === 'beranda' ? 'active' : '' ?>" href="<?= $base ?>index.php">Beranda</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= $base ?>index.php#peta">Peta Gempa</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= $base ?>index.php#dirasakan">Gempa Dirasakan</a></li>
        <li class="nav-item"><a class="nav-link <?= $aktif === 'orang-hilang' ? 'active' : '' ?>" href="<?= $base ?>orang-hilang/cari.php">Orang Hilang</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= $base ?>index.php#statistik">Statistik</a></li>
        <li class="nav-item"><a class="nav-link <?= $aktif === 'edukasi' ? 'active' : '' ?>" href="<?= $base ?>edukasi.php">Edukasi</a></li>
      </ul>
      <a class="btn btn-danger fw-bold d-none d-lg-inline-block" href="<?= $base ?>index.php#kontak">
        <i class="bi bi-telephone-fill"></i> KONTAK DARURAT
      </a>
    </div>
  </div>
</nav>

<?php if (empty($tanpaAlert)): ?>
<!-- ALERT BAR: diisi oleh assets/js/app.js -->
<div id="alertBar" class="alert-bar alert-bar-info" role="alert" aria-live="assertive">
  <div class="container-xl py-2">
    <span id="alertTeks"><span class="spinner-border spinner-border-sm me-2"></span>Memuat data gempa terbaru…</span>
  </div>
</div>
<?php endif; ?>
