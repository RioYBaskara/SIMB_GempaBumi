<?php
/**
 * includes/fungsi.php
 * Kumpulan fungsi pembantu yang dipakai di seluruh aplikasi
 */

// Definisikan BASE_URL jika belum didefinisikan (misal dari API endpoint)
if (!defined('BASE_URL')) {
    define('BASE_URL', '/SIMB_GempaBumi');
}

// Pastikan koneksi sudah ada
if (!isset($koneksi)) {
    require_once __DIR__ . '/../config/koneksi.php';
}

/**
 * Ambil data dari URL menggunakan cURL
 * @param string $url   URL tujuan
 * @param int    $timeout  Batas waktu dalam detik
 * @return string|false  Isi respons atau false jika gagal
 */
function ambilDataCurl($url, $timeout = 10) {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => $timeout,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false, // Kompatibilitas XAMPP lokal
        CURLOPT_USERAGENT      => 'SIMB-GempaBumi/1.0 (Mahasiswa; PHP/' . PHP_VERSION . ')',
        CURLOPT_HTTPHEADER     => ['Accept: application/json'],
    ]);
    $hasil = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        error_log("cURL error untuk $url: $error");
        return false;
    }
    return $hasil;
}

/**
 * Cek apakah cache API masih valid (belum melewati batas waktu)
 * @param  string $key      Kunci cache
 * @param  int    $ttl_detik  Batas waktu cache dalam detik
 * @return string|false     Data cache atau false jika kedaluwarsa
 */
function ambilCache($key, $ttl_detik = 60) {
    global $koneksi;
    $stmt = $koneksi->prepare(
        "SELECT data, updated_at FROM api_cache WHERE cache_key = ? AND updated_at > DATE_SUB(NOW(), INTERVAL ? SECOND)"
    );
    $stmt->bind_param('si', $key, $ttl_detik);
    $stmt->execute();
    $hasil = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $hasil ? $hasil['data'] : false;
}

/**
 * Simpan data ke cache API
 * @param string $key   Kunci cache
 * @param string $data  Data yang akan disimpan
 */
function simpanCache($key, $data) {
    global $koneksi;
    $stmt = $koneksi->prepare(
        "INSERT INTO api_cache (cache_key, data) VALUES (?, ?)
         ON DUPLICATE KEY UPDATE data = VALUES(data), updated_at = NOW()"
    );
    $stmt->bind_param('ss', $key, $data);
    $stmt->execute();
    $stmt->close();
}

/**
 * Simpan satu data gempa ke database (abaikan duplikasi)
 * @param array $gempa  Array data gempa
 * @param string $sumber  Sumber data: 'autogempa', 'terkini', atau 'dirasakan'
 */
function simpanGempa($gempa, $sumber = 'terkini') {
    global $koneksi;

    // Parse datetime dari format BMKG
    $dt = isset($gempa['DateTime']) ? $gempa['DateTime'] : null;
    if (!$dt && isset($gempa['Tanggal']) && isset($gempa['Jam'])) {
        // Format tanggal BMKG: "07-Nov-24" jam "14:32:00 WIB"
        $jam_bersih = str_replace(' WIB', '', $gempa['Jam']);
        $dt_str = $gempa['Tanggal'] . ' ' . $jam_bersih;
        $dt_obj = DateTime::createFromFormat('d-M-y H:i:s', $dt_str);
        $dt = $dt_obj ? $dt_obj->format('Y-m-d H:i:s') : null;
    }
    if (!$dt) return; // Lewati jika datetime tidak valid

    // Ekstrak lintang dan bujur dari field Coordinates ("lintang,bujur")
    $lintang = isset($gempa['Lintang']) ? (float)$gempa['Lintang'] : 0;
    $bujur   = isset($gempa['Bujur'])   ? (float)$gempa['Bujur']   : 0;
    if (isset($gempa['Coordinates'])) {
        $coords  = explode(',', $gempa['Coordinates']);
        $lintang = isset($coords[0]) ? (float)trim($coords[0]) : $lintang;
        $bujur   = isset($coords[1]) ? (float)trim($coords[1]) : $bujur;
    }

    $magnitudo   = isset($gempa['Magnitude']) ? (float)$gempa['Magnitude'] : 0;
    $kedalaman   = isset($gempa['Kedalaman']) ? (int)filter_var($gempa['Kedalaman'], FILTER_SANITIZE_NUMBER_INT) : 0;
    $wilayah     = isset($gempa['Wilayah'])   ? $gempa['Wilayah']   : '';
    $potensi     = isset($gempa['Potensi'])   ? $gempa['Potensi']   : '';
    $dirasakan   = isset($gempa['Dirasakan']) ? $gempa['Dirasakan'] : '';
    $shakemap    = isset($gempa['Shakemap'])  ? $gempa['Shakemap']  : '';
    $tanggal     = isset($gempa['Tanggal'])   ? $gempa['Tanggal']   : '';
    $jam         = isset($gempa['Jam'])       ? $gempa['Jam']       : '';

    $stmt = $koneksi->prepare(
        "INSERT IGNORE INTO gempa (datetime, tanggal, jam, lintang, bujur, magnitudo, kedalaman_km, wilayah, potensi, dirasakan, shakemap, sumber)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt->bind_param('sssdddiissss',
        $dt, $tanggal, $jam, $lintang, $bujur,
        $magnitudo, $kedalaman, $wilayah, $potensi,
        $dirasakan, $shakemap, $sumber
    );
    $stmt->execute();
    $stmt->close();
}

