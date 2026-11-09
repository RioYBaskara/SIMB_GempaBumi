<?php
/**
 * monitoring.php — Halaman Monitoring & Riwayat Gempa
 * Tabel paginasi, filter, dan grafik Chart.js
 */

require_once __DIR__ . '/config/koneksi.php';
require_once __DIR__ . '/includes/fungsi.php';

// ============================================================
// PARAMETER FILTER & PAGINASI
// ============================================================
$hal     = max(1, (int)($_GET['hal']       ?? 1));
$per_hal = 20;
$offset  = ($hal - 1) * $per_hal;

$mag_min    = isset($_GET['mag_min'])    ? max(0, (float)$_GET['mag_min'])    : 0;
$tgl_dari   = isset($_GET['tgl_dari'])   ? $_GET['tgl_dari']   : '';
$tgl_sampai = isset($_GET['tgl_sampai']) ? $_GET['tgl_sampai'] : '';
$cari_wil   = isset($_GET['wilayah'])    ? trim($_GET['wilayah']) : '';

// Bangun WHERE clause secara aman
$where   = ['1=1'];
$params  = [];
$types   = '';

if ($mag_min > 0) {
    $where[]  = 'magnitudo >= ?';
    $params[] = $mag_min;
    $types   .= 'd';
}
if ($tgl_dari) {
    $where[]  = 'DATE(datetime) >= ?';
    $params[] = $tgl_dari;
    $types   .= 's';
}
if ($tgl_sampai) {
    $where[]  = 'DATE(datetime) <= ?';
    $params[] = $tgl_sampai;
    $types   .= 's';
}
if ($cari_wil) {
    $where[]  = 'wilayah LIKE ?';
    $params[] = '%' . $cari_wil . '%';
    $types   .= 's';
}

$where_sql = implode(' AND ', $where);

// Hitung total data
$sql_count = "SELECT COUNT(*) AS total FROM gempa WHERE $where_sql";
$stmt = $koneksi->prepare($sql_count);
if ($types) $stmt->bind_param($types, ...$params);
$stmt->execute();
$total = (int)$stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

$total_hal = max(1, ceil($total / $per_hal));
$hal = min($hal, $total_hal);

// Ambil data paginasi
$sql_data = "SELECT * FROM gempa WHERE $where_sql ORDER BY datetime DESC LIMIT ? OFFSET ?";
$stmt = $koneksi->prepare($sql_data);
$params_data  = $params;
$params_data[] = $per_hal;
$params_data[] = $offset;
$types_data    = $types . 'ii';
$stmt->bind_param($types_data, ...$params_data);
$stmt->execute();
$daftar_gempa = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// ============================================================
// TOMBOL PERBARUI DATA
// ============================================================
$pesan_perbarui = '';
if (isset($_POST['perbarui'])) {
    // Panggil semua endpoint BMKG untuk menyimpan data baru
    $urls = [
        'https://data.bmkg.go.id/DataMKG/TEWS/autogempa.json'      => 'autogempa',
        'https://data.bmkg.go.id/DataMKG/TEWS/gempaterkini.json'   => 'terkini',
        'https://data.bmkg.go.id/DataMKG/TEWS/gempadirasakan.json' => 'dirasakan',
    ];
    $berhasil = 0;
    foreach ($urls as $url => $sumber) {
        $raw = ambilDataCurl($url);
        if ($raw) {
            $json = json_decode($raw, true);
            $list = $json['Infogempa']['gempa'] ?? null;
            if ($list) {
                if (isset($list['Tanggal'])) $list = [$list]; // pastikan array
                foreach ($list as $g) simpanGempa($g, $sumber);
                $berhasil++;
            }
        }
    }
    if ($berhasil > 0) {
        $pesan_perbarui = "success|Data gempa berhasil diperbarui dari BMKG ({$berhasil} sumber).";
    } else {
        $pesan_perbarui = "warning|Tidak dapat menghubungi BMKG. Data tetap menggunakan yang tersimpan.";
    }
    // Bersihkan cache agar halaman ini pakai data baru
    $koneksi->query("DELETE FROM api_cache");
}

$judul_halaman = 'Monitoring Gempa';
$deskripsi     = 'Riwayat dan statistik data gempa bumi Indonesia dari BMKG';
require_once __DIR__ . '/includes/header.php';
?>

