<?php
/**
 * api/statistik.php
 * Endpoint JSON untuk data Chart.js di monitoring.php
 * Mengembalikan:
 *   - per_hari:  jumlah gempa per hari (30 hari terakhir)
 *   - distribusi_mag: distribusi magnitudo (<4, 4-4.9, 5-5.9, >=6)
 *   - kedalaman: distribusi kategori kedalaman
 *   - tren:      magnitudo rata-rata per hari
 *   - ringkasan: total, terbesar, rata-rata, hari ini
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../config/koneksi.php';

// ---- 1. Jumlah gempa per hari (30 hari terakhir) ----
$stmt = $koneksi->prepare(
    "SELECT DATE(datetime) AS hari, COUNT(*) AS jumlah
     FROM gempa
     WHERE datetime >= DATE_SUB(NOW(), INTERVAL 30 DAY)
     GROUP BY DATE(datetime)
     ORDER BY hari ASC"
);
$stmt->execute();
$rows_hari = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Isi tanggal yang tidak ada dengan 0
$per_hari_label = [];
$per_hari_data  = [];
$hari_map = [];
foreach ($rows_hari as $r) $hari_map[$r['hari']] = (int)$r['jumlah'];

for ($i = 29; $i >= 0; $i--) {
    $tgl = date('Y-m-d', strtotime("-$i days"));
    $per_hari_label[] = date('d/m', strtotime($tgl));
    $per_hari_data[]  = isset($hari_map[$tgl]) ? $hari_map[$tgl] : 0;
}

// ---- 2. Distribusi magnitudo ----
$stmt = $koneksi->prepare(
    "SELECT
        SUM(CASE WHEN magnitudo < 4.0 THEN 1 ELSE 0 END) AS di_bawah_4,
        SUM(CASE WHEN magnitudo >= 4.0 AND magnitudo < 5.0 THEN 1 ELSE 0 END) AS m4_49,
        SUM(CASE WHEN magnitudo >= 5.0 AND magnitudo < 6.0 THEN 1 ELSE 0 END) AS m5_59,
        SUM(CASE WHEN magnitudo >= 6.0 THEN 1 ELSE 0 END) AS m6_plus
     FROM gempa"
);
$stmt->execute();
$dist = $stmt->get_result()->fetch_assoc();
$stmt->close();

// ---- 3. Distribusi kedalaman ----
$stmt = $koneksi->prepare(
    "SELECT
        SUM(CASE WHEN kedalaman_km < 60 THEN 1 ELSE 0 END) AS dangkal,
        SUM(CASE WHEN kedalaman_km >= 60 AND kedalaman_km <= 300 THEN 1 ELSE 0 END) AS menengah,
        SUM(CASE WHEN kedalaman_km > 300 THEN 1 ELSE 0 END) AS dalam
     FROM gempa"
);
$stmt->execute();
$kedalaman = $stmt->get_result()->fetch_assoc();
$stmt->close();

// ---- 4. Tren magnitudo rata-rata per hari ----
$stmt = $koneksi->prepare(
    "SELECT DATE(datetime) AS hari, ROUND(AVG(magnitudo),1) AS rata_mag, MAX(magnitudo) AS maks_mag
     FROM gempa
     WHERE datetime >= DATE_SUB(NOW(), INTERVAL 30 DAY)
     GROUP BY DATE(datetime)
     ORDER BY hari ASC"
);
$stmt->execute();
$rows_tren = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Sesuaikan dengan label per_hari (30 hari)
$tren_rata  = array_fill(0, 30, null);
$tren_maks  = array_fill(0, 30, null);
foreach ($rows_tren as $r) {
    $idx = 29 - (int)floor((time() - strtotime($r['hari'])) / 86400);
    if ($idx >= 0 && $idx < 30) {
        $tren_rata[$idx] = (float)$r['rata_mag'];
        $tren_maks[$idx] = (float)$r['maks_mag'];
    }
}

// ---- 5. Ringkasan statistik ----
$stmt = $koneksi->prepare(
    "SELECT
        COUNT(*) AS total,
        MAX(magnitudo) AS terbesar,
        ROUND(AVG(magnitudo),2) AS rata_rata,
        SUM(CASE WHEN DATE(datetime) = CURDATE() THEN 1 ELSE 0 END) AS hari_ini
     FROM gempa"
);
$stmt->execute();
$ringkasan = $stmt->get_result()->fetch_assoc();
$stmt->close();

// --- Keluarkan JSON ---
echo json_encode([
    'per_hari' => [
        'label' => $per_hari_label,
        'data'  => $per_hari_data,
    ],
    'distribusi_mag' => [
        'label' => ['< 4.0', '4.0 – 4.9', '5.0 – 5.9', '≥ 6.0'],
        'data'  => [
            (int)($dist['di_bawah_4'] ?? 0),
            (int)($dist['m4_49']      ?? 0),
            (int)($dist['m5_59']      ?? 0),
            (int)($dist['m6_plus']    ?? 0),
        ],
        'warna' => ['#64748b','#0891b2','#d97706','#dc2626'],
    ],
    'kedalaman' => [
        'label' => ['Dangkal (< 60 km)', 'Menengah (60–300 km)', 'Dalam (> 300 km)'],
        'data'  => [
            (int)($kedalaman['dangkal']   ?? 0),
            (int)($kedalaman['menengah']  ?? 0),
            (int)($kedalaman['dalam']     ?? 0),
        ],
        'warna' => ['#f59e0b','#3b82f6','#6366f1'],
    ],
    'tren' => [
        'label'    => $per_hari_label,
        'rata'     => $tren_rata,
        'maks'     => $tren_maks,
    ],
    'ringkasan' => [
        'total'     => (int)($ringkasan['total']     ?? 0),
        'terbesar'  => (float)($ringkasan['terbesar'] ?? 0),
        'rata_rata' => (float)($ringkasan['rata_rata'] ?? 0),
        'hari_ini'  => (int)($ringkasan['hari_ini']  ?? 0),
    ],
], JSON_UNESCAPED_UNICODE);
