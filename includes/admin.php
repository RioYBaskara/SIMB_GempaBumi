<?php
/**
 * Helper panel admin: autentikasi, flash message, layout bersama.
 * Dipakai oleh semua halaman di folder /admin (kecuali login & setup).
 */
require_once __DIR__ . '/fungsi.php';

const ADMIN_IDLE_DETIK = 1800;   // otomatis keluar setelah 30 menit tidak aktif

function admin_masuk(): bool
{
    return !empty($_SESSION['admin_id']);
}

/** Panggil di baris awal setiap halaman admin */
function admin_wajib(): void
{
    $idle = time() - (int) ($_SESSION['admin_aktif'] ?? 0);
    if (!admin_masuk() || $idle > ADMIN_IDLE_DETIK) {
        unset($_SESSION['admin_id'], $_SESSION['admin_nama'], $_SESSION['admin_aktif']);
        header('Location: login.php');
        exit;
    }
    $_SESSION['admin_aktif'] = time();
    header('Cache-Control: no-store');
}

/* ---------------- Flash message ---------------- */
function flash_set(string $tipe, string $pesan): void
{
    $_SESSION['flash'][] = ['tipe' => $tipe, 'pesan' => $pesan];
}

function flash_tampil(): string
{
    $h = '';
    foreach ($_SESSION['flash'] ?? [] as $f) {
        $h .= '<div class="alert alert-' . e($f['tipe']) . ' alert-dismissible fade show" role="alert">'
            . e($f['pesan'])
            . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button></div>';
    }
    unset($_SESSION['flash']);
    return $h;
}

/** Hapus file foto di /uploads dengan aman (hanya nama file, tanpa path) */
function hapus_foto(?string $nama): void
{
    if (!$nama) {
        return;
    }
    $p = __DIR__ . '/../uploads/' . basename($nama);
    if (is_file($p)) {
        @unlink($p);
    }
}

/* ---------------- Layout ---------------- */
function admin_atas(string $judul, string $aktif = ''): void
{
    global $koneksi;
    $menunggu = (int) $koneksi->query(
        "SELECT COUNT(*) AS c FROM orang_hilang WHERE status_laporan = 'menunggu'"
    )->fetch_assoc()['c'];

    $item = [
        'dashboard' => ['index.php',   'Dashboard'],
        'laporan'   => ['laporan.php', 'Laporan Orang Hilang'],
        'kontak'    => ['kontak.php',  'Kontak Darurat'],
    ];
    ?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex, nofollow">
  <title><?= e($judul) ?> - Admin SIMB</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top">
  <div class="container-xl">
    <a class="navbar-brand fw-bold" href="index.php"><i class="bi bi-shield-lock text-warning"></i> SIMB Admin</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menuAdmin" aria-label="Buka menu">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="menuAdmin">
      <ul class="navbar-nav me-auto">
        <?php foreach ($item as $k => [$url, $teks]): ?>
          <li class="nav-item">
            <a class="nav-link <?= $aktif === $k ? 'active' : '' ?>" href="<?= $url ?>">
              <?= e($teks) ?>
              <?php if ($k === 'laporan' && $menunggu > 0): ?>
                <span class="badge bg-danger"><?= $menunggu ?></span>
              <?php endif; ?>
            </a>
          </li>
        <?php endforeach; ?>
        <li class="nav-item"><a class="nav-link" href="../index.php" target="_blank" rel="noopener">Lihat situs <i class="bi bi-box-arrow-up-right small"></i></a></li>
      </ul>
      <form method="post" action="logout.php" class="d-flex align-items-center gap-2">
        <?= csrf_field() ?>
        <span class="navbar-text small"><?= e($_SESSION['admin_nama'] ?? '') ?></span>
        <button class="btn btn-outline-light btn-sm" type="submit"><i class="bi bi-box-arrow-right"></i> Keluar</button>
      </form>
    </div>
  </div>
</nav>
<main class="container-xl py-3">
<?= flash_tampil() ?>
<?php
}

function admin_bawah(): void
{
    ?>
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
}