<script>var BASE_URL = '<?= BASE_URL ?>';</script>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <h1 class="h3 fw-bold text-primary-dark mb-0">📊 Monitoring Gempa Bumi</h1>

        <!-- Tombol perbarui -->
        <form method="post" class="mb-0">
            <button type="submit" name="perbarui" class="btn btn-primary btn-sm">
                🔄 Perbarui Data dari BMKG
            </button>
        </form>
    </div>

    <!-- Pesan setelah perbarui -->
    <?php if ($pesan_perbarui): [$tipe, $pesan] = explode('|', $pesan_perbarui, 2); ?>
    <div class="alert alert-<?= e($tipe) ?> alert-dismissible fade show py-2">
        <?= e($pesan) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <!-- ============================================================ -->
    <!-- KARTU RINGKASAN                                               -->
    <!-- ============================================================ -->
    <div class="row g-3 mb-4" id="kartu-ringkasan">
        <div class="col-6 col-md-3">
            <div class="card text-center p-3">
                <div style="font-size:2rem;color:#2563a8" id="stat-total">–</div>
                <div class="small text-muted">Total Tercatat</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card text-center p-3">
                <div style="font-size:2rem;color:#dc2626" id="stat-terbesar">–</div>
                <div class="small text-muted">Magnitudo Terbesar</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card text-center p-3">
                <div style="font-size:2rem;color:#d97706" id="stat-rata">–</div>
                <div class="small text-muted">Rata-rata Magnitudo</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card text-center p-3">
                <div style="font-size:2rem;color:#16a34a" id="stat-hari-ini">–</div>
                <div class="small text-muted">Gempa Hari Ini</div>
            </div>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- GRAFIK CHART.JS                                               -->
    <!-- ============================================================ -->
    <div class="row g-3 mb-4">
        <!-- Grafik 1: Jumlah per hari -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">📅 Jumlah Gempa per Hari (30 Hari Terakhir)</div>
                <div class="card-body">
                    <canvas id="grafikPerHari" height="120"></canvas>
                </div>
            </div>
        </div>
        <!-- Grafik 2: Distribusi magnitudo -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">📊 Distribusi Magnitudo</div>
                <div class="card-body d-flex align-items-center justify-content-center">
                    <canvas id="grafikDistribusi" style="max-height:200px"></canvas>
                </div>
            </div>
        </div>
        <!-- Grafik 3: Distribusi kedalaman -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">🌊 Kategori Kedalaman</div>
                <div class="card-body d-flex align-items-center justify-content-center">
                    <canvas id="grafikKedalaman" style="max-height:200px"></canvas>
                </div>
            </div>
        </div>
        <!-- Grafik 4: Tren magnitudo rata-rata -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">📈 Tren Magnitudo (30 Hari Terakhir)</div>
                <div class="card-body">
                    <canvas id="grafikTren" height="120"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- FORM FILTER                                                   -->
    <!-- ============================================================ -->
    <div class="card mb-3">
        <div class="card-body py-3">
            <form method="get" class="row g-2 align-items-end">
                <div class="col-sm-6 col-md-3">
                    <label class="form-label small mb-1">Dari Tanggal</label>
                    <input type="date" name="tgl_dari" class="form-control form-control-sm"
                           value="<?= e($tgl_dari) ?>">
                </div>
                <div class="col-sm-6 col-md-3">
                    <label class="form-label small mb-1">Sampai Tanggal</label>
                    <input type="date" name="tgl_sampai" class="form-control form-control-sm"
                           value="<?= e($tgl_sampai) ?>">
                </div>
                <div class="col-sm-6 col-md-2">
                    <label class="form-label small mb-1">Magnitudo Min.</label>
                    <input type="number" name="mag_min" class="form-control form-control-sm"
                           step="0.1" min="0" max="10" placeholder="mis. 5.0"
                           value="<?= e($mag_min ?: '') ?>">
                </div>
                <div class="col-sm-6 col-md-3">
                    <label class="form-label small mb-1">Cari Wilayah</label>
                    <input type="text" name="wilayah" class="form-control form-control-sm"
                           placeholder="mis. Jawa, Sulawesi..."
                           value="<?= e($cari_wil) ?>">
                </div>
                <div class="col-md-1 d-flex gap-1">
                    <button type="submit" class="btn btn-primary btn-sm flex-fill">Cari</button>
                    <a href="monitoring.php" class="btn btn-outline-secondary btn-sm">✕</a>
                </div>
            </form>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- TABEL RIWAYAT GEMPA                                           -->
    <!-- ============================================================ -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>📋 Riwayat Gempa</span>
            <small class="text-warning">
                <?= number_format($total) ?> data ditemukan
            </small>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Waktu (WIB)</th>
                            <th>Magnitudo</th>
                            <th>Kedalaman</th>
                            <th>Wilayah</th>
                            <th>Potensi</th>
                            <th>Dirasakan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($daftar_gempa as $no => $g): ?>
                        <tr>
                            <td class="text-muted small"><?= $offset + $no + 1 ?></td>
                            <td class="small">
                                <?= e($g['tanggal']) ?><br>
                                <span class="text-muted"><?= e($g['jam']) ?></span>
                            </td>
                            <td>
                                <span class="badge-mag mag-<?=
                                    $g['magnitudo'] >= 6 ? 'berbahaya' :
                                    ($g['magnitudo'] >= 5 ? 'kuat' :
                                    ($g['magnitudo'] >= 4 ? 'sedang' : 'rendah')) ?>">
                                    M <?= number_format((float)$g['magnitudo'], 1) ?>
                                </span>
                            </td>
                            <td class="small"><?= e($g['kedalaman_km']) ?> km</td>
                            <td class="small"><?= e($g['wilayah']) ?></td>
                            <td class="small">
                                <?php if (!empty($g['potensi'])): ?>
                                    <?php $tsunami = stripos($g['potensi'],'tsunami') !== false && stripos($g['potensi'],'tidak') === false; ?>
                                    <span class="<?= $tsunami ? 'text-danger fw-bold' : 'text-success' ?>">
                                        <?= $tsunami ? '🌊 ' : '✅ ' ?><?= e(substr($g['potensi'], 0, 30)) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted">–</span>
                                <?php endif; ?>
                            </td>
                            <td class="small text-muted"><?= e(substr($g['dirasakan'] ?? '', 0, 50)) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($daftar_gempa)): ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">
                                Tidak ada data gempa yang cocok dengan filter.
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Paginasi -->
        <?php if ($total_hal > 1): ?>
        <div class="card-footer">
            <nav>
                <ul class="pagination pagination-sm mb-0 justify-content-center flex-wrap">
                    <!-- Sebelumnya -->
                    <li class="page-item <?= $hal <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['hal' => $hal-1])) ?>">‹</a>
                    </li>
                    <?php
                    // Tampilkan maks 7 halaman
                    $mulai = max(1, $hal - 3);
                    $akhir = min($total_hal, $hal + 3);
                    for ($p = $mulai; $p <= $akhir; $p++):
                    ?>
                    <li class="page-item <?= $p == $hal ? 'active' : '' ?>">
                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['hal' => $p])) ?>"><?= $p ?></a>
                    </li>
                    <?php endfor; ?>
                    <!-- Berikutnya -->
                    <li class="page-item <?= $hal >= $total_hal ? 'disabled' : '' ?>">
                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['hal' => $hal+1])) ?>">›</a>
                    </li>
                </ul>
            </nav>
        </div>
        <?php endif; ?>
    </div>

