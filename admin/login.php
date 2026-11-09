<?php
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../includes/admin.php';

if (admin_masuk()) {
    header('Location: index.php');
    exit;
}

const LOGIN_MAKS_GAGAL = 5;     // percobaan gagal
const LOGIN_JENDELA_MNT = 15;   // dalam rentang menit

$galat = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ip   = ip_klien();
    $user = mb_substr(trim((string) ($_POST['username'] ?? '')), 0, 50);
    $pw   = (string) ($_POST['password'] ?? '');

    $koneksi->query("DELETE FROM login_gagal WHERE created_at < NOW() - INTERVAL 1 DAY");

    $stmt = $koneksi->prepare(
        "SELECT COUNT(*) AS c FROM login_gagal WHERE ip = ? AND created_at > NOW() - INTERVAL ? MINUTE"
    );
    $mnt = LOGIN_JENDELA_MNT;
    $stmt->bind_param('si', $ip, $mnt);
    $stmt->execute();
    $gagal = (int) $stmt->get_result()->fetch_assoc()['c'];
    $stmt->close();

    if (!csrf_valid()) {
        $galat = 'Sesi kedaluwarsa. Muat ulang halaman lalu coba lagi.';
    } elseif ($gagal >= LOGIN_MAKS_GAGAL) {
        $galat = 'Terlalu banyak percobaan gagal. Coba lagi dalam ' . LOGIN_JENDELA_MNT . ' menit.';
    } else {
        $stmt = $koneksi->prepare("SELECT id, password_hash, nama_lengkap FROM admin WHERE username = ?");
        $stmt->bind_param('s', $user);
        $stmt->execute();
        $a = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        // Tetap menghitung hash walau user tidak ada, agar waktu respons tidak membocorkan username
        $hash = $a ? $a['password_hash'] : password_hash('dummy-' . $user, PASSWORD_DEFAULT);
        $ok   = password_verify($pw, $hash) && $a;

        if ($ok) {
            session_regenerate_id(true);                 // cegah session fixation
            $_SESSION['admin_id']    = (int) $a['id'];
            $_SESSION['admin_nama']  = $a['nama_lengkap'];
            $_SESSION['admin_aktif'] = time();

            $stmt = $koneksi->prepare("DELETE FROM login_gagal WHERE ip = ?");
            $stmt->bind_param('s', $ip);
            $stmt->execute();
            $stmt->close();

            if (password_needs_rehash($hash, PASSWORD_DEFAULT)) {
                $baru = password_hash($pw, PASSWORD_DEFAULT);
                $u = $koneksi->prepare("UPDATE admin SET password_hash = ? WHERE id = ?");
                $u->bind_param('si', $baru, $a['id']);
                $u->execute();
                $u->close();
            }
            header('Location: index.php');
            exit;
        }

        $stmt = $koneksi->prepare("INSERT INTO login_gagal (ip, username) VALUES (?, ?)");
        $stmt->bind_param('ss', $ip, $user);
        $stmt->execute();
        $stmt->close();
        $galat = 'Username atau password salah.';   // pesan sama untuk kedua kasus
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex, nofollow">
  <title>Login Admin - SIMB</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
</head>
<body class="bg-dark">
<main class="container py-5" style="max-width:420px;">
  <div class="card shadow">
    <div class="card-body p-4">
      <h1 class="h5 fw-bold text-center mb-3"><i class="bi bi-shield-lock text-danger"></i> Login Admin SIMB</h1>
      <?php if ($galat): ?><div class="alert alert-danger py-2"><?= e($galat) ?></div><?php endif; ?>
      <form method="post">
        <?= csrf_field() ?>
        <div class="mb-3">
          <label class="form-label" for="username">Username</label>
          <input class="form-control" id="username" name="username" required autofocus autocomplete="username" value="<?= e($_POST['username'] ?? '') ?>">
        </div>
        <div class="mb-3">
          <label class="form-label" for="password">Password</label>
          <input class="form-control" type="password" id="password" name="password" required autocomplete="current-password">
        </div>
        <button class="btn btn-dark w-100" type="submit">Masuk</button>
      </form>
      <div class="text-center mt-3"><a class="small" href="../index.php">&laquo; Kembali ke situs</a></div>
    </div>
  </div>
</main>
</body>
</html>
