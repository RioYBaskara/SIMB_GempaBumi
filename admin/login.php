<?php
/**
 * admin/login.php — Halaman login admin sederhana
 * Untuk menghapus laporan palsu dan mengelola gambar edukasi
 */

session_start();
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../includes/fungsi.php';

// Jika sudah login, redirect ke gambar.php
if (!empty($_SESSION['admin_id'])) {
    header('Location: ' . BASE_URL . '/admin/gambar.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password']     ?? '';

    if (!$username || !$password) {
        $error = 'Username dan password wajib diisi.';
    } else {
        $stmt = $koneksi->prepare("SELECT id, username, password_hash FROM admin WHERE username = ?");
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $admin = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($admin && password_verify($password, $admin['password_hash'])) {
            $_SESSION['admin_id']   = $admin['id'];
            $_SESSION['admin_user'] = $admin['username'];
            header('Location: ' . BASE_URL . '/admin/gambar.php');
            exit;
        } else {
            $error = 'Username atau password salah.';
            // Tunda 1 detik untuk mengurangi brute-force
            sleep(1);
        }
    }
}

$judul_halaman = 'Login Admin';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-sm-8 col-md-5 col-lg-4">
            <div class="card shadow-sm">
                <div class="card-header text-center fw-bold">
                    🔐 Login Admin
                </div>
                <div class="card-body px-4 py-4">
                    <?php if ($error): ?>
                    <div class="alert alert-danger py-2 small"><?= e($error) ?></div>
                    <?php endif; ?>

                    <form method="post">
                        <div class="mb-3">
                            <label class="form-label small">Username</label>
                            <input type="text" name="username" class="form-control"
                                   value="<?= e($_POST['username'] ?? '') ?>" required autofocus>
                        </div>
                        <div class="mb-4">
                            <label class="form-label small">Password</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Masuk</button>
                    </form>

                    <div class="text-center mt-3">
                        <a href="<?= BASE_URL ?>/" class="small text-muted">← Kembali ke Beranda</a>
                    </div>
                </div>
            </div>
            <p class="text-center text-muted small mt-3">
                Default: admin / admin123 (ganti segera!)
            </p>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
