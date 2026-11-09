<?php
/**
 * lapor-orang-hilang.php — Form laporan orang hilang
 * Validasi server-side + client-side, upload foto, kode verifikasi
 */

session_start();
require_once __DIR__ . '/config/koneksi.php';
require_once __DIR__ . '/includes/fungsi.php';

$errors  = [];
$sukses  = false;
$kode_verifikasi = '';
$data    = []; // Isi ulang form jika ada error

// ============================================================
// PROSES FORM POST
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Ambil dan bersihkan input
    $nama           = trim($_POST['nama']           ?? '');
    $usia           = (int)($_POST['usia']           ?? 0);
    $jenis_kelamin  = $_POST['jenis_kelamin']        ?? '';
    $ciri_ciri      = trim($_POST['ciri_ciri']       ?? '');
    $lokasi_terakhir= trim($_POST['lokasi_terakhir'] ?? '');
    $tanggal_hilang = $_POST['tanggal_hilang']       ?? '';
    $domisili       = trim($_POST['domisili']        ?? '');
    $nama_pelapor   = trim($_POST['nama_pelapor']    ?? '');
    $hubungan       = trim($_POST['hubungan']        ?? '');
    $kontak_pelapor = trim($_POST['kontak_pelapor']  ?? '');

    // Simpan data untuk isi ulang form jika ada error
    $data = compact('nama','usia','jenis_kelamin','ciri_ciri','lokasi_terakhir',
                    'tanggal_hilang','domisili','nama_pelapor','hubungan','kontak_pelapor');

    // --- Validasi ---
    if (!$nama)           $errors['nama']           = 'Nama wajib diisi.';
    if ($usia < 1 || $usia > 120) $errors['usia']  = 'Usia tidak valid.';
    if (!in_array($jenis_kelamin, ['Laki-laki', 'Perempuan']))
                          $errors['jenis_kelamin']   = 'Jenis kelamin wajib dipilih.';
    if (!$ciri_ciri)      $errors['ciri_ciri']      = 'Ciri-ciri fisik wajib diisi.';
    if (!$lokasi_terakhir)$errors['lokasi_terakhir']= 'Lokasi terakhir wajib diisi.';
    if (!$tanggal_hilang) $errors['tanggal_hilang'] = 'Tanggal hilang wajib diisi.';
    if (!$domisili)       $errors['domisili']       = 'Domisili wajib diisi.';
    if (!$nama_pelapor)   $errors['nama_pelapor']   = 'Nama pelapor wajib diisi.';
    if (!$hubungan)       $errors['hubungan']       = 'Hubungan dengan korban wajib diisi.';
    if (!$kontak_pelapor) $errors['kontak_pelapor'] = 'Nomor HP/WhatsApp wajib diisi.';

    // Validasi nomor HP
    if ($kontak_pelapor && !preg_match('/^[0-9+\-\s]{8,20}$/', $kontak_pelapor)) {
        $errors['kontak_pelapor'] = 'Format nomor HP tidak valid.';
    }

    // --- Upload foto (opsional) ---
    $nama_foto = null;
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] !== UPLOAD_ERR_NO_FILE) {
        $folder_upload = __DIR__ . '/uploads/orang_hilang/';
        if (!is_dir($folder_upload)) mkdir($folder_upload, 0755, true);
        $hasil_upload = uploadGambar($_FILES['foto'], $folder_upload);
        if (!$hasil_upload['sukses']) {
            $errors['foto'] = $hasil_upload['pesan'];
        } else {
            $nama_foto = $hasil_upload['nama'];
        }
    }

    // --- Simpan ke database jika tidak ada error ---
    if (empty($errors)) {
        $kode_verifikasi = generateKodeVerifikasi(8);

        $stmt = $koneksi->prepare(
            "INSERT INTO orang_hilang
             (nama, usia, jenis_kelamin, foto, ciri_ciri, lokasi_terakhir, tanggal_hilang,
              domisili, nama_pelapor, hubungan, kontak_pelapor, status, kode_verifikasi)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'dicari', ?)"
        );
        $stmt->bind_param(
            'siissssssss',
            $nama, $usia, $jenis_kelamin, $nama_foto, $ciri_ciri,
            $lokasi_terakhir, $tanggal_hilang, $domisili,
            $nama_pelapor, $hubungan, $kontak_pelapor, $kode_verifikasi
        );
        if ($stmt->execute()) {
            $sukses = true;
            $data   = []; // Reset form
        } else {
            $errors['db'] = 'Terjadi kesalahan saat menyimpan data. Silakan coba lagi.';
        }
        $stmt->close();
    }
}

$judul_halaman = 'Lapor Orang Hilang';
$deskripsi     = 'Form pelaporan orang hilang pascagempa bumi';
require_once __DIR__ . '/includes/header.php';
?>

