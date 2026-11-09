<?php
/**
 * admin/gambar.php — Kelola gambar edukasi
 * Admin bisa: tambah gambar manual, hapus gambar, hapus laporan palsu
 */

session_start();
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../includes/fungsi.php';

// Cek autentikasi admin
if (empty($_SESSION['admin_id'])) {
    header('Location: ' . BASE_URL . '/admin/login.php');
    exit;
}

$pesan = '';

// ============================================================
// AKSI: Logout
// ============================================================
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: ' . BASE_URL . '/admin/login.php');
    exit;
}

// ============================================================
// AKSI: Tambah gambar manual
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_gambar'])) {
    $bagian      = $_POST['bagian']      ?? '';
    $url_gambar  = trim($_POST['url_gambar']  ?? '');
    $judul       = trim($_POST['judul']       ?? '');
    $sumber      = trim($_POST['sumber']      ?? '');
    $link_sumber = trim($_POST['link_sumber'] ?? '');
    $kata_kunci  = trim($_POST['kata_kunci']  ?? '');

    $bagian_valid = ['definisi','penyebab','dampak','penanggulangan','mitigasi'];

    if (!in_array($bagian, $bagian_valid)) {
        $pesan = 'error|Bagian tidak valid.';
    } elseif (!$url_gambar || strpos($url_gambar, 'https://') !== 0) {
        $pesan = 'error|URL gambar harus diawali https://.';
    } else {
        $stmt = $koneksi->prepare(
            "INSERT INTO gambar_edukasi (bagian, kata_kunci, url_gambar, thumbnail, judul, sumber, link_sumber)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        $thumb = $url_gambar; // Gunakan URL yang sama sebagai thumbnail
        $stmt->bind_param('sssssss', $bagian, $kata_kunci, $url_gambar, $thumb, $judul, $sumber, $link_sumber);
        if ($stmt->execute()) {
            $pesan = 'success|Gambar berhasil ditambahkan.';
        } else {
            $pesan = 'error|Gagal menyimpan gambar.';
        }
        $stmt->close();
    }
}

// ============================================================
// AKSI: Hapus gambar
// ============================================================
if (isset($_GET['hapus_gambar']) && is_numeric($_GET['hapus_gambar'])) {
    $id_hapus = (int)$_GET['hapus_gambar'];
    $stmt = $koneksi->prepare("DELETE FROM gambar_edukasi WHERE id = ?");
    $stmt->bind_param('i', $id_hapus);
    $stmt->execute();
    $stmt->close();
    $pesan = 'success|Gambar berhasil dihapus.';
}

// ============================================================
// AKSI: Hapus laporan orang hilang
// ============================================================
if (isset($_GET['hapus_laporan']) && is_numeric($_GET['hapus_laporan'])) {
    $id_hapus = (int)$_GET['hapus_laporan'];
    // Hapus foto jika ada
    $stmt = $koneksi->prepare("SELECT foto FROM orang_hilang WHERE id = ?");
    $stmt->bind_param('i', $id_hapus);
    $stmt->execute();
    $baris = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($baris && $baris['foto']) {
        $file = __DIR__ . '/../uploads/orang_hilang/' . $baris['foto'];
        if (file_exists($file)) unlink($file);
    }
    $stmt = $koneksi->prepare("DELETE FROM orang_hilang WHERE id = ?");
    $stmt->bind_param('i', $id_hapus);
    $stmt->execute();
    $stmt->close();
    $pesan = 'success|Laporan orang hilang berhasil dihapus.';
}

