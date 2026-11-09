<?php
/**
 * Pembuatan akun admin pertama.
 * - Hanya bisa dipakai selama akun admin masih berisi hash placeholder dari SQL.
 * - Wajib memasukkan SINKRON_KEY dari config/koneksi.php (bukti Anda pemilik server).
 * - Setelah berhasil, halaman ini otomatis menolak akses. Tetap disarankan MENGHAPUS file ini.
 */
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../includes/fungsi.php';

$row   = $koneksi->query("SELECT id, password_hash FROM admin ORDER BY id LIMIT 1")->fetch_assoc();
$butuh = !$row || strpos($row['password_hash'], 'REPLACE_THIS') !== false;
$kunciDefault = (SINKRON_KEY === 'ganti-dengan-kunci-rahasia-kamu');

$galat = [];
$selesai = false;

if ($butuh && !$kunciDefault && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $kunci = (string) ($_POST['kunci'] ?? '');
    $user  = trim((string) ($_POST['username'] ?? ''));
    $nama  = trim((string) ($_POST['nama_lengkap'] ?? ''));
    $pw    = (string) ($_POST['password'] ?? '');
    $pw2   = (string) ($_POST['password2'] ?? '');

    if (!csrf_valid())                              $galat[] = 'Sesi kedaluwarsa, muat ulang halaman.';
    if (!hash_equals(SINKRON_KEY, $kunci))          $galat[] = 'Kunci setup salah.';
    if (!preg_match('/^[A-Za-z0-9_.-]{3,50}$/', $user)) $galat[] = 'Username 3-50 karakter (huruf, angka, _ . -).';
    if ($nama === '' || mb_strlen($nama) > 100)     $galat[] = 'Nama lengkap wajib diisi (maks 100 karakter).';
    if (strlen($pw) < 10)                           $galat[] = 'Password minimal 10 karakter.';
    if ($pw !== $pw2)                               $galat[] = 'Konfirmasi password tidak sama.';

    if (!$galat) {
        $hash = password_hash($pw, PASSWORD_DEFAULT);
        if ($row) {
            $stmt = $koneksi->prepare("UPDATE admin SET username = ?, password_hash = ?, nama_lengkap = ? WHERE id = ?");
            $stmt->bind_param('sssi', $user, $hash, $nama, $row['id']);
        } else {
            $stmt = $koneksi->prepare("INSERT INTO admin (username, password_hash, nama_lengkap) VALUES (?,?,?)");
            $stmt->bind_param('sss', $user, $hash, $nama);
        }
        $stmt->execute();
        $stmt->close();
        $selesai = true;
        $butuh = false;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex, nofollow">
  <title>Setup Admin - SIMB</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<main class="container py-5" style="max-width:520px;">
  <h1 class="h4 fw-bold mb-3">Setup Akun Admin SIMB</h1>

  <?php if ($selesai): ?>
    <div class="alert alert-success">
      Akun admin berhasil dibuat. <strong>Hapus file <code>admin/setup.php</code></strong> dari server, lalu
      <a href="login.php">masuk ke panel admin</a>.
    </div>

  <?php elseif (!$butuh): ?>
    <div class="alert alert-secondary">Setup sudah selesai. Silakan hapus file ini, lalu <a href="login.php">login</a>.</div>

  <?php elseif ($kunciDefault): ?>
    <div class="alert alert-warning">
      Ubah dulu nilai <code>SINKRON_KEY</code> di <code>config/koneksi.php</code> menjadi string acak milik Anda,
      lalu muat ulang halaman ini. Kunci itu dipakai sebagai bukti bahwa Anda pemilik server.
    </div>

  <?php else: ?>
    <?php if ($galat): ?>
      <div class="alert alert-danger"><ul class="mb-0"><?php foreach ($galat as $g): ?><li><?= e($g) ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>
    <form method="post" class="card card-body">
      <?= csrf_field() ?>
      <div class="mb-3">
        <label class="form-label" for="kunci">Kunci setup (isi <code>SINKRON_KEY</code> dari config/koneksi.php)</label>
        <input class="form-control" type="password" id="kunci" name="kunci" required autocomplete="off">
      </div>
      <div class="mb-3">
        <label class="form-label" for="username">Username</label>
        <input class="form-control" id="username" name="username" required value="<?= e($_POST['username'] ?? '') ?>" autocomplete="username">
      </div>
      <div class="mb-3">
        <label class="form-label" for="nama_lengkap">Nama lengkap</label>
        <input class="form-control" id="nama_lengkap" name="nama_lengkap" required value="<?= e($_POST['nama_lengkap'] ?? '') ?>">
      </div>
      <div class="mb-3">
        <label class="form-label" for="password">Password (min. 10 karakter)</label>
        <input class="form-control" type="password" id="password" name="password" required minlength="10" autocomplete="new-password">
      </div>
      <div class="mb-3">
        <label class="form-label" for="password2">Ulangi password</label>
        <input class="form-control" type="password" id="password2" name="password2" required minlength="10" autocomplete="new-password">
      </div>
      <button class="btn btn-dark" type="submit">Buat akun admin</button>
    </form>
  <?php endif; ?>
</main>
</body>
</html>
