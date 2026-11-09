<?php
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../includes/admin.php';
require_once __DIR__ . '/../includes/bmkg.php';
admin_wajib();

// Sinkron manual BMKG
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['aksi'] ?? '') === 'sinkron') {
    if (!csrf_valid()) {
        flash_set('danger', 'Sesi kedaluwarsa, coba lagi.');
    } else {
        $hasil = bmkg_sinkron_semua($koneksi, true);
        foreach ($hasil as $h) {
            flash_set($h['status'] === 'sukses' ? 'success' : 'warning',
                $h['endpoint'] . ': ' . $h['status'] . ' (data baru: ' . $h['baru'] . ')'
                . (isset($h['pesan']) ? ' - ' . $h['pesan'] : ''));
        }
    }
    header('Location: index.php');
    exit;
}

// Angka ringkasan
$lap = ['menunggu' => 0, 'terverifikasi' => 0, 'ditolak' => 0];
$r = $koneksi->query("SELECT status_laporan, COUNT(*) c FROM orang_hilang GROUP BY status_laporan");
while ($x = $r->fetch_assoc()) { $lap[$x['status_laporan']] = (int) $x['c']; }

$org = ['hilang' => 0, 'ditemukan' => 0, 'dalam_perawatan' => 0];
$r = $koneksi->query("SELECT status_orang, COUNT(*) c FROM orang_hilang WHERE status_laporan='terverifikasi' GROUP BY status_orang");
while ($x = $r->fetch_assoc()) { $org[$x['status_orang']] = (int) $x['c']; }

$totalGempa = (int) $koneksi->query("SELECT COUNT(*) c FROM gempa")->fetch_assoc()['c'];

$menunggu = $koneksi->query(
    "SELECT id, kode_laporan, nama_lengkap, usia, lokasi_terakhir, nama_pelapor, created_at
       FROM orang_hilang WHERE status_laporan = 'menunggu' ORDER BY created_at ASC LIMIT 8"
)->fetch_all(MYSQLI_ASSOC);

$sinkron = $koneksi->query(
    "SELECT endpoint, status, jumlah_baru, pesan, created_at FROM log_sinkron_api
      WHERE id IN (SELECT MAX(id) FROM log_sinkron_api GROUP BY endpoint) ORDER BY endpoint"
)->fetch_all(MYSQLI_ASSOC);

admin_atas('Dashboard', 'dashboard');
?>
<h1 class="h4 fw-bold mb-3">Dashboard</h1>

<div class="row g-3 mb-4">
  <div class="col-6 col-md-3">
    <a class="text-decoration-none" href="laporan.php?s=menunggu">
      <div class="kartu p-3 text-center <?= $lap['menunggu'] ? 'border-danger border-2' : '' ?>">
        <div class="display-6 fw-bold text-danger"><?= $lap['menunggu'] ?></div>
        <div class="small text-dark">Laporan menunggu verifikasi</div>
      </div>
    </a>
  </div>
  <div class="col-6 col-md-3"><div class="kartu p-3 text-center">
    <div class="display-6 fw-bold"><?= $lap['terverifikasi'] ?></div><div class="small">Laporan terverifikasi</div></div></div>
  <div class="col-6 col-md-3"><div class="kartu p-3 text-center">
    <div class="display-6 fw-bold text-success"><?= $org['ditemukan'] ?></div><div class="small">Orang ditemukan</div></div></div>
  <div class="col-6 col-md-3"><div class="kartu p-3 text-center">
    <div class="display-6 fw-bold"><?= $totalGempa ?></div><div class="small">Data gempa tersimpan</div></div></div>
</div>

<div class="row g-3">
  <div class="col-lg-7">
    <section class="kartu">
      <div class="kartu-judul d-flex justify-content-between"><span>Menunggu verifikasi (terlama dulu)</span>
        <a class="small fw-normal" href="laporan.php?s=menunggu">Semua</a></div>
      <?php if (!$menunggu): ?>
        <div class="p-3 text-muted">Tidak ada laporan yang menunggu. 👍</div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-sm table-hover align-middle mb-0">
            <thead class="table-light"><tr><th>Kode</th><th>Nama</th><th>Lokasi terakhir</th><th>Masuk</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($menunggu as $m): ?>
              <tr>
                <td class="font-monospace small"><?= e($m['kode_laporan']) ?></td>
                <td><?= e($m['nama_lengkap']) ?><?= $m['usia'] !== null ? ' <span class="text-muted small">(' . e($m['usia']) . ' th)</span>' : '' ?></td>
                <td class="small"><?= e($m['lokasi_terakhir']) ?></td>
                <td class="small text-nowrap"><?= e(date('d/m H:i', strtotime($m['created_at']))) ?></td>
                <td><a class="btn btn-sm btn-dark" href="tinjau.php?id=<?= (int) $m['id'] ?>">Tinjau</a></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </section>
  </div>

  <div class="col-lg-5">
    <section class="kartu">
      <div class="kartu-judul">Sinkronisasi data BMKG</div>
      <div class="p-3">
        <?php if (!$sinkron): ?>
          <div class="text-muted small mb-2">Belum pernah sinkron.</div>
        <?php else: ?>
          <ul class="list-unstyled small mb-3">
            <?php foreach ($sinkron as $s): ?>
              <li class="mb-1">
                <span class="badge <?= $s['status'] === 'sukses' ? 'bg-success' : 'bg-danger' ?>"><?= e($s['status']) ?></span>
                <strong><?= e($s['endpoint']) ?></strong>
                &middot; <?= e(date('d/m H:i:s', strtotime($s['created_at']))) ?>
                <?= $s['status'] === 'sukses' ? '(baru: ' . (int) $s['jumlah_baru'] . ')' : '' ?>
                <?php if ($s['pesan']): ?><div class="text-danger"><?= e($s['pesan']) ?></div><?php endif; ?>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
        <form method="post">
          <?= csrf_field() ?>
          <input type="hidden" name="aksi" value="sinkron">
          <button class="btn btn-outline-dark btn-sm" type="submit"><i class="bi bi-arrow-repeat"></i> Sinkron sekarang</button>
        </form>
      </div>
    </section>
  </div>
</div>
<?php admin_bawah(); ?>
