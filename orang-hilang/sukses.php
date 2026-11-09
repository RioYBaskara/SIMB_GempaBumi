<?php
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../includes/fungsi.php';

// Data ini hanya ada di sesi pelapor yang baru saja mengirim laporan
$s = $_SESSION['lapor_sukses'] ?? null;
if (!$s) {
    header('Location: lapor.php');
    exit;
}
header('Referrer-Policy: no-referrer');

$linkKelola = url_folder_ini() . 'kelola.php?kode=' . urlencode($s['kode']) . '&token=' . urlencode($s['token']);

$judul = 'Laporan Terkirim - SIMB';
$base  = '../';
$aktif = 'orang-hilang';
$tanpaAlert = true;
require __DIR__ . '/../includes/header.php';
?>

<main class="container-xl py-3" style="max-width: 760px;">
  <div class="alert alert-success">
    <h1 class="h5 fw-bold mb-1"><i class="bi bi-check-circle-fill"></i> Laporan berhasil dikirim</h1>
    Laporan Anda berstatus <strong>Menunggu verifikasi admin</strong> dan belum tampil di pencarian publik.
  </div>

  <div class="kartu p-3 mb-3">
    <div class="text-muted small">Kode laporan</div>
    <div class="display-6 fw-bold font-monospace"><?= e($s['kode']) ?></div>
  </div>

  <div class="kartu p-3 mb-3 border-danger border-2">
    <h2 class="h6 fw-bold text-danger"><i class="bi bi-key-fill"></i> Simpan link rahasia ini</h2>
    <p class="small mb-2">
      Link ini satu-satunya cara Anda memperbarui status laporan (mis. menandai <em>Ditemukan</em>) tanpa akun.
      <strong>Jangan dibagikan ke orang lain.</strong> Screenshot atau salin link ini sekarang.
    </p>
    <input class="form-control font-monospace small mb-2" id="linkKelola" value="<?= e($linkKelola) ?>" readonly onclick="this.select()">
    <div class="d-flex flex-wrap gap-2">
      <button class="btn btn-dark" type="button"
              onclick="navigator.clipboard.writeText(document.getElementById('linkKelola').value).then(()=>{this.textContent='Tersalin ✓'})">
        <i class="bi bi-clipboard"></i> Salin link
      </button>
      <a class="btn btn-outline-dark" href="<?= e($linkKelola) ?>">Buka halaman kelola</a>
    </div>
  </div>

  <div class="d-flex flex-wrap gap-2">
    <a class="btn btn-danger" href="cari.php"><i class="bi bi-search"></i> Cari orang hilang</a>
    <a class="btn btn-outline-dark" href="../index.php">Kembali ke beranda</a>
  </div>
  <p class="small text-muted mt-3">
    Mendesak? Hubungi <a href="tel:115">Basarnas 115</a> atau <a href="tel:117">BNPB 117</a> tanpa menunggu verifikasi.
  </p>
</main>

<?php require __DIR__ . '/../includes/footer.php'; ?>
