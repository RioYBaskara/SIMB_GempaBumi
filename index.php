<?php
/**
 * index.php — Beranda SIMB GempaBumi
 * Halaman utama: status gempa terbaru, peta, tabel, dan tindakan darurat
 */

require_once __DIR__ . '/config/koneksi.php';
require_once __DIR__ . '/includes/fungsi.php';

date_default_timezone_set('Asia/Jakarta');

// ============================================================
// 1. AMBIL DATA GEMPA TERBARU
// ============================================================

// Panggil API internal (sudah ada caching di dalamnya)
$gempa_terbaru = null;
$sumber_data   = 'database';
$pesan_fallback = '';

// Coba BMKG via fungsi cURL
$raw_terbaru = ambilDataCurl('https://data.bmkg.go.id/DataMKG/TEWS/autogempa.json');
if ($raw_terbaru) {
    $json = json_decode($raw_terbaru, true);
    if (isset($json['Infogempa']['gempa'])) {
        $gempa_terbaru = $json['Infogempa']['gempa'];
        $sumber_data   = 'bmkg';
        simpanGempa($gempa_terbaru, 'autogempa');
        simpanCache('autogempa', $raw_terbaru);
    }
}

// Fallback: ambil dari DB
if (!$gempa_terbaru) {
    $stmt = $koneksi->prepare("SELECT * FROM gempa ORDER BY datetime DESC LIMIT 1");
    $stmt->execute();
    $baris = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($baris) {
        $gempa_terbaru = [
            'DateTime'  => $baris['datetime'],
            'Tanggal'   => $baris['tanggal'],
            'Jam'       => $baris['jam'],
            'Lintang'   => $baris['lintang'],
            'Bujur'     => $baris['bujur'],
            'Magnitude' => $baris['magnitudo'],
            'Kedalaman' => $baris['kedalaman_km'] . ' km',
            'Wilayah'   => $baris['wilayah'],
            'Potensi'   => $baris['potensi'],
            'Dirasakan' => $baris['dirasakan'],
            'Shakemap'  => $baris['shakemap'],
        ];
        $sumber_data    = 'database';
        $pesan_fallback = 'Data BMKG sedang tidak dapat diakses, menampilkan data tersimpan.';
    }
}

// ============================================================
// 2. DAFTAR 15 GEMPA M 5.0+ UNTUK PETA & TABEL
// ============================================================

$daftar_terkini  = [];
$daftar_dirasakan = [];

$raw_terkini = ambilDataCurl('https://data.bmkg.go.id/DataMKG/TEWS/gempaterkini.json');
if ($raw_terkini) {
    $j = json_decode($raw_terkini, true);
    if (isset($j['Infogempa']['gempa'])) {
        $daftar_terkini = $j['Infogempa']['gempa'];
        if (isset($daftar_terkini['Tanggal'])) $daftar_terkini = [$daftar_terkini]; // pastikan array
        foreach ($daftar_terkini as $g) simpanGempa($g, 'terkini');
    }
}

// Fallback daftar terkini dari DB
if (empty($daftar_terkini)) {
    $stmt = $koneksi->prepare("SELECT * FROM gempa WHERE magnitudo >= 5.0 ORDER BY datetime DESC LIMIT 15");
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    foreach ($rows as $b) {
        $daftar_terkini[] = [
            'DateTime'  => $b['datetime'], 'Tanggal' => $b['tanggal'], 'Jam' => $b['jam'],
            'Lintang'   => $b['lintang'],  'Bujur'   => $b['bujur'],
            'Magnitude' => $b['magnitudo'],'Kedalaman'=> $b['kedalaman_km'] . ' km',
            'Wilayah'   => $b['wilayah'], 'Potensi' => $b['potensi'], 'Dirasakan' => $b['dirasakan'],
        ];
    }
}