<script>var BASE_URL = '<?= BASE_URL ?>';</script>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">

            <div class="mb-4">
                <h1 class="h3 fw-bold text-primary-dark mb-1">📋 Lapor Orang Hilang</h1>
                <p class="text-muted">Isi formulir di bawah untuk melaporkan orang hilang. Data akan ditampilkan di halaman pencarian agar bisa membantu menemukan mereka.</p>
            </div>

            <!-- ============================================================ -->
            <!-- PESAN SUKSES                                                   -->
            <!-- ============================================================ -->
            <?php if ($sukses): ?>
            <div class="card border-success mb-4">
                <div class="card-body text-center py-4">
                    <div style="font-size:3rem">✅</div>
                    <h4 class="text-success mt-2">Laporan Berhasil Dikirim!</h4>
                    <p class="mb-3">Data orang hilang telah disimpan dan akan segera ditampilkan di halaman pencarian.</p>
                    <div class="alert alert-warning d-inline-block px-4">
                        <strong>🔑 Kode Verifikasi Anda:</strong>
                        <div style="font-size:1.5rem; letter-spacing:0.2em; font-weight:700"><?= e($kode_verifikasi) ?></div>
                        <small class="text-muted d-block mt-1">
                            Simpan kode ini! Digunakan untuk menandai orang ditemukan<br>
                            atau memperbarui informasi laporan.
                        </small>
                    </div>
                    <div class="mt-3 d-flex gap-2 justify-content-center">
                        <a href="<?= BASE_URL ?>/orang-hilang.php" class="btn btn-primary">Lihat Daftar Orang Hilang</a>
                        <a href="<?= BASE_URL ?>/lapor-orang-hilang.php" class="btn btn-outline-secondary">Buat Laporan Lain</a>
                    </div>
                </div>
            </div>

            <?php else: ?>

            <!-- ============================================================ -->
            <!-- FORM                                                           -->
            <!-- ============================================================ -->
            <?php if (!empty($errors['db'])): ?>
            <div class="alert alert-danger"><?= e($errors['db']) ?></div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">📝 Data Orang Hilang</div>
                <div class="card-body">
                    <form method="post" enctype="multipart/form-data" novalidate id="formLaporan">

                        <!-- Nama -->
                        <div class="mb-3">
                            <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                            <input type="text" name="nama" class="form-control <?= isset($errors['nama']) ? 'is-invalid' : '' ?>"
                                   value="<?= e($data['nama'] ?? '') ?>" required>
                            <?php if (isset($errors['nama'])): ?>
                            <div class="invalid-feedback"><?= e($errors['nama']) ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="row g-3 mb-3">
                            <!-- Usia -->
                            <div class="col-sm-4">
                                <label class="form-label">Usia <span class="text-danger">*</span></label>
                                <input type="number" name="usia" class="form-control <?= isset($errors['usia']) ? 'is-invalid' : '' ?>"
                                       min="1" max="120" value="<?= e($data['usia'] ?? '') ?>" required>
                                <?php if (isset($errors['usia'])): ?>
                                <div class="invalid-feedback"><?= e($errors['usia']) ?></div>
                                <?php endif; ?>
                            </div>
                            <!-- Jenis Kelamin -->
                            <div class="col-sm-8">
                                <label class="form-label">Jenis Kelamin <span class="text-danger">*</span></label>
                                <select name="jenis_kelamin" class="form-select <?= isset($errors['jenis_kelamin']) ? 'is-invalid' : '' ?>" required>
                                    <option value="">— Pilih —</option>
                                    <option value="Laki-laki"  <?= ($data['jenis_kelamin'] ?? '') === 'Laki-laki'  ? 'selected' : '' ?>>Laki-laki</option>
                                    <option value="Perempuan"  <?= ($data['jenis_kelamin'] ?? '') === 'Perempuan'  ? 'selected' : '' ?>>Perempuan</option>
                                </select>
                                <?php if (isset($errors['jenis_kelamin'])): ?>
                                <div class="invalid-feedback"><?= e($errors['jenis_kelamin']) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Foto -->
                        <div class="mb-3">
                            <label class="form-label">Foto Terbaru (JPG/PNG, maks 2 MB)</label>
                            <input type="file" name="foto" class="form-control <?= isset($errors['foto']) ? 'is-invalid' : '' ?>"
                                   accept=".jpg,.jpeg,.png">
                            <?php if (isset($errors['foto'])): ?>
                            <div class="invalid-feedback"><?= e($errors['foto']) ?></div>
                            <?php endif; ?>
                            <div class="form-text">Upload foto terakhir orang yang dicari untuk memudahkan pengenalan.</div>
                        </div>

                        <!-- Ciri-ciri -->
                        <div class="mb-3">
                            <label class="form-label">Ciri-ciri Fisik <span class="text-danger">*</span></label>
                            <textarea name="ciri_ciri" rows="3"
                                      class="form-control <?= isset($errors['ciri_ciri']) ? 'is-invalid' : '' ?>"
                                      placeholder="Contoh: Tinggi 170 cm, kulit sawo matang, rambut pendek hitam, ada tato di lengan kanan, memakai kaos merah..."
                                      required><?= e($data['ciri_ciri'] ?? '') ?></textarea>
                            <?php if (isset($errors['ciri_ciri'])): ?>
                            <div class="invalid-feedback"><?= e($errors['ciri_ciri']) ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="row g-3 mb-3">
                            <!-- Lokasi terakhir -->
                            <div class="col-md-8">
                                <label class="form-label">Lokasi Terakhir Terlihat <span class="text-danger">*</span></label>
                                <input type="text" name="lokasi_terakhir"
                                       class="form-control <?= isset($errors['lokasi_terakhir']) ? 'is-invalid' : '' ?>"
                                       value="<?= e($data['lokasi_terakhir'] ?? '') ?>"
                                       placeholder="Contoh: Dekat Pasar Gianyar, Bali" required>
                                <?php if (isset($errors['lokasi_terakhir'])): ?>
                                <div class="invalid-feedback"><?= e($errors['lokasi_terakhir']) ?></div>
                                <?php endif; ?>
                            </div>
                            <!-- Tanggal hilang -->
                            <div class="col-md-4">
                                <label class="form-label">Tanggal Terakhir Terlihat <span class="text-danger">*</span></label>
                                <input type="date" name="tanggal_hilang"
                                       class="form-control <?= isset($errors['tanggal_hilang']) ? 'is-invalid' : '' ?>"
                                       value="<?= e($data['tanggal_hilang'] ?? '') ?>"
                                       max="<?= date('Y-m-d') ?>" required>
                                <?php if (isset($errors['tanggal_hilang'])): ?>
                                <div class="invalid-feedback"><?= e($errors['tanggal_hilang']) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Domisili -->
                        <div class="mb-3">
                            <label class="form-label">Domisili / Alamat Asal <span class="text-danger">*</span></label>
                            <input type="text" name="domisili"
                                   class="form-control <?= isset($errors['domisili']) ? 'is-invalid' : '' ?>"
                                   value="<?= e($data['domisili'] ?? '') ?>"
                                   placeholder="Contoh: Jl. Melati No. 5, Gianyar, Bali" required>
                            <?php if (isset($errors['domisili'])): ?>
                            <div class="invalid-feedback"><?= e($errors['domisili']) ?></div>
                            <?php endif; ?>
                        </div>

                        <hr class="my-3">
                        <h6 class="fw-bold mb-3">📞 Data Pelapor / Keluarga</h6>

                        <div class="row g-3 mb-3">
                            <!-- Nama pelapor -->
                            <div class="col-md-6">
                                <label class="form-label">Nama Pelapor / Keluarga <span class="text-danger">*</span></label>
                                <input type="text" name="nama_pelapor"
                                       class="form-control <?= isset($errors['nama_pelapor']) ? 'is-invalid' : '' ?>"
                                       value="<?= e($data['nama_pelapor'] ?? '') ?>" required>
                                <?php if (isset($errors['nama_pelapor'])): ?>
                                <div class="invalid-feedback"><?= e($errors['nama_pelapor']) ?></div>
                                <?php endif; ?>
                            </div>
                            <!-- Hubungan -->
                            <div class="col-md-6">
                                <label class="form-label">Hubungan dengan Korban <span class="text-danger">*</span></label>
                                <input type="text" name="hubungan"
                                       class="form-control <?= isset($errors['hubungan']) ? 'is-invalid' : '' ?>"
                                       value="<?= e($data['hubungan'] ?? '') ?>"
                                       placeholder="Contoh: Istri, Orang tua, Saudara..." required>
                                <?php if (isset($errors['hubungan'])): ?>
                                <div class="invalid-feedback"><?= e($errors['hubungan']) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Kontak -->
                        <div class="mb-4">
                            <label class="form-label">No. HP / WhatsApp Pelapor <span class="text-danger">*</span></label>
                            <input type="tel" name="kontak_pelapor"
                                   class="form-control <?= isset($errors['kontak_pelapor']) ? 'is-invalid' : '' ?>"
                                   value="<?= e($data['kontak_pelapor'] ?? '') ?>"
                                   placeholder="Contoh: 08123456789" required>
                            <?php if (isset($errors['kontak_pelapor'])): ?>
                            <div class="invalid-feedback"><?= e($errors['kontak_pelapor']) ?></div>
                            <?php endif; ?>
                            <div class="form-text">Nomor ini akan digunakan oleh penemu untuk menghubungi Anda.</div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center">
                            <a href="<?= BASE_URL ?>/orang-hilang.php" class="btn btn-outline-secondary">Kembali</a>
                            <button type="submit" class="btn btn-danger px-4">
                                📋 Kirim Laporan
                            </button>
                        </div>

                    </form>
                </div>
            </div>

            <?php endif; // end !sukses ?>

        </div>
    </div>
</div><!-- /container -->

<?php
$skrip_tambahan = '<script>
// Validasi client-side sederhana
document.getElementById("formLaporan")?.addEventListener("submit", function(e) {
    var form = this;
    if (!form.checkValidity()) {
        e.preventDefault();
        e.stopPropagation();
    }
    form.classList.add("was-validated");
});
</script>';
require_once __DIR__ . '/includes/footer.php';
?>
