<?php
require_once __DIR__ . '/../includes/fungsi.php';

// Logout lewat POST + CSRF agar tidak bisa dipicu oleh gambar/link dari situs lain
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_valid()) {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}
header('Location: login.php');
exit;
