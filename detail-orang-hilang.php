<?php
/**
 * detail-orang-hilang.php — Detail laporan orang hilang
 * + Tombol hubungi via WhatsApp + Tombol "Tandai Ditemukan"
 */

session_start();
require_once __DIR__ . '/config/koneksi.php';
require_once __DIR__ . '/includes/fungsi.php';

// Ambil ID dari URL
$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    header('Location: ' . BASE_URL . '/orang-hilang.php');
    exit;
}

// Ambil data orang
$stmt = $koneksi->prepare("SELECT * FROM orang_hilang WHERE id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$orang = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$orang) {
    header('Location: ' . BASE_URL . '/orang-hilang.php');
    exit;
}

$pesan_sukses = '';
$pesan_error  = '';

// ============================================================
// PROSES TANDAI DITEMUKAN
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tandai_ditemukan'])) {
    $kode_input = trim($_POST['kode_verifikasi'] ?? '');

    if (!$kode_input) {
        $pesan_error = 'Kode verifikasi wajib diisi.';
    } elseif (strtoupper($kode_input) !== strtoupper($orang['kode_verifikasi'])) {
        $pesan_error = 'Kode verifikasi tidak cocok. Periksa kembali kode yang diterima saat melapor.';
    } elseif ($orang['status'] === 'ditemukan') {
        $pesan_sukses = 'Status sudah ditandai sebagai Ditemukan sebelumnya.';
    } else {
        // Update status
        $stmt = $koneksi->prepare("UPDATE orang_hilang SET status = 'ditemukan' WHERE id = ?");
        $stmt->bind_param('i', $id);
        if ($stmt->execute()) {
            $orang['status'] = 'ditemukan'; // Perbarui data lokal
            $pesan_sukses = 'Status berhasil diperbarui menjadi DITEMUKAN. Terima kasih atas informasinya!';
        } else {
            $pesan_error = 'Gagal memperbarui status. Silakan coba lagi.';
        }
        $stmt->close();
    }
}

// Buat link WhatsApp
$no_wa = preg_replace('/[^0-9]/', '', $orang['kontak_pelapor'] ?? '');
if (substr($no_wa, 0, 1) === '0') {
    $no_wa = '62' . substr($no_wa, 1); // 0xxx → 62xxx
}
$pesan_wa = urlencode(
    "Halo, saya menemukan informasi orang yang Anda cari di SIMB GempaBumi.\n" .
    "Nama: " . $orang['nama'] . "\n" .
    "Terima kasih."
);
$link_wa = "https://wa.me/{$no_wa}?text={$pesan_wa}";

// Siapkan path foto
$foto_url = !empty($orang['foto'])
    ? BASE_URL . '/uploads/orang_hilang/' . $orang['foto']
    : BASE_URL . '/assets/img/no-photo.svg';

$judul_halaman = 'Detail: ' . $orang['nama'];
$deskripsi     = 'Informasi lengkap orang hilang: ' . $orang['nama'];
require_once __DIR__ . '/includes/header.php';
?>

<script>var BASE_URL = '<?= BASE_URL ?>';</script>

