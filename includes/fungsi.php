<?php
/**
 * Fungsi bantu bersama: sesi, CSRF, rate limit, upload foto, label status.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start(['cookie_httponly' => true, 'cookie_samesite' => 'Lax']);
}

/** Escape output HTML (cegah XSS) */
function e($s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/* ---------------- CSRF ---------------- */
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function csrf_valid(): bool
{
    return !empty($_SESSION['csrf'])
        && isset($_POST['csrf']) && is_string($_POST['csrf'])
        && hash_equals($_SESSION['csrf'], $_POST['csrf']);
}

/* ---------------- Identitas laporan ---------------- */

/** Hanya REMOTE_ADDR (header X-Forwarded-For mudah dipalsukan) */
function ip_klien(): string
{
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

/** Kode publik, tanpa karakter membingungkan (0/O, 1/I) -> mis. OH-7K2M9Q */
function kode_laporan_baru(): string
{
    $abj = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $k = '';
    for ($i = 0; $i < 6; $i++) {
        $k .= $abj[random_int(0, strlen($abj) - 1)];
    }
    return 'OH-' . $k;
}

function kode_valid(string $k): bool
{
    return (bool) preg_match('/^OH-[A-Z2-9]{6}$/', $k);
}

/** Token rahasia pelapor (32 karakter hex) */
function token_baru(): string
{
    return bin2hex(random_bytes(16));
}

/* ---------------- Anti spam ---------------- */
function batas_laporan_terlampaui(mysqli $db, string $ip, int $maks = 5, int $menit = 60): bool
{
    $stmt = $db->prepare(
        "SELECT COUNT(*) AS c FROM orang_hilang
          WHERE ip_pelapor = ? AND created_at > NOW() - INTERVAL ? MINUTE"
    );
    $stmt->bind_param('si', $ip, $menit);
    $stmt->execute();
    $c = (int) $stmt->get_result()->fetch_assoc()['c'];
    $stmt->close();
    return $c >= $maks;
}

/* ---------------- Upload foto ---------------- */
/**
 * Validasi + simpan foto. Gambar dibuat ulang (re-encode) ke JPG sehingga:
 *  - file bukan gambar sungguhan akan ditolak,
 *  - data EXIF (termasuk koordinat GPS) terhapus demi privasi,
 *  - ukuran dikecilkan (maks 900px).
 * Return: ['ok'=>bool, 'nama'=>?string, 'pesan'=>?string]
 */
function simpan_foto(array $f): array
{
    if (($f['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return ['ok' => true, 'nama' => null];
    }
    if ($f['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($f['tmp_name'])) {
        return ['ok' => false, 'pesan' => 'Upload foto gagal. Coba lagi atau lewati foto.'];
    }
    if ($f['size'] > 2 * 1024 * 1024) {
        return ['ok' => false, 'pesan' => 'Ukuran foto maksimal 2 MB.'];
    }

    $info = @getimagesize($f['tmp_name']);
    $izin = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp'];
    if (!$info || !isset($izin[$info[2]])) {
        return ['ok' => false, 'pesan' => 'Foto harus berformat JPG, PNG, atau WebP.'];
    }

    $dir  = __DIR__ . '/../uploads/';
    $nama = bin2hex(random_bytes(12));                 // nama acak, bukan nama asli

    if (function_exists('imagecreatefromstring') && function_exists('imagejpeg')) {
        $img = @imagecreatefromstring((string) file_get_contents($f['tmp_name']));
        if (!$img) {
            return ['ok' => false, 'pesan' => 'File foto rusak atau tidak dapat dibaca.'];
        }

        // Putar sesuai orientasi kamera HP (EXIF) sebelum EXIF dibuang
        if ($info[2] === IMAGETYPE_JPEG && function_exists('exif_read_data')) {
            $ex  = @exif_read_data($f['tmp_name']);
            $ori = $ex['Orientation'] ?? 1;
            $sudut = [3 => 180, 6 => -90, 8 => 90][$ori] ?? 0;
            if ($sudut) {
                $rot = imagerotate($img, $sudut, 0);
                if ($rot) { $img = $rot; }
            }
        }

        $w = imagesx($img);
        $h = imagesy($img);
        $skala = min(1, 900 / max($w, $h));
        $nw = max(1, (int) round($w * $skala));
        $nh = max(1, (int) round($h * $skala));

        $kanvas = imagecreatetruecolor($nw, $nh);
        imagefill($kanvas, 0, 0, imagecolorallocate($kanvas, 255, 255, 255)); // latar putih (PNG transparan)
        imagecopyresampled($kanvas, $img, 0, 0, 0, 0, $nw, $nh, $w, $h);

        $nama .= '.jpg';
        $ok = imagejpeg($kanvas, $dir . $nama, 82);
        imagedestroy($img);
        imagedestroy($kanvas);
        return $ok ? ['ok' => true, 'nama' => $nama]
                   : ['ok' => false, 'pesan' => 'Gagal menyimpan foto.'];
    }

    // Cadangan jika ekstensi GD tidak aktif: ekstensi ditentukan dari tipe ASLI gambar
    $nama .= '.' . $izin[$info[2]];
    return move_uploaded_file($f['tmp_name'], $dir . $nama)
        ? ['ok' => true, 'nama' => $nama]
        : ['ok' => false, 'pesan' => 'Gagal menyimpan foto.'];
}

/* ---------------- Tampilan ---------------- */
function label_status_orang(string $s): array
{
    switch ($s) {
        case 'ditemukan':       return ['Ditemukan',        'bg-success'];
        case 'dalam_perawatan': return ['Dalam Perawatan',  'bg-warning text-dark'];
        default:                return ['Masih Dicari',     'bg-danger'];
    }
}

/** 0812xxx / +62812xxx -> 62812xxx (format wa.me) */
function nomor_wa(string $no): string
{
    $d = preg_replace('/\D/', '', $no);
    if (strpos($d, '0') === 0) {
        $d = '62' . substr($d, 1);
    }
    return $d;
}

/** URL absolut folder halaman yang sedang dibuka (untuk link bagikan) */
function url_folder_ini(): string
{
    $skema = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host  = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $path  = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
    return $skema . '://' . $host . $path . '/';
}