$raw_dirasakan = ambilDataCurl('https://data.bmkg.go.id/DataMKG/TEWS/gempadirasakan.json');
if ($raw_dirasakan) {
    $j = json_decode($raw_dirasakan, true);
    if (isset($j['Infogempa']['gempa'])) {
        $daftar_dirasakan = $j['Infogempa']['gempa'];
        if (isset($daftar_dirasakan['Tanggal'])) $daftar_dirasakan = [$daftar_dirasakan];
        foreach ($daftar_dirasakan as $g) simpanGempa($g, 'dirasakan');
    }
}

// Fallback dirasakan dari DB
if (empty($daftar_dirasakan)) {
    $stmt = $koneksi->prepare(
        "SELECT * FROM gempa WHERE dirasakan IS NOT NULL AND dirasakan != '' ORDER BY datetime DESC LIMIT 15"
    );
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    foreach ($rows as $b) {
        $daftar_dirasakan[] = [
            'DateTime'  => $b['datetime'], 'Tanggal' => $b['tanggal'], 'Jam' => $b['jam'],
            'Lintang'   => $b['lintang'],  'Bujur'   => $b['bujur'],
            'Magnitude' => $b['magnitudo'],'Kedalaman'=> $b['kedalaman_km'] . ' km',
            'Wilayah'   => $b['wilayah'], 'Potensi' => $b['potensi'], 'Dirasakan' => $b['dirasakan'],
        ];
    }
}

// ============================================================
// 3. LOGIKA STATUS BANNER
// ============================================================

$status_banner = 'hijau';
$teks_banner   = '✅ Status Normal: Tidak ada gempa signifikan dalam 24 jam terakhir.';

// Cek gempa dalam 24 jam terakhir
$stmt = $koneksi->prepare(
    "SELECT magnitudo, potensi FROM gempa
     WHERE datetime >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
     ORDER BY magnitudo DESC LIMIT 1"
);
$stmt->execute();
$gempa_24j = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($gempa_24j) {
    $m = (float)$gempa_24j['magnitudo'];
    $p = strtolower($gempa_24j['potensi'] ?? '');
    if ($m >= 6.0 || (strpos($p, 'tsunami') !== false && strpos($p, 'tidak berpotensi') === false)) {
        $status_banner = 'merah';
        $teks_banner   = '⚠️ PERINGATAN: Gempa M' . number_format($m, 1) . ' terdeteksi. Pantau informasi resmi BMKG!';
    } elseif ($m >= 5.0) {
        $status_banner = 'kuning';
        $teks_banner   = '⚡ PERHATIAN: Gempa M' . number_format($m, 1) . ' terdeteksi dalam 24 jam terakhir.';
    }
}

// ============================================================
// 4. DETEKSI GEMPA SUSULAN (Haversine)
// ============================================================

$gempa_susulan = [];
$mainshock     = null;

// Cari mainshock: gempa M>=5.0 terbaru dalam 7 hari
$stmt = $koneksi->prepare(
    "SELECT * FROM gempa WHERE magnitudo >= 5.0 AND datetime >= DATE_SUB(NOW(), INTERVAL 7 DAY)
     ORDER BY datetime DESC LIMIT 1"
);
$stmt->execute();
$mainshock = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($mainshock) {
    // Cari gempa susulan: setelah mainshock, radius <= 100 km, dalam 72 jam
    $stmt = $koneksi->prepare(
        "SELECT * FROM gempa
         WHERE datetime > ? AND datetime <= DATE_ADD(?, INTERVAL 72 HOUR)
         AND id != ?
         ORDER BY datetime ASC"
    );
    $stmt->bind_param('ssi', $mainshock['datetime'], $mainshock['datetime'], $mainshock['id']);
    $stmt->execute();
    $kandidat = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    foreach ($kandidat as $k) {
        $jarak = hitungJarakKm(
            (float)$mainshock['lintang'], (float)$mainshock['bujur'],
            (float)$k['lintang'],         (float)$k['bujur']
        );
        if ($jarak <= 100) {
            $k['jarak_km'] = round($jarak, 1);
            $gempa_susulan[] = $k;
        }
    }
}

