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
  <link href="<?= $base ?>assets/css/redesign.css" rel="stylesheet">
  <link href="<?= $base ?>assets/css/redesign-v3.css" rel="stylesheet">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top simb-navbar">
  <div class="container-xl">

    <!-- ── Brand ── -->
    <a class="navbar-brand fw-bold simb-brand" href="<?= $base ?>index.php">
      <span class="simb-brand__wordmark">
        <span class="simb-brand__top">
          <span class="simb-brand__name">SIMB</span><span class="simb-brand__dot"></span>
        </span>
        <span class="simb-brand__sub">Sistem Informasi Manajemen Bencana</span>
      </span>
    </a>

    <!-- ── Mobile: emergency + hamburger ── -->
    <a class="btn btn-danger btn-sm fw-bold ms-auto me-2 d-lg-none simb-btn-emergency" href="<?= $base ?>index.php#kontak">
      <i class="bi bi-telephone-fill"></i> DARURAT
    </a>
    <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#menuUtama"
            aria-controls="menuUtama" aria-expanded="false" aria-label="Buka menu">
      <span class="navbar-toggler-icon"></span>
    </button>

    <!-- ── Collapsible menu ── -->
    <div class="collapse navbar-collapse" id="menuUtama">
      <ul class="navbar-nav me-auto" id="simbNavList">
        <li class="nav-item"><a class="nav-link simb-nav-link <?= $aktif === 'beranda'      ? 'active' : '' ?>" href="<?= $base ?>index.php" data-simb-page="beranda">Beranda</a></li>
        <li class="nav-item"><a class="nav-link simb-nav-link" href="<?= $base ?>index.php#peta" data-simb-hash="peta">Peta Gempa</a></li>
        <li class="nav-item"><a class="nav-link simb-nav-link" href="<?= $base ?>index.php#dirasakan" data-simb-hash="dirasakan">Gempa Dirasakan</a></li>
        <li class="nav-item"><a class="nav-link simb-nav-link" href="<?= $base ?>index.php#statistik" data-simb-hash="statistik">Statistik</a></li>
        <li class="nav-item"><a class="nav-link simb-nav-link <?= $aktif === 'orang-hilang' ? 'active' : '' ?>" href="<?= $base ?>orang-hilang/cari.php" data-simb-page="orang-hilang">Orang Hilang</a></li>
        <li class="nav-item"><a class="nav-link simb-nav-link <?= $aktif === 'edukasi'      ? 'active' : '' ?>" href="<?= $base ?>edukasi.php" data-simb-page="edukasi">Edukasi</a></li>
      </ul>

      <!-- ── Right cluster ── -->
      <div class="d-none d-lg-flex align-items-center gap-3">
        <!-- Live status badge -->
        <span class="simb-live-badge" aria-label="Sistem aktif real-time">
          <span class="simb-live-dot" aria-hidden="true"></span>
          <span class="simb-live-text">LIVE&thinsp;•&thinsp;<span id="simbNavClock">Sistem Aktif</span></span>
        </span>
        <!-- Emergency button -->
        <a class="btn btn-danger fw-bold simb-btn-emergency" href="<?= $base ?>index.php#kontak">
          <i class="bi bi-telephone-fill"></i> KONTAK DARURAT
        </a>
      </div>

      <!-- Mobile live badge (inside drawer) -->
      <div class="d-lg-none simb-live-badge simb-live-badge--mobile mt-2 mb-1">
        <span class="simb-live-dot" aria-hidden="true"></span>
        <span class="simb-live-text">LIVE&thinsp;•&thinsp;<span id="simbNavClockMobile">Sistem Aktif</span></span>
      </div>
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
