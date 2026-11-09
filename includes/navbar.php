<?php
/**
 * includes/navbar.php
 * Navigasi utama yang dipakai di semua halaman
 */

// Tentukan halaman aktif berdasarkan nama file
$halaman_aktif = basename($_SERVER['PHP_SELF']);
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary-dark sticky-top shadow-sm">
    <div class="container">
        <!-- Logo & Nama Aplikasi -->
        <a class="navbar-brand d-flex align-items-center gap-2" href="<?= BASE_URL ?>/">
            <img src="<?= BASE_URL ?>/assets/img/logo.svg" alt="SIMB Logo" width="32" height="32">
            <span class="fw-bold">SIMB <span class="fw-light text-warning">GempaBumi</span></span>
        </a>

        <!-- Tombol hamburger untuk mobile -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMenu">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Menu navigasi -->
        <div class="collapse navbar-collapse" id="navbarMenu">
            <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link <?= in_array($halaman_aktif, ['index.php','']) ? 'active fw-semibold' : '' ?>"
                       href="<?= BASE_URL ?>/">
                        🏠 Beranda
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $halaman_aktif === 'edukasi.php' ? 'active fw-semibold' : '' ?>"
                       href="<?= BASE_URL ?>/edukasi.php">
                        📚 Edukasi
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $halaman_aktif === 'monitoring.php' ? 'active fw-semibold' : '' ?>"
                       href="<?= BASE_URL ?>/monitoring.php">
                        📊 Monitoring
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $halaman_aktif === 'orang-hilang.php' ? 'active fw-semibold' : '' ?>"
                       href="<?= BASE_URL ?>/orang-hilang.php">
                        🔍 Orang Hilang
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $halaman_aktif === 'api-info.php' ? 'active fw-semibold' : '' ?>"
                       href="<?= BASE_URL ?>/api-info.php">
                        🔌 API
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>
