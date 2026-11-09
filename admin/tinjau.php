<?php
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../includes/admin.php';
admin_wajib();

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);

function ambil_laporan(mysqli $db, int $id): ?array
{
    $stmt = $db->prepare("SELECT * FROM orang_hilang WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $r = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $r ?: null;
}

$o = ambil_laporan($koneksi, $id);
if (!$o) {
    flash_set('danger', 'Laporan tidak ditemukan.');
    header('Location: laporan.php');
    exit;
}

/* ---------------- Aksi (POST) ---------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = $_POST['aksi'] ?? '';
    $adm  = (int) $_SESSION['admin_id'];

    if (!csrf_valid()) {
        flash_set('danger', 'Sesi kedaluwarsa. Coba lagi.');
        header('Location: tinjau.php?id=' . $id);
        exit;
    }

    switch ($aksi) {
        case 'setujui':
            $stmt = $koneksi->prepare(
                "UPDATE orang_hilang SET status_laporan='terverifikasi', alasan_penolakan=NULL,
                        diverifikasi_oleh=?, diverifikasi_pada=NOW() WHERE id=?");
            $stmt->bind_param('ii', $adm, $id);
            $stmt->execute();
            flash_set('success', 'Laporan disetujui dan kini tampil di pencarian publik.');
            header('Location: ' . (($_POST['lanjut'] ?? '') === '1' ? 'laporan.php?s=menunggu' : 'tinjau.php?id=' . $id));
            exit;

        case 'tolak':
            $alasan = mb_substr(trim((string) ($_POST['alasan'] ?? '')), 0, 255);
            if ($alasan === '') {
                flash_set('danger', 'Alasan penolakan wajib diisi.');
                header('Location: tinjau.php?id=' . $id);
                exit;
            }
            $stmt = $koneksi->prepare(
                "UPDATE orang_hilang SET status_laporan='ditolak', alasan_penolakan=?,
                        diverifikasi_oleh=?, diverifikasi_pada=NOW() WHERE id=?");
            $stmt->bind_param('sii', $alasan, $adm, $id);
            $stmt->execute();
            flash_set('warning', 'Laporan ditolak.');
            header('Location: laporan.php?s=menunggu');
            exit;

        case 'tunggu':   // kembalikan ke antrean
            $stmt = $koneksi->prepare(
                "UPDATE orang_hilang SET status_laporan='menunggu', alasan_penolakan=NULL,
                        diverifikasi_oleh=NULL, diverifikasi_pada=NULL WHERE id=?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            flash_set('info', 'Laporan dikembalikan ke antrean verifikasi.');
            header('Location: tinjau.php?id=' . $id);
            exit;

        case 'status':
            $baru = $_POST['status_orang'] ?? '';
            if (in_array($baru, ['hilang', 'ditemukan', 'dalam_perawatan'], true)) {
                $stmt = $koneksi->prepare("UPDATE orang_hilang SET status_orang=? WHERE id=?");
                $stmt->bind_param('si', $baru, $id);
                $stmt->execute();
                flash_set('success', 'Status orang diperbarui.');
            }
            header('Location: tinjau.php?id=' . $id);
            exit;

        case 'hapus':
            if (($_POST['konfirmasi'] ?? '') !== $o['kode_laporan']) {
                flash_set('danger', 'Kode konfirmasi tidak cocok. Laporan tidak dihapus.');
                header('Location: tinjau.php?id=' . $id);
                exit;
            }
            $stmt = $koneksi->prepare("DELETE FROM orang_hilang WHERE id=?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            hapus_foto($o['foto']);
            flash_set('success', 'Laporan ' . $o['kode_laporan'] . ' dihapus permanen.');
            header('Location: laporan.php');
            exit;
    }
    header('Location: tinjau.php?id=' . $id);
    exit;
}

/* ---------------- Data pendukung ---------------- */
// Laporan lain dengan nama sama: bantu deteksi duplikat / laporan palsu berulang
$stmt = $koneksi->prepare(
    "SELECT id, kode_laporan, status_laporan, nama_pelapor, created_at
       FROM orang_hilang WHERE nama_lengkap = ? AND id <> ? ORDER BY created_at DESC LIMIT 5"
);
$stmt->bind_param('si', $o['nama_lengkap'], $id);
$stmt->execute();
$serupa = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Berapa laporan dari IP yang sama
$stmt = $koneksi->prepare("SELECT COUNT(*) AS c FROM orang_hilang WHERE ip_pelapor = ?");
$stmt->bind_param('s', $o['ip_pelapor']);
$stmt->execute();
$dariIp = (int) $stmt->get_result()->fetch_assoc()['c'];
$stmt->close();

[$lbl, $kls] = label_status_orang($o['status_orang']);
$badgeLap = ['menunggu' => 'bg-secondary', 'terverifikasi' => 'bg-success', 'ditolak' => 'bg-danger'][$o['status_laporan']];

admin_atas('Tinjau ' . $o['kode_laporan'], 'laporan');
?>
<a class="small" href="laporan.php?s=<?= e($o['status_laporan']) ?>">&laquo; Kembali ke daftar</a>
<div class="d-flex flex-wrap align-items-center gap-2 my-2">
  <h1 class="h4 fw-bold mb-0"><?= e($o['nama_lengkap']) ?></h1>
  <span class="badge <?= $badgeLap ?>"><?= e(ucfirst($o['status_laporan'])) ?></span>
  <span class="badge <?= $kls ?>"><?= e($lbl) ?></span>
  <span class="font-monospace text-muted"><?= e($o['kode_laporan']) ?></span>
</div>

<div class="row g-3">
  <div class="col-lg-8">
    <section class="kartu p-3 mb-3">
      <div class="row g-3">
        <div class="col-md-4">
          <?php if ($o['foto']): ?>
            <a href="../uploads/<?= e($o['foto']) ?>" target="_blank" rel="noopener">
              <img class="img-fluid rounded border" src="../uploads/<?= e($o['foto']) ?>" alt="Foto <?= e($o['nama_lengkap']) ?>"></a>
          <?php else: ?>
            <div class="bg-light text-secondary d-flex align-items-center justify-content-center rounded" style="height:160px;"><i class="bi bi-person-fill" style="font-size:4rem;"></i></div>
            <div class="small text-muted text-center mt-1">Tanpa foto</div>
          <?php endif; ?>
        </div>
        <div class="col-md-8">
          <dl class="row mb-0 small">
            <dt class="col-sm-4">Usia / JK</dt><dd class="col-sm-8"><?= $o['usia'] !== null ? e($o['usia']) . ' th' : '-' ?> / <?= $o['jenis_kelamin'] === 'L' ? 'Laki-laki' : 'Perempuan' ?></dd>
            <dt class="col-sm-4">Ciri-ciri</dt><dd class="col-sm-8"><?= $o['ciri_ciri'] ? nl2br(e($o['ciri_ciri'])) : '-' ?></dd>
            <dt class="col-sm-4">Lokasi terakhir</dt><dd class="col-sm-8"><?= e($o['lokasi_terakhir']) ?>
              <?php if ($o['lintang_terakhir'] !== null): ?>
                <a href="https://www.openstreetmap.org/?mlat=<?= e($o['lintang_terakhir']) ?>&amp;mlon=<?= e($o['bujur_terakhir']) ?>#map=14/<?= e($o['lintang_terakhir']) ?>/<?= e($o['bujur_terakhir']) ?>" target="_blank" rel="noopener">(lihat peta)</a>
              <?php endif; ?></dd>
            <dt class="col-sm-4">Waktu terakhir</dt><dd class="col-sm-8"><?= $o['waktu_terakhir'] ? e(date('d M Y H:i', strtotime($o['waktu_terakhir']))) . ' WIB' : '-' ?></dd>
            <dt class="col-sm-4">Domisili</dt><dd class="col-sm-8"><?= e($o['domisili']) ?></dd>
          </dl>
        </div>
      </div>
    </section>

    <section class="kartu p-3 mb-3">
      <h2 class="h6 fw-bold">Pelapor</h2>
      <dl class="row mb-0 small">
        <dt class="col-sm-3">Nama</dt><dd class="col-sm-9"><?= e($o['nama_pelapor']) ?><?= $o['hubungan'] ? ' (' . e($o['hubungan']) . ')' : '' ?></dd>
        <dt class="col-sm-3">Kontak</dt><dd class="col-sm-9">
          <?= e($o['kontak_pelapor']) ?>
          <a class="ms-2" href="https://wa.me/<?= e(nomor_wa($o['kontak_pelapor'])) ?>" target="_blank" rel="noopener"><i class="bi bi-whatsapp"></i> Tes WhatsApp</a></dd>
        <dt class="col-sm-3">IP / masuk</dt><dd class="col-sm-9"><?= e($o['ip_pelapor']) ?> &middot; <?= e(date('d M Y H:i', strtotime($o['created_at']))) ?>
          <?php if ($dariIp > 1): ?><span class="badge bg-warning text-dark"><?= $dariIp ?> laporan dari IP ini</span><?php endif; ?></dd>
      </dl>
      <?php if ($serupa): ?>
        <div class="alert alert-warning small mt-3 mb-0">
          <strong>Ada laporan lain dengan nama yang sama</strong> (cek duplikat):
          <ul class="mb-0">
            <?php foreach ($serupa as $s): ?>
              <li><a href="tinjau.php?id=<?= (int) $s['id'] ?>"><?= e($s['kode_laporan']) ?></a>
                - <?= e($s['status_laporan']) ?>, pelapor <?= e($s['nama_pelapor']) ?>, <?= e(date('d/m/y', strtotime($s['created_at']))) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>
    </section>
  </div>

  <div class="col-lg-4">
    <section class="kartu p-3 mb-3">
      <h2 class="h6 fw-bold">Verifikasi</h2>

      <?php if ($o['status_laporan'] === 'ditolak' && $o['alasan_penolakan']): ?>
        <div class="alert alert-danger small py-2">Alasan penolakan: <?= e($o['alasan_penolakan']) ?></div>
      <?php endif; ?>

      <?php if ($o['status_laporan'] !== 'terverifikasi'): ?>
        <form method="post" class="mb-2">
          <?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>"><input type="hidden" name="aksi" value="setujui">
          <?php if ($o['status_laporan'] === 'menunggu'): ?><input type="hidden" name="lanjut" value="1"><?php endif; ?>
          <button class="btn btn-success w-100" type="submit"><i class="bi bi-check-circle"></i> Setujui &amp; tampilkan</button>
        </form>
      <?php endif; ?>

      <?php if ($o['status_laporan'] !== 'ditolak'): ?>
        <form method="post" class="mb-2">
          <?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>"><input type="hidden" name="aksi" value="tolak">
          <input class="form-control form-control-sm mb-1" name="alasan" maxlength="255" required placeholder="Alasan penolakan (wajib)">
          <button class="btn btn-outline-danger w-100" type="submit"><i class="bi bi-x-circle"></i> Tolak laporan</button>
        </form>
      <?php endif; ?>

      <?php if ($o['status_laporan'] !== 'menunggu'): ?>
        <form method="post">
          <?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>"><input type="hidden" name="aksi" value="tunggu">
          <button class="btn btn-outline-secondary btn-sm w-100" type="submit">Kembalikan ke antrean</button>
        </form>
      <?php endif; ?>

      <?php if ($o['status_laporan'] === 'terverifikasi'): ?>
        <a class="btn btn-outline-dark btn-sm w-100 mt-2" target="_blank" rel="noopener"
           href="../orang-hilang/detail.php?kode=<?= e(urlencode($o['kode_laporan'])) ?>">Lihat halaman publik</a>
      <?php endif; ?>
    </section>

    <section class="kartu p-3 mb-3">
      <h2 class="h6 fw-bold">Ubah status orang</h2>
      <form method="post" class="d-flex gap-2">
        <?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>"><input type="hidden" name="aksi" value="status">
        <select class="form-select form-select-sm" name="status_orang">
          <option value="hilang" <?= $o['status_orang'] === 'hilang' ? 'selected' : '' ?>>Masih dicari</option>
          <option value="ditemukan" <?= $o['status_orang'] === 'ditemukan' ? 'selected' : '' ?>>Ditemukan</option>
          <option value="dalam_perawatan" <?= $o['status_orang'] === 'dalam_perawatan' ? 'selected' : '' ?>>Dalam perawatan</option>
        </select>
        <button class="btn btn-dark btn-sm" type="submit">Simpan</button>
      </form>
    </section>

    <section class="kartu p-3 border-danger">
      <h2 class="h6 fw-bold text-danger">Hapus permanen</h2>
      <p class="small text-muted mb-2">Untuk spam atau data keliru. Ketik kode <span class="font-monospace"><?= e($o['kode_laporan']) ?></span> untuk konfirmasi.</p>
      <form method="post">
        <?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>"><input type="hidden" name="aksi" value="hapus">
        <input class="form-control form-control-sm mb-1 font-monospace" name="konfirmasi" required autocomplete="off" placeholder="<?= e($o['kode_laporan']) ?>">
        <button class="btn btn-danger btn-sm w-100" type="submit"><i class="bi bi-trash"></i> Hapus</button>
      </form>
    </section>
  </div>
</div>
<?php admin_bawah(); ?>
