<?php
/**
 * orang-hilang.php — Daftar orang hilang pascagempa
 * Tampilan kartu dengan pencarian dan filter status
 */

require_once __DIR__ . '/config/koneksi.php';
require_once __DIR__ . '/includes/fungsi.php';

// ============================================================
// FILTER & PENCARIAN
// ============================================================
$cari      = trim($_GET['cari']   ?? '');
$status    = $_GET['status'] ?? '';   // 'dicari' | 'ditemukan' | ''

$where  = ['1=1'];
$params = [];
$types  = '';

if ($cari) {
    $where[]  = '(nama LIKE ? OR lokasi_terakhir LIKE ?)';
    $like     = '%' . $cari . '%';
    $params[] = $like;
    $params[] = $like;
    $types   .= 'ss';
}
if (in_array($status, ['dicari', 'ditemukan'])) {
    $where[]  = 'status = ?';
    $params[] = $status;
    $types   .= 's';
}

$where_sql = implode(' AND ', $where);

// Ambil semua data (tanpa paginasi — umumnya sedikit)
$stmt = $koneksi->prepare("SELECT * FROM orang_hilang WHERE $where_sql ORDER BY status ASC, created_at DESC");
if ($types) $stmt->bind_param($types, ...$params);
$stmt->execute();
$daftar = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Hitung per status
$total_dicari    = 0;
$total_ditemukan = 0;
foreach ($daftar as $d) {
    if ($d['status'] === 'dicari')    $total_dicari++;
    if ($d['status'] === 'ditemukan') $total_ditemukan++;
}

$judul_halaman = 'Orang Hilang';
$deskripsi     = 'Daftar laporan orang hilang pascagempa bumi — bantu menemukan mereka';
require_once __DIR__ . '/includes/header.php';
?>

<script>var BASE_URL = '<?= BASE_URL ?>';</script>

<div class="container py-4">
    <!-- Judul + tombol lapor -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h1 class="h3 fw-bold text-primary-dark mb-1">🔍 Orang Hilang</h1>
            <p class="text-muted mb-0">Informasi orang hilang yang dilaporkan pascagempa bumi</p>
        </div>
        <a href="<?= BASE_URL ?>/lapor-orang-hilang.php" class="btn btn-danger">
            ➕ Laporkan Orang Hilang
        </a>
    </div>

    <!-- Kartu statistik -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card text-center p-3 border-danger">
                <div style="font-size:2rem; color:#dc2626"><?= $total_dicari ?></div>
                <div class="small text-muted">Masih Dicari</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card text-center p-3 border-success">
                <div style="font-size:2rem; color:#16a34a"><?= $total_ditemukan ?></div>
                <div class="small text-muted">Sudah Ditemukan</div>
            </div>
        </div>
    </div>

    <!-- Form pencarian & filter -->
    <div class="card mb-4">
        <div class="card-body py-3">
            <form method="get" class="row g-2 align-items-end">
                <div class="col-sm-6 col-md-5">
                    <label class="form-label small mb-1">Cari nama atau lokasi terakhir</label>
                    <input type="text" name="cari" class="form-control form-control-sm"
                           placeholder="Nama atau lokasi..." value="<?= e($cari) ?>">
                </div>
                <div class="col-sm-4 col-md-3">
                    <label class="form-label small mb-1">Filter Status</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">Semua Status</option>
                        <option value="dicari"    <?= $status === 'dicari'    ? 'selected' : '' ?>>Masih Dicari</option>
                        <option value="ditemukan" <?= $status === 'ditemukan' ? 'selected' : '' ?>>Sudah Ditemukan</option>
                    </select>
                </div>
                <div class="col-sm-2 col-md-2 d-flex gap-1">
                    <button type="submit" class="btn btn-primary btn-sm flex-fill">Cari</button>
                    <a href="orang-hilang.php" class="btn btn-outline-secondary btn-sm">✕</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Daftar kartu orang hilang -->
    <?php if (!empty($daftar)): ?>
    <div class="row g-3">
        <?php foreach ($daftar as $orang): ?>
        <div class="col-sm-6 col-md-4 col-lg-3">
            <div class="card kartu-orang h-100">
                <!-- Foto -->
                <?php
                $foto_path = !empty($orang['foto'])
                    ? BASE_URL . '/uploads/orang_hilang/' . $orang['foto']
                    : BASE_URL . '/assets/img/no-photo.svg';
                ?>
                <img src="<?= e($foto_path) ?>"
                     alt="Foto <?= e($orang['nama']) ?>"
                     class="foto-orang"
                     onerror="this.src='<?= BASE_URL ?>/assets/img/no-photo.svg'">

                <!-- Badge status -->
                <div class="position-absolute top-0 end-0 m-2">
                    <span class="badge badge-status-<?= e($orang['status']) ?> text-white">
                        <?= $orang['status'] === 'dicari' ? '🔍 Dicari' : '✅ Ditemukan' ?>
                    </span>
                </div>

                <div class="card-body p-3">
                    <h6 class="fw-bold mb-1"><?= e($orang['nama']) ?></h6>
                    <div class="small text-muted">
                        <div>👤 <?= e($orang['jenis_kelamin']) ?>, <?= e($orang['usia']) ?> tahun</div>
                        <div>📍 <?= e(substr($orang['lokasi_terakhir'] ?? '', 0, 50)) ?></div>
                        <div>📅 Hilang: <?= $orang['tanggal_hilang'] ? date('d M Y', strtotime($orang['tanggal_hilang'])) : '–' ?></div>
                    </div>
                </div>
                <div class="card-footer p-2 bg-transparent border-top">
                    <a href="<?= BASE_URL ?>/detail-orang-hilang.php?id=<?= $orang['id'] ?>"
                       class="btn btn-sm btn-outline-primary w-100">
                        Lihat Detail
                    </a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="text-center py-5">
        <div style="font-size:4rem">🔍</div>
        <h5 class="text-muted mt-2">Tidak ada laporan ditemukan</h5>
        <p class="text-muted">
            <?= $cari || $status ? 'Coba ubah filter pencarian.' : 'Belum ada laporan orang hilang.' ?>
        </p>
        <a href="<?= BASE_URL ?>/lapor-orang-hilang.php" class="btn btn-danger">Buat Laporan Baru</a>
    </div>
    <?php endif; ?>

</div><!-- /container -->

<?php require_once __DIR__ . '/includes/footer.php'; ?>
