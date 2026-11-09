<?php
/**
 * Endpoint JSON untuk frontend (alert bar, peta, daftar, gempa dirasakan).
 *
 * Contoh pemakaian:
 *   api/gempa_json.php?aksi=terbaru
 *   api/gempa_json.php?aksi=daftar&min_mag=5&limit=20
 *   api/gempa_json.php?aksi=dirasakan
 *   api/gempa_json.php?aksi=peta&hari=30
 *   api/gempa_json.php?aksi=statistik&dari=2026-10-01&sampai=2026-10-31
 *   api/gempa_json.php?aksi=status
 *
 * Setiap request otomatis memicu sinkron ke BMKG JIKA sudah lewat interval
 * (jadi tanpa cron pun data tetap segar selama ada pengunjung).
 */
define('API_MODE', true);
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../includes/bmkg.php';
require_once __DIR__ . '/../includes/statistik.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');

function kirim(array $payload, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function ambil_semua(mysqli_stmt $stmt): array
{
    $stmt->execute();
    $res  = $stmt->get_result();
    $rows = [];
    while ($r = $res->fetch_assoc()) {
        $rows[] = bmkg_format_baris($r);
    }
    $stmt->close();
    return $rows;
}

$kolom = "id, waktu, lintang, bujur, magnitudo, kedalaman, wilayah, potensi, dirasakan, shakemap";
$aksi  = $_GET['aksi'] ?? 'terbaru';

// 1. Sinkron "malas" (lazy) - gagal pun tetap lanjut pakai data di database
$sinkron = bmkg_sinkron_semua($koneksi);

// 2. Waktu sinkron sukses terakhir
$diperbarui = $koneksi->query(
    "SELECT MAX(created_at) AS t FROM log_sinkron_api WHERE status='sukses'"
)->fetch_assoc()['t'];

switch ($aksi) {

    case 'terbaru':
        $stmt = $koneksi->prepare("SELECT $kolom FROM gempa ORDER BY waktu DESC LIMIT 1");
        $rows = ambil_semua($stmt);
        kirim([
            'ok'         => true,
            'data'       => $rows[0] ?? null,
            'diperbarui' => $diperbarui,
        ]);

    case 'daftar':
        $minMag = isset($_GET['min_mag']) ? (float) $_GET['min_mag'] : 0.0;
        $limit  = max(1, min(200, (int) ($_GET['limit'] ?? 50)));
        $stmt   = $koneksi->prepare(
            "SELECT $kolom FROM gempa WHERE magnitudo >= ? ORDER BY waktu DESC LIMIT ?"
        );
        $stmt->bind_param('di', $minMag, $limit);
        kirim(['ok' => true, 'data' => ambil_semua($stmt), 'diperbarui' => $diperbarui]);

    case 'dirasakan':
        $limit = max(1, min(50, (int) ($_GET['limit'] ?? 15)));
        $stmt  = $koneksi->prepare(
            "SELECT $kolom FROM gempa
              WHERE dirasakan IS NOT NULL AND dirasakan <> ''
              ORDER BY waktu DESC LIMIT ?"
        );
        $stmt->bind_param('i', $limit);
        kirim(['ok' => true, 'data' => ambil_semua($stmt), 'diperbarui' => $diperbarui]);

    case 'peta':
        $hari = max(1, min(365, (int) ($_GET['hari'] ?? 30)));
        $stmt = $koneksi->prepare(
            "SELECT $kolom FROM gempa
              WHERE waktu >= NOW() - INTERVAL ? DAY
              ORDER BY waktu DESC LIMIT 500"
        );
        $stmt->bind_param('i', $hari);
        kirim(['ok' => true, 'data' => ambil_semua($stmt), 'diperbarui' => $diperbarui]);

    case 'statistik':
        $tz   = new DateTimeZone('Asia/Jakarta');
        $hariIni = new DateTime('today', $tz);

        // Parsing tanggal ketat: harus Y-m-d yang benar-benar valid
        $parse = function ($v) use ($tz) {
            $dt = DateTime::createFromFormat('!Y-m-d', (string) $v, $tz);
            return ($dt && $dt->format('Y-m-d') === $v) ? $dt : null;
        };
        $sampai = $parse($_GET['sampai'] ?? '') ?: clone $hariIni;
        if ($sampai > $hariIni) { $sampai = clone $hariIni; }
        $dari = $parse($_GET['dari'] ?? '') ?: (clone $sampai)->modify('-29 days');
        if ($dari > $sampai) { $dari = clone $sampai; }
        if ($dari->diff($sampai)->days > 365) { $dari = (clone $sampai)->modify('-365 days'); }

        kirim([
            'ok'         => true,
            'data'       => statistik_hitung($koneksi, $dari->format('Y-m-d'), $sampai->format('Y-m-d')),
            'diperbarui' => $diperbarui,
        ]);

    case 'status':
        $total = (int) $koneksi->query("SELECT COUNT(*) c FROM gempa")->fetch_assoc()['c'];
        kirim([
            'ok'          => true,
            'total_gempa' => $total,
            'diperbarui'  => $diperbarui,
            'sinkron'     => $sinkron,
        ]);

    default:
        kirim(['ok' => false, 'pesan' => 'Parameter aksi tidak dikenal'], 400);
}
