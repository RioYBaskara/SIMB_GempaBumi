<?php
/**
 * Halaman kelola laporan untuk pelapor, TANPA login.
 * Akses memakai kode laporan + token rahasia (diberikan saat laporan dikirim).
 */
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../includes/fungsi.php';

// Token ada di URL: jangan bocor lewat header Referer ke situs lain
header('Referrer-Policy: no-referrer');
header('Cache-Control: no-store');

$src   = $_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST : $_GET;
$kode  = strtoupper(trim((string) ($src['kode'] ?? '')));
$token = trim((string) ($src['token'] ?? ''));

$o = null;
if (kode_valid($kode) && preg_match('/^[a-f0-9]{32}$/', $token)) {
    $stmt = $koneksi->prepare(
        "SELECT id, kode_laporan, token_kelola, nama_lengkap, status_laporan, alasan_penolakan, status_orang
           FROM orang_hilang WHERE kode_laporan = ?"
    );
    $stmt->bind_param('s', $kode);
    $stmt->execute();
    $o = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    // Bandingkan waktu-konstan; salah token diperlakukan sama dengan "tidak ada"
    if ($o && !hash_equals($o['token_kelola'], $token)) {
        $o = null;
    }
}

$pesan = null;
$galat = null;

if ($o && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $baru = $_POST['status_orang'] ?? '';
    if (!csrf_valid()) {
        $galat = 'Sesi kedaluwarsa. Muat ulang halaman lalu coba lagi.';
    } elseif (!in_array($baru, ['hilang', 'ditemukan', 'dalam_perawatan'], true)) {
        $galat = 'Status tidak valid.';
    } else {
        $stmt = $koneksi->prepare("UPDATE orang_hilang SET status_orang = ? WHERE id = ?");
        $stmt->bind_param('si', $baru, $o['id']);
        $stmt->execute();
        $stmt->close();
        $o['status_orang'] = $baru;
        $pesan = 'Status berhasil diperbarui.';
    }
}

$judul = 'Kelola Laporan - SIMB';
$base  = '../';
$aktif = 'orang-hilang';
$tanpaAlert = true;
require __DIR__ . '/../includes/header.php';
?>

<main class="container-xl py-3" style="max-width: 680px;">
<?php if (!$o): ?>
  <div class="alert alert-danger">
    <strong>Link tidak valid.</strong> Pastikan Anda memakai link lengkap yang diberikan saat laporan dikirim.
  </div>
  <a class="btn btn-dark" href="cari.php">Kembali ke pencarian</a>

<?php else: [$lbl, $kls] = label_status_orang($o['status_orang']); ?>
  <h1 class="h4 fw-bold"><i class="bi bi-gear"></i> Kelola Laporan</h1>
  <div class="text-muted mb-3">
    <?= e($o['nama_lengkap']) ?> &middot; Kode <span class="font-monospace"><?= e($o['kode_laporan']) ?></span>
  </div>

  <?php if ($pesan): ?><div class="alert alert-success"><?= e($pesan) ?></div><?php endif; ?>
  <?php if ($galat): ?><div class="alert alert-danger"><?= e($galat) ?></div><?php endif; ?>

  <div class="kartu p-3 mb-3">
    <div class="small text-muted">Status verifikasi admin</div>
    <?php if ($o['status_laporan'] === 'menunggu'): ?>
      <div><span class="badge bg-secondary">Menunggu verifikasi</span>
        <span class="small">Laporan belum tampil di pencarian publik.</span></div>
    <?php elseif ($o['status_laporan'] === 'terverifikasi'): ?>
      <div><span class="badge bg-success">Terverifikasi</span>
        <a class="small" href="detail.php?kode=<?= e(urlencode($o['kode_laporan'])) ?>">Lihat halaman publik</a></div>
    <?php else: ?>
      <div><span class="badge bg-danger">Ditolak</span></div>
      <?php if ($o['alasan_penolakan']): ?><div class="small mt-1">Alasan: <?= e($o['alasan_penolakan']) ?></div><?php endif; ?>
    <?php endif; ?>
  </div>

  <form method="post" class="kartu p-3">
    <?= csrf_field() ?>
    <input type="hidden" name="kode" value="<?= e($o['kode_laporan']) ?>">
    <input type="hidden" name="token" value="<?= e($token) ?>">

    <div class="mb-2">Status saat ini: <span class="badge <?= $kls ?>"><?= e($lbl) ?></span></div>
    <div class="fw-bold mb-2">Ubah status orang yang dicari:</div>

    <div class="d-grid gap-2">
      <button class="btn btn-success btn-lg" name="status_orang" value="ditemukan" type="submit"
              onclick="return confirm('Tandai orang ini sebagai DITEMUKAN?');">
        <i class="bi bi-check-circle"></i> Sudah ditemukan
      </button>
      <button class="btn btn-warning btn-lg" name="status_orang" value="dalam_perawatan" type="submit">
        <i class="bi bi-hospital"></i> Dalam perawatan
      </button>
      <button class="btn btn-outline-danger" name="status_orang" value="hilang" type="submit">
        <i class="bi bi-arrow-counterclockwise"></i> Kembalikan ke "Masih dicari"
      </button>
    </div>
  </form>

  <p class="small text-muted mt-3">Jangan bagikan link halaman ini. Siapa pun yang memilikinya dapat mengubah status laporan.</p>
<?php endif; ?>
</main>

<?php require __DIR__ . '/../includes/footer.php'; ?>