/**
 * Hitung jarak dua titik koordinat dengan rumus Haversine (dalam km)
 */
function hitungJarakKm($lat1, $lon1, $lat2, $lon2) {
    $r = 6371; // Radius bumi dalam km
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $a = sin($dLat/2) * sin($dLat/2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($dLon/2) * sin($dLon/2);
    $c = 2 * atan2(sqrt($a), sqrt(1-$a));
    return $r * $c;
}

/**
 * Format waktu relatif (berapa menit/jam/hari lalu)
 * @param string $datetime  Format Y-m-d H:i:s
 * @return string
 */
function waktuRelatif($datetime) {
    $sekarang = time();
    $waktu    = strtotime($datetime);
    $selisih  = $sekarang - $waktu;

    if ($selisih < 60)         return 'Baru saja';
    if ($selisih < 3600)       return floor($selisih/60) . ' menit yang lalu';
    if ($selisih < 86400)      return floor($selisih/3600) . ' jam yang lalu';
    if ($selisih < 2592000)    return floor($selisih/86400) . ' hari yang lalu';
    return date('d M Y', $waktu);
}

/**
 * Tentukan warna/kelas Bootstrap berdasarkan magnitudo
 */
function warnaMagnitudo($mag) {
    if ($mag >= 7.0) return 'danger';
    if ($mag >= 6.0) return 'danger';
    if ($mag >= 5.0) return 'warning';
    if ($mag >= 4.0) return 'info';
    return 'secondary';
}

/**
 * Tampilkan pesan flash dari session
 */
function tampilkanFlash() {
    if (isset($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        $tipe = htmlspecialchars($f['tipe']);
        $pesan = htmlspecialchars($f['pesan']);
        echo "<div class='alert alert-{$tipe} alert-dismissible fade show' role='alert'>
                {$pesan}
                <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
              </div>";
        unset($_SESSION['flash']);
    }
}

/**
 * Set pesan flash ke session
 */
function setFlash($tipe, $pesan) {
    if (!isset($_SESSION)) session_start();
    $_SESSION['flash'] = ['tipe' => $tipe, 'pesan' => $pesan];
}

/**
 * Generate kode verifikasi acak untuk orang hilang
 */
function generateKodeVerifikasi($panjang = 8) {
    $karakter = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // Hindari karakter ambigu
    $kode = '';
    for ($i = 0; $i < $panjang; $i++) {
        $kode .= $karakter[random_int(0, strlen($karakter) - 1)];
    }
    return $kode;
}

/**
 * Validasi dan rename file upload gambar
 * @param array  $file    Data dari $_FILES
 * @param string $folder  Folder tujuan (harus sudah ada)
 * @return array          ['sukses' => bool, 'nama' => string, 'pesan' => string]
 */
function uploadGambar($file, $folder) {
    $ekstensi_ok = ['jpg', 'jpeg', 'png'];
    $mime_ok     = ['image/jpeg', 'image/png'];
    $maks_ukuran = 2 * 1024 * 1024; // 2 MB

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['sukses' => false, 'nama' => '', 'pesan' => 'Terjadi kesalahan saat upload.'];
    }
    if ($file['size'] > $maks_ukuran) {
        return ['sukses' => false, 'nama' => '', 'pesan' => 'Ukuran file melebihi batas 2 MB.'];
    }

    $mime_aktual = mime_content_type($file['tmp_name']);
    if (!in_array($mime_aktual, $mime_ok)) {
        return ['sukses' => false, 'nama' => '', 'pesan' => 'Tipe file tidak diizinkan. Hanya JPG/PNG.'];
    }

    $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $ekstensi_ok)) {
        return ['sukses' => false, 'nama' => '', 'pesan' => 'Ekstensi file tidak diizinkan.'];
    }

    // Beri nama acak agar tidak bisa ditebak
    $nama_baru = bin2hex(random_bytes(16)) . '.' . $ext;
    $tujuan    = rtrim($folder, '/') . '/' . $nama_baru;

    if (!move_uploaded_file($file['tmp_name'], $tujuan)) {
        return ['sukses' => false, 'nama' => '', 'pesan' => 'Gagal menyimpan file. Periksa izin folder.'];
    }

    return ['sukses' => true, 'nama' => $nama_baru, 'pesan' => 'Upload berhasil.'];
}

/**
 * Escape HTML output agar aman dari XSS
 */
function e($str) {
    return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
}
