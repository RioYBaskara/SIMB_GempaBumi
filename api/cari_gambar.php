<?php
/**
 * api/cari_gambar.php
 * Endpoint JSON: cari gambar via Google Custom Search API
 * Parameter GET: ?q=kata+kunci&bagian=definisi (wajib)
 *
 * Cache hasil 7 hari di tabel gambar_edukasi
 * Fallback: placeholder SVG lokal jika API gagal
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../config/api_key.php';
require_once __DIR__ . '/../includes/fungsi.php';

// --- Validasi parameter ---
$q      = trim($_GET['q']      ?? '');
$bagian = trim($_GET['bagian'] ?? '');

$bagian_valid = ['definisi', 'penyebab', 'dampak', 'penanggulangan', 'mitigasi'];
if (!$q || !in_array($bagian, $bagian_valid)) {
    http_response_code(400);
    echo json_encode(['error' => 'Parameter q dan bagian wajib diisi.']);
    exit;
}

// --- Cek cache database (7 hari = 604800 detik) ---
$stmt = $koneksi->prepare(
    "SELECT * FROM gambar_edukasi
     WHERE bagian = ? AND kata_kunci = ? AND created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
     ORDER BY id ASC LIMIT 5"
);
$stmt->bind_param('ss', $bagian, $q);
$stmt->execute();
$cache_rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

if (!empty($cache_rows)) {
    // Kembalikan dari cache
    $hasil = array_map(function($r) {
        return [
            'url'         => e($r['url_gambar']),
            'thumbnail'   => e($r['thumbnail']),
            'judul'       => e($r['judul']),
            'sumber'      => e($r['sumber']),
            'link_sumber' => e($r['link_sumber']),
        ];
    }, $cache_rows);
    echo json_encode(['gambar' => $hasil, 'sumber' => 'cache']);
    exit;
}

// --- Cek apakah API key sudah diisi ---
if (GOOGLE_API_KEY === 'ISI_API_KEY_GOOGLE_ANDA_DI_SINI' || GOOGLE_CX === 'ISI_SEARCH_ENGINE_ID_CX_ANDA_DI_SINI') {
    echo json_encode([
        'gambar'  => [],
        'sumber'  => 'none',
        'pesan'   => 'API Key belum dikonfigurasi. Lihat README.md untuk panduan.',
        'placeholder' => BASE_URL . '/assets/img/placeholder.svg',
    ]);
    exit;
}

// --- Panggil Google Custom Search API ---
$params = http_build_query([
    'key'        => GOOGLE_API_KEY,
    'cx'         => GOOGLE_CX,
    'q'          => $q,
    'searchType' => 'image',
    'num'        => 5,        // Maks 10 per kueri, kita ambil 5
    'safe'       => 'active',
    'imgSize'    => 'medium',
    // Filter lisensi terbuka (tidak selalu tersedia di semua gambar)
    'rights'     => 'cc_publicdomain|cc_attribute|cc_sharealike',
]);
$url_api = 'https://www.googleapis.com/customsearch/v1?' . $params;

$raw = ambilDataCurl($url_api, 15);

if ($raw === false) {
    echo json_encode(['gambar' => [], 'sumber' => 'error', 'pesan' => 'Gagal menghubungi Google API.']);
    exit;
}

$data = json_decode($raw, true);

// Cek error dari Google
if (isset($data['error'])) {
    $msg = $data['error']['message'] ?? 'Error tidak diketahui';
    echo json_encode(['gambar' => [], 'sumber' => 'error', 'pesan' => 'Google API error: ' . $msg]);
    exit;
}

if (!isset($data['items']) || empty($data['items'])) {
    echo json_encode(['gambar' => [], 'sumber' => 'empty', 'pesan' => 'Tidak ada gambar ditemukan.']);
    exit;
}

// --- Proses dan simpan hasil ---
$hasil = [];
foreach ($data['items'] as $item) {
    $url_gambar = $item['link'] ?? '';
    $thumbnail  = $item['image']['thumbnailLink'] ?? $url_gambar;
    $judul      = $item['title'] ?? '';
    $sumber     = $item['displayLink'] ?? '';
    $link_sumber = $item['image']['contextLink'] ?? '';

    // Filter keamanan: hanya HTTPS, ekstensi aman
    if (strpos($url_gambar, 'https://') !== 0) continue;
    $ekstensi = strtolower(pathinfo(parse_url($url_gambar, PHP_URL_PATH), PATHINFO_EXTENSION));
    if (!in_array($ekstensi, ['jpg', 'jpeg', 'png', 'webp'])) continue;

    // Simpan ke database (cache)
    $stmt = $koneksi->prepare(
        "INSERT INTO gambar_edukasi (bagian, kata_kunci, url_gambar, thumbnail, judul, sumber, link_sumber)
         VALUES (?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt->bind_param('sssssss', $bagian, $q, $url_gambar, $thumbnail, $judul, $sumber, $link_sumber);
    $stmt->execute();
    $stmt->close();

    $hasil[] = [
        'url'         => e($url_gambar),
        'thumbnail'   => e($thumbnail),
        'judul'       => e($judul),
        'sumber'      => e($sumber),
        'link_sumber' => e($link_sumber),
    ];
}

echo json_encode(['gambar' => $hasil, 'sumber' => 'google', 'total' => count($hasil)]);