// ============================================================
// AMBIL DATA
// ============================================================
$gambar_semua   = $koneksi->query("SELECT * FROM gambar_edukasi ORDER BY bagian, id DESC")->fetch_all(MYSQLI_ASSOC);
$laporan_semua  = $koneksi->query("SELECT * FROM orang_hilang ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);

$judul_halaman = 'Panel Admin';
require_once __DIR__ . '/../includes/header.php';
?>

<script>var BASE_URL = '<?= BASE_URL ?>';</script>

<div class="container py-4">
    <!-- Header admin -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <h1 class="h3 fw-bold text-primary-dark mb-0">⚙️ Panel Admin</h1>
        <div class="d-flex gap-2">
            <span class="text-muted small">Login sebagai: <strong><?= e($_SESSION['admin_user']) ?></strong></span>
            <a href="?logout=1" class="btn btn-sm btn-outline-danger">Logout</a>
        </div>
    </div>

    <!-- Pesan -->
    <?php if ($pesan): [$tipe, $txt] = explode('|', $pesan, 2); ?>
    <div class="alert alert-<?= e($tipe) ?> alert-dismissible fade show py-2">
        <?= e($txt) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <!-- Tab navigasi -->
    <ul class="nav nav-tabs mb-4" id="tabAdmin">
        <li class="nav-item">
            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tabGambar">
                🖼️ Gambar Edukasi (<?= count($gambar_semua) ?>)
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabLaporan">
                📋 Laporan Orang Hilang (<?= count($laporan_semua) ?>)
            </button>
        </li>
    </ul>

    <div class="tab-content">

        <!-- ============================================================ -->
        <!-- TAB 1: GAMBAR EDUKASI                                         -->
        <!-- ============================================================ -->
        <div class="tab-pane fade show active" id="tabGambar">

            <!-- Form tambah gambar -->
            <div class="card mb-4">
                <div class="card-header fw-bold">➕ Tambah Gambar Manual</div>
                <div class="card-body">
                    <form method="post" class="row g-2">
                        <div class="col-md-3">
                            <label class="form-label small">Bagian *</label>
                            <select name="bagian" class="form-select form-select-sm" required>
                                <option value="">— Pilih Bagian —</option>
                                <?php foreach (['definisi','penyebab','dampak','penanggulangan','mitigasi'] as $b): ?>
                                <option value="<?= $b ?>"><?= ucfirst($b) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label small">URL Gambar (https://) *</label>
                            <input type="url" name="url_gambar" class="form-control form-control-sm"
                                   placeholder="https://..." required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small">Judul Gambar</label>
                            <input type="text" name="judul" class="form-control form-control-sm"
                                   placeholder="Deskripsi singkat gambar">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small">Sumber (website)</label>
                            <input type="text" name="sumber" class="form-control form-control-sm"
                                   placeholder="Contoh: wikimedia.org">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small">Link Halaman Asal</label>
                            <input type="url" name="link_sumber" class="form-control form-control-sm"
                                   placeholder="https://...">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small">Kata Kunci</label>
                            <input type="text" name="kata_kunci" class="form-control form-control-sm"
                                   placeholder="opsional">
                        </div>
                        <div class="col-12">
                            <button type="submit" name="tambah_gambar" class="btn btn-primary btn-sm">Simpan Gambar</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Tabel gambar -->
            <div class="card">
                <div class="card-header">📋 Daftar Gambar (<?= count($gambar_semua) ?>)</div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Preview</th>
                                    <th>Bagian</th>
                                    <th>Judul</th>
                                    <th>Sumber</th>
                                    <th>Ditambahkan</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($gambar_semua as $g): ?>
                                <tr>
                                    <td>
                                        <img src="<?= e($g['thumbnail'] ?: $g['url_gambar']) ?>"
                                             alt="preview"
                                             style="width:60px;height:45px;object-fit:cover;border-radius:4px"
                                             onerror="this.style.display='none'">
                                    </td>
                                    <td><span class="badge bg-secondary"><?= e($g['bagian']) ?></span></td>
                                    <td class="small"><?= e(substr($g['judul'] ?? '', 0, 50)) ?></td>
                                    <td class="small text-muted">
                                        <?php if ($g['link_sumber']): ?>
                                        <a href="<?= e($g['link_sumber']) ?>" target="_blank" rel="noopener noreferrer">
                                            <?= e(substr($g['sumber'] ?? '', 0, 30)) ?>
                                        </a>
                                        <?php else: ?>
                                        <?= e(substr($g['sumber'] ?? '', 0, 30)) ?>
                                        <?php endif; ?>
                                    </td>
                                    <td class="small text-muted"><?= date('d/m/Y', strtotime($g['created_at'])) ?></td>
                                    <td>
                                        <a href="?hapus_gambar=<?= $g['id'] ?>"
                                           class="btn btn-sm btn-outline-danger btn-konfirmasi"
                                           data-pesan="Yakin hapus gambar ini?">Hapus</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($gambar_semua)): ?>
                                <tr><td colspan="6" class="text-center text-muted py-3">Belum ada gambar.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- ============================================================ -->
        <!-- TAB 2: LAPORAN ORANG HILANG                                   -->
        <!-- ============================================================ -->
        <div class="tab-pane fade" id="tabLaporan">
            <div class="card">
                <div class="card-header">📋 Laporan Orang Hilang (<?= count($laporan_semua) ?>)</div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nama</th>
                                    <th>Usia/JK</th>
                                    <th>Lokasi</th>
                                    <th>Pelapor</th>
                                    <th>Status</th>
                                    <th>Dilaporkan</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($laporan_semua as $l): ?>
                                <tr>
                                    <td class="text-muted"><?= $l['id'] ?></td>
                                    <td>
                                        <a href="<?= BASE_URL ?>/detail-orang-hilang.php?id=<?= $l['id'] ?>"
                                           target="_blank"><?= e($l['nama']) ?></a>
                                    </td>
                                    <td class="small"><?= e($l['usia']) ?> / <?= e(substr($l['jenis_kelamin'], 0, 1)) ?></td>
                                    <td class="small"><?= e(substr($l['lokasi_terakhir'] ?? '', 0, 40)) ?></td>
                                    <td class="small"><?= e($l['nama_pelapor']) ?><br>
                                        <span class="text-muted"><?= e($l['kontak_pelapor']) ?></span></td>
                                    <td>
                                        <span class="badge badge-status-<?= e($l['status']) ?> text-white">
                                            <?= $l['status'] === 'dicari' ? '🔍 Dicari' : '✅ Ditemukan' ?>
                                        </span>
                                    </td>
                                    <td class="small text-muted"><?= date('d/m/Y', strtotime($l['created_at'])) ?></td>
                                    <td>
                                        <a href="?hapus_laporan=<?= $l['id'] ?>"
                                           class="btn btn-sm btn-outline-danger btn-konfirmasi"
                                           data-pesan="Yakin hapus laporan ini? Data tidak bisa dikembalikan.">Hapus</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($laporan_semua)): ?>
                                <tr><td colspan="8" class="text-center text-muted py-3">Belum ada laporan.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div><!-- /tab-content -->
</div><!-- /container -->

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