// ============================================================
// 5. SIAPKAN DATA PETA (JSON untuk Leaflet)
// ============================================================

$peta_data = [];
// Tambahkan gempa terbaru sebagai titik pertama
if ($gempa_terbaru) {
    $peta_data[] = [
        'lintang'     => (float)$gempa_terbaru['Lintang'],
        'bujur'       => (float)$gempa_terbaru['Bujur'],
        'magnitudo'   => (float)$gempa_terbaru['Magnitude'],
        'wilayah'     => $gempa_terbaru['Wilayah'],
        'datetime'    => $gempa_terbaru['DateTime'],
        'kedalaman_km'=> filter_var($gempa_terbaru['Kedalaman'], FILTER_SANITIZE_NUMBER_INT),
        'is_terbaru'  => true,
    ];
}
// Tambahkan daftar M5.0+
foreach (array_slice($daftar_terkini, 0, 15) as $g) {
    $peta_data[] = [
        'lintang'     => (float)($g['Lintang'] ?? 0),
        'bujur'       => (float)($g['Bujur']   ?? 0),
        'magnitudo'   => (float)($g['Magnitude'] ?? 0),
        'wilayah'     => $g['Wilayah'] ?? '',
        'datetime'    => $g['DateTime'] ?? '',
        'kedalaman_km'=> filter_var($g['Kedalaman'] ?? '0', FILTER_SANITIZE_NUMBER_INT),
        'is_terbaru'  => false,
    ];
}

// ============================================================
// 6. RENDER HALAMAN
// ============================================================

$judul_halaman = 'Beranda';
$deskripsi     = 'Pantau gempa bumi terkini di Indonesia dari BMKG secara real-time';
require_once __DIR__ . '/includes/header.php';

// BASE_URL untuk JavaScript
$base_url = BASE_URL;
?>

<!-- Script: kirim BASE_URL ke JavaScript -->
<script>var BASE_URL = '<?= $base_url ?>';</script>

<!-- ============================================================ -->
<!-- BANNER STATUS                                                 -->
<!-- ============================================================ -->
<div id="banner-status" class="banner-status banner-<?= $status_banner ?>" role="alert">
    <div class="container">
        <span class="banner-teks"><?= e($teks_banner) ?></span>
    </div>
</div>

<!-- Notifikasi data dari database (bukan langsung BMKG) -->
<?php if ($pesan_fallback): ?>
<div class="notif-data-lama">
    <div class="container">
        ⚠️ <?= e($pesan_fallback) ?>
    </div>
</div>
<?php endif; ?>