<div class="container py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/">Beranda</a></li>
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/orang-hilang.php">Orang Hilang</a></li>
            <li class="breadcrumb-item active"><?= e($orang['nama']) ?></li>
        </ol>
    </nav>

    <!-- Pesan -->
    <?php if ($pesan_sukses): ?>
    <div class="alert alert-success"><?= e($pesan_sukses) ?></div>
    <?php endif; ?>
    <?php if ($pesan_error): ?>
    <div class="alert alert-danger"><?= e($pesan_error) ?></div>
    <?php endif; ?>

    <div class="row g-4">

        <!-- Foto + aksi -->
        <div class="col-md-4">
            <div class="card">
                <img src="<?= e($foto_url) ?>"
                     alt="Foto <?= e($orang['nama']) ?>"
                     class="card-img-top"
                     style="max-height:320px; object-fit:contain; background:#f8fafc"
                     onerror="this.src='<?= BASE_URL ?>/assets/img/no-photo.svg'">
                <div class="card-body text-center">

                    <!-- Badge status -->
                    <span class="badge badge-status-<?= e($orang['status']) ?> text-white px-3 py-2 mb-3 d-inline-block">
                        <?= $orang['status'] === 'dicari' ? '🔍 Masih Dicari' : '✅ Sudah Ditemukan' ?>
                    </span>

                    <!-- Tombol WhatsApp -->
                    <?php if ($orang['status'] === 'dicari'): ?>
                    <a href="<?= $link_wa ?>" target="_blank" rel="noopener noreferrer"
                       class="btn btn-success w-100 mb-2">
                        💬 Hubungi Pelapor via WhatsApp
                    </a>
                    <?php endif; ?>

                    <a href="<?= BASE_URL ?>/orang-hilang.php" class="btn btn-outline-secondary w-100 btn-sm">
                        ← Kembali ke Daftar
                    </a>
                </div>
            </div>

            <!-- Form Tandai Ditemukan -->
            <?php if ($orang['status'] === 'dicari'): ?>
            <div class="card mt-3">
                <div class="card-header small fw-bold">✅ Tandai Sudah Ditemukan</div>
                <div class="card-body">
                    <p class="small text-muted mb-2">
                        Jika Anda adalah pelapor dan orang ini sudah ditemukan, masukkan kode verifikasi yang Anda terima saat melapor.
                    </p>
                    <form method="post">
                        <div class="mb-2">
                            <input type="text" name="kode_verifikasi"
                                   class="form-control form-control-sm text-uppercase"
                                   placeholder="Kode Verifikasi (mis. ABCD1234)"
                                   maxlength="10" style="letter-spacing:.1em"
                                   required>
                        </div>
                        <button type="submit" name="tandai_ditemukan"
                                class="btn btn-success btn-sm w-100">
                            Konfirmasi Ditemukan
                        </button>
                    </form>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Detail informasi -->
        <div class="col-md-8">
            <div class="card h-100">
                <div class="card-header">
                    ℹ️ Informasi Lengkap
                </div>
                <div class="card-body">
                    <h4 class="fw-bold text-primary-dark mb-3"><?= e($orang['nama']) ?></h4>

                    <div class="row g-3">
                        <div class="col-sm-6">
                            <div class="small text-muted">👤 Usia</div>
                            <div><?= e($orang['usia']) ?> Tahun</div>
                        </div>
                        <div class="col-sm-6">
                            <div class="small text-muted">⚥ Jenis Kelamin</div>
                            <div><?= e($orang['jenis_kelamin']) ?></div>
                        </div>
                        <div class="col-12">
                            <div class="small text-muted">🔍 Ciri-ciri Fisik</div>
                            <div class="p-2 bg-light rounded"><?= nl2br(e($orang['ciri_ciri'])) ?></div>
                        </div>
                        <div class="col-sm-8">
                            <div class="small text-muted">📍 Lokasi Terakhir Terlihat</div>
                            <div><?= e($orang['lokasi_terakhir']) ?></div>
                        </div>
                        <div class="col-sm-4">
                            <div class="small text-muted">📅 Tanggal Hilang</div>
                            <div><?= $orang['tanggal_hilang'] ? date('d M Y', strtotime($orang['tanggal_hilang'])) : '–' ?></div>
                        </div>
                        <div class="col-12">
                            <div class="small text-muted">🏠 Domisili / Alamat Asal</div>
                            <div><?= e($orang['domisili']) ?></div>
                        </div>
                    </div>

                    <hr class="my-3">
                    <h6 class="fw-bold mb-2">📞 Kontak Pelapor</h6>
                    <div class="row g-2">
                        <div class="col-sm-6">
                            <div class="small text-muted">Nama Pelapor</div>
                            <div><?= e($orang['nama_pelapor']) ?></div>
                        </div>
                        <div class="col-sm-6">
                            <div class="small text-muted">Hubungan</div>
                            <div><?= e($orang['hubungan']) ?></div>
                        </div>
                        <div class="col-sm-6">
                            <div class="small text-muted">No. HP / WhatsApp</div>
                            <div>
                                <a href="<?= $link_wa ?>" target="_blank" rel="noopener noreferrer" class="text-success">
                                    <?= e($orang['kontak_pelapor']) ?> 💬
                                </a>
                            </div>
                        </div>
                    </div>

                    <hr class="my-3">
                    <div class="small text-muted">
                        Dilaporkan pada: <?= date('d M Y H:i', strtotime($orang['created_at'])) ?> WIB
                    </div>
                </div>
            </div>
        </div>

    </div>

</div><!-- /container -->

<?php require_once __DIR__ . '/includes/footer.php'; ?>