</div><!-- /container -->

<?php
$skrip_tambahan = '<script>
// Muat statistik dan render semua grafik Chart.js
fetch(BASE_URL + "/api/statistik.php")
    .then(r => r.json())
    .then(function(d) {

        // Perbarui kartu ringkasan
        document.getElementById("stat-total").textContent    = d.ringkasan.total.toLocaleString("id-ID");
        document.getElementById("stat-terbesar").textContent = "M " + d.ringkasan.terbesar.toFixed(1);
        document.getElementById("stat-rata").textContent     = "M " + d.ringkasan.rata_rata.toFixed(2);
        document.getElementById("stat-hari-ini").textContent = d.ringkasan.hari_ini;

        // Grafik 1: Jumlah per hari (Line)
        new Chart(document.getElementById("grafikPerHari"), {
            type: "bar",
            data: {
                labels: d.per_hari.label,
                datasets: [{
                    label: "Jumlah Gempa",
                    data: d.per_hari.data,
                    backgroundColor: "rgba(37,99,168,0.7)",
                    borderColor: "#2563a8",
                    borderWidth: 1,
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, ticks: { precision: 0 } }
                }
            }
        });

        // Grafik 2: Distribusi magnitudo (Bar)
        new Chart(document.getElementById("grafikDistribusi"), {
            type: "bar",
            data: {
                labels: d.distribusi_mag.label,
                datasets: [{
                    label: "Jumlah",
                    data: d.distribusi_mag.data,
                    backgroundColor: d.distribusi_mag.warna,
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
            }
        });

        // Grafik 3: Distribusi kedalaman (Doughnut)
        new Chart(document.getElementById("grafikKedalaman"), {
            type: "doughnut",
            data: {
                labels: d.kedalaman.label,
                datasets: [{
                    data: d.kedalaman.data,
                    backgroundColor: d.kedalaman.warna,
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: "bottom", labels: { font: { size: 11 } } }
                }
            }
        });

        // Grafik 4: Tren magnitudo (Line)
        new Chart(document.getElementById("grafikTren"), {
            type: "line",
            data: {
                labels: d.tren.label,
                datasets: [
                    {
                        label: "Rata-rata M",
                        data: d.tren.rata,
                        borderColor: "#2563a8",
                        backgroundColor: "rgba(37,99,168,0.1)",
                        fill: true,
                        tension: 0.3,
                        spanGaps: true,
                    },
                    {
                        label: "Maks M",
                        data: d.tren.maks,
                        borderColor: "#dc2626",
                        borderDash: [5,3],
                        fill: false,
                        tension: 0.3,
                        spanGaps: true,
                    }
                ]
            },
            options: {
                responsive: true,
                plugins: { legend: { position: "top" } },
                scales: {
                    y: { beginAtZero: false, min: 2, max: 8 }
                }
            }
        });
    })
    .catch(function(err) {
        console.warn("Gagal memuat statistik:", err);
    });
</script>';
require_once __DIR__ . '/includes/footer.php';
?>