<!-- ============================================================ -->
<!-- KONTEN UTAMA                                                  -->
<!-- ============================================================ -->
<div class="container py-4">

    <!-- KARTU GEMPA TERBARU -->
    <div class="row g-4 mb-4">
        <div class="col-12">
            <div class="card card-gempa-terbaru" id="konten-gempa-terbaru">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>🌍 GEMPA TERBARU</span>
                    <small class="text-warning" id="waktu-update">
                        <span class="spinner-bmkg me-1"></span> Memuat data...
                    </small>
                </div>
                <?php if ($gempa_terbaru): ?>
                <div class="card-body">
                    <div class="row align-items-center g-3">

                        <!-- Magnitudo -->
                        <div class="col-md-2 text-center">
                            <div class="label-magnitudo">MAGNITUDO</div>
                            <div class="angka-magnitudo text-<?= warnaMagnitudo((float)$gempa_terbaru['Magnitude']) ?>"
                                 id="nilai-magnitudo">
                                <?= number_format((float)$gempa_terbaru['Magnitude'], 1) ?>
                            </div>
                            <div class="label-magnitudo">SR (Skala Richter)</div>
                        </div>

                        <!-- Detail -->
                        <div class="col-md-6">
                            <h5 class="fw-bold mb-2" id="nilai-wilayah"><?= e($gempa_terbaru['Wilayah']) ?></h5>
                            <table class="table table-sm table-borderless mb-0" style="font-size:.88rem">
                                <tr>
                                    <td class="text-muted pe-3" style="width:130px">🕐 Waktu</td>
                                    <td>
                                        <strong><?= e($gempa_terbaru['Tanggal'] ?? '') ?> <?= e($gempa_terbaru['Jam'] ?? '') ?></strong>
                                        &nbsp;—&nbsp;
                                        <span id="waktu-relatif" class="text-muted">
                                            <?= e(waktuRelatif($gempa_terbaru['DateTime'] ?? date('Y-m-d H:i:s'))) ?>
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-muted">📏 Kedalaman</td>
                                    <td><?= e($gempa_terbaru['Kedalaman']) ?></td>
                                </tr>
                                <tr>
                                    <td class="text-muted">📍 Koordinat</td>
                                    <td><?= e($gempa_terbaru['Lintang']) ?>° LS / <?= e($gempa_terbaru['Bujur']) ?>° BT</td>
                                </tr>
                                <?php if (!empty($gempa_terbaru['Potensi'])): ?>
                                <tr>
                                    <td class="text-muted">⚡ Potensi</td>
                                    <td class="<?= stripos($gempa_terbaru['Potensi'], 'tsunami') !== false && stripos($gempa_terbaru['Potensi'], 'tidak') === false ? 'text-danger fw-bold' : 'text-success' ?>">
                                        <?= e($gempa_terbaru['Potensi']) ?>
                                    </td>
                                </tr>
                                <?php endif; ?>
                                <?php if (!empty($gempa_terbaru['Dirasakan'])): ?>
                                <tr>
                                    <td class="text-muted">🏠 Dirasakan</td>
                                    <td><?= e($gempa_terbaru['Dirasakan']) ?></td>
                                </tr>
                                <?php endif; ?>
                            </table>
                        </div>

                        <!-- Shakemap -->
                        <div class="col-md-4 text-center">
                            <?php
                            $shakemap = $gempa_terbaru['Shakemap'] ?? '';
                            $shakemap_url = $shakemap
                                ? 'https://data.bmkg.go.id/DataMKG/TEWS/' . $shakemap
                                : BASE_URL . '/assets/img/placeholder.svg';
                            ?>
                            <img src="<?= e($shakemap_url) ?>"
                                 alt="Peta Guncangan <?= e($gempa_terbaru['Wilayah']) ?>"
                                 class="img-fluid rounded"
                                 style="max-height:180px; object-fit:contain"
                                 onerror="this.src='<?= BASE_URL ?>/assets/img/placeholder.svg'">
                            <div class="text-muted small mt-1">Peta Guncangan (Shakemap) BMKG</div>
                        </div>

                    </div>
                </div>
                <?php else: ?>
                <div class="card-body text-center text-muted py-4">
                    Tidak ada data gempa tersedia saat ini.
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ROW: STATUS SUSULAN + PETA -->
    <div class="row g-4 mb-4">

        <!-- Kartu Status Gempa Susulan -->
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-header">🔄 STATUS GEMPA SUSULAN</div>
                <div class="card-body">
                    <?php if ($mainshock): ?>
                    <p class="small text-muted mb-2">
                        <strong>Gempa Utama (M≥5.0):</strong><br>
                        M <?= e(number_format((float)$mainshock['magnitudo'], 1)) ?> —
                        <?= e($mainshock['wilayah']) ?><br>
                        <span class="text-muted"><?= e($mainshock['datetime']) ?></span>
                    </p>
                    <hr class="my-2">
                    <?php if (!empty($gempa_susulan)): ?>
                        <div class="alert alert-warning py-2 px-3 small mb-2">
                            <strong>⚠️ <?= count($gempa_susulan) ?> gempa susulan terdeteksi</strong>
                        </div>
                        <ul class="list-unstyled small">
                            <?php foreach (array_slice($gempa_susulan, 0, 5) as $s): ?>
                            <li class="mb-1 border-bottom pb-1">
                                M<?= e(number_format((float)$s['magnitudo'], 1)) ?>
                                — <?= e(substr($s['wilayah'], 0, 40)) ?>...
                                <br><span class="text-muted"><?= e(waktuRelatif($s['datetime'])) ?> · <?= e($s['jarak_km']) ?> km</span>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <div class="alert alert-success py-2 px-3 small">
                            ✅ Belum terdeteksi gempa susulan dalam radius 100 km dan 72 jam terakhir.
                        </div>
                    <?php endif; ?>
                    <?php else: ?>
                    <p class="text-muted small">Tidak ada gempa M≥5.0 dalam 7 hari terakhir.</p>
                    <?php endif; ?>
                    <p class="text-muted" style="font-size:.75rem;margin-top:.5rem">
                        * Estimasi berdasarkan data BMKG. Bukan prediksi resmi BMKG.
                    </p>
                </div>
            </div>
        </div>

        <!-- Peta Leaflet -->
        <div class="col-md-8">
            <div class="card h-100">
                <div class="card-header">🗺️ PETA GEMPA TERKINI</div>
                <div class="card-body p-2">
                    <div id="peta-gempa"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- TABEL M5.0+ dan DIRASAKAN -->
    <div class="row g-4 mb-4">

        <!-- 5 Gempa M5.0+ Terkini -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">📋 5 Gempa Terkini (M 5.0+)</div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Magnitudo</th>
                                    <th>Waktu</th>
                                    <th>Wilayah</th>
                                    <th>Kedalaman</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (array_slice($daftar_terkini, 0, 5) as $g): ?>
                                <tr>
                                    <td>
                                        <span class="badge-mag mag-<?= (float)($g['Magnitude']??0) >= 6 ? 'berbahaya' : ((float)($g['Magnitude']??0) >= 5 ? 'kuat' : 'sedang') ?>">
                                            M <?= e(number_format((float)($g['Magnitude']??0), 1)) ?>
                                        </span>
                                    </td>
                                    <td class="small"><?= e($g['Tanggal']??'') ?><br><?= e($g['Jam']??'') ?></td>
                                    <td class="small"><?= e(substr($g['Wilayah']??'', 0, 45)) ?></td>
                                    <td class="small"><?= e($g['Kedalaman']??'') ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($daftar_terkini)): ?>
                                <tr><td colspan="4" class="text-center text-muted">Tidak ada data.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- 5 Gempa Dirasakan -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">🏠 5 Gempa Dirasakan</div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Magnitudo</th>
                                    <th>Waktu</th>
                                    <th>Wilayah</th>
                                    <th>Dirasakan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (array_slice($daftar_dirasakan, 0, 5) as $g): ?>
                                <tr>
                                    <td>
                                        <span class="badge-mag mag-<?= (float)($g['Magnitude']??0) >= 6 ? 'berbahaya' : ((float)($g['Magnitude']??0) >= 5 ? 'kuat' : 'sedang') ?>">
                                            M <?= e(number_format((float)($g['Magnitude']??0), 1)) ?>
                                        </span>
                                    </td>
                                    <td class="small"><?= e($g['Tanggal']??'') ?><br><?= e($g['Jam']??'') ?></td>
                                    <td class="small"><?= e(substr($g['Wilayah']??'', 0, 35)) ?></td>
                                    <td class="small text-muted"><?= e(substr($g['Dirasakan']??'', 0, 50)) ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($daftar_dirasakan)): ?>
                                <tr><td colspan="4" class="text-center text-muted">Tidak ada data.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- KARTU TINDAKAN CEPAT -->
    <div class="row g-4 mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">🚨 TINDAKAN CEPAT SAAT GEMPA</div>
                <div class="card-body">
                    <div class="row g-3">

                        <!-- DROP COVER HOLD ON -->
                        <div class="col-md-4">
                            <div class="text-center p-3 rounded" style="background:#fff8e1; border:1px solid #f59e0b">
                                <div style="font-size:2.5rem">🫲</div>
                                <h5 class="fw-bold text-warning mt-2">1. DROP</h5>
                                <p class="small mb-0">Jatuhkan diri ke lantai, tangan melindungi kepala dan leher.</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-center p-3 rounded" style="background:#e8f0f9; border:1px solid #2563a8">
                                <div style="font-size:2.5rem">🛡️</div>
                                <h5 class="fw-bold text-primary mt-2">2. COVER</h5>
                                <p class="small mb-0">Berlindung di bawah meja kokoh atau di sudut dinding dalam. Jauhi jendela.</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-center p-3 rounded" style="background:#f0fdf4; border:1px solid #16a34a">
                                <div style="font-size:2.5rem">✋</div>
                                <h5 class="fw-bold text-success mt-2">3. HOLD ON</h5>
                                <p class="small mb-0">Pegang erat perlindungan Anda hingga guncangan berhenti.</p>
                            </div>
                        </div>

                        <!-- Nomor darurat -->
                        <div class="col-md-6">
                            <h6 class="section-title">📞 Nomor Darurat</h6>
                            <ul class="list-group list-group-flush small">
                                <li class="list-group-item d-flex justify-content-between px-0">
                                    <span>🆘 Nomor Darurat Nasional</span>
                                    <strong>112</strong>
                                </li>
                                <li class="list-group-item d-flex justify-content-between px-0">
                                    <span>🏛️ BNPB (Badan Nasional Penanggulangan Bencana)</span>
                                    <strong>021-500-113</strong>
                                </li>
                                <li class="list-group-item d-flex justify-content-between px-0">
                                    <span>🌐 BMKG (Info Gempa & Cuaca)</span>
                                    <strong>021-650-2468</strong>
                                </li>
                                <li class="list-group-item d-flex justify-content-between px-0">
                                    <span>🏥 Ambulans</span>
                                    <strong>119</strong>
                                </li>
                            </ul>
                        </div>

                        <!-- Tautan cepat -->
                        <div class="col-md-6">
                            <h6 class="section-title">🔗 Informasi Lebih Lanjut</h6>
                            <div class="d-flex flex-column gap-2">
                                <a href="<?= BASE_URL ?>/edukasi.php" class="btn btn-outline-primary btn-sm">
                                    📚 Pelajari cara penanggulangan gempa
                                </a>
                                <a href="<?= BASE_URL ?>/orang-hilang.php" class="btn btn-outline-warning btn-sm">
                                    🔍 Cari orang hilang pascagempa
                                </a>
                                <a href="<?= BASE_URL ?>/lapor-orang-hilang.php" class="btn btn-outline-danger btn-sm">
                                    📋 Laporkan orang hilang
                                </a>
                                <a href="<?= BASE_URL ?>/monitoring.php" class="btn btn-outline-secondary btn-sm">
                                    📊 Lihat riwayat gempa lengkap
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div><!-- /container -->

<!-- ============================================================ -->
<!-- SKRIP KHUSUS HALAMAN BERANDA                                  -->
<!-- ============================================================ -->
<?php
// Siapkan data peta sebagai JSON agar bisa dipakai Leaflet
$peta_json = json_encode($peta_data, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
$skrip_tambahan = "<script>
// Inisialisasi peta dengan data gempa
document.addEventListener('DOMContentLoaded', function() {
    var dataGempa = $peta_json;
    inisialisasiPeta('peta-gempa', dataGempa, -2.5, 118, 5);
    // Tampilkan waktu update awal
    var elWaktu = document.getElementById('waktu-update');
    if (elWaktu) {
        var now = new Date();
        elWaktu.textContent = 'Terakhir diperbarui: ' + now.toLocaleTimeString('id-ID') + ' WIB';
    }
});
</script>";
require_once __DIR__ . '/includes/footer.php';
?>
