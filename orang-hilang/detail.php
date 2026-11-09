<?php
/**
 * Detail laporan (publik). Hanya laporan terverifikasi yang bisa dibuka.
 */
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../includes/fungsi.php';

$kode = strtoupper(trim((string) ($_GET['kode'] ?? '')));
$o = null;

if (kode_valid($kode)) {
    $stmt = $koneksi->prepare(
        "SELECT kode_laporan, nama_lengkap, usia, jenis_kelamin, ciri_ciri, foto, lokasi_terakhir,
                lintang_terakhir, bujur_terakhir, waktu_terakhir, domisili, nama_pelapor, hubungan,
                kontak_pelapor, status_orang, created_at, updated_at
           FROM orang_hilang
          WHERE kode_laporan = ? AND status_laporan = 'terverifikasi'"
    );
    $stmt->bind_param('s', $kode);
    $stmt->execute();
    $o = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

if (!$o) {
    http_response_code(404);
    $judul = 'Laporan tidak ditemukan - SIMB';
    $base = '../'; $aktif = 'orang-hilang'; $tanpaAlert = true;
    require __DIR__ . '/../includes/header.php';
    echo '<main class="container-xl py-5 text-center"><h1 class="h4">Laporan tidak ditemukan</h1>'
       . '<p class="text-muted">Kode salah, atau laporan belum diverifikasi admin.</p>'
       . '<a class="btn btn-dark" href="cari.php">Kembali ke pencarian</a></main>';
    require __DIR__ . '/../includes/footer.php';
    exit;
}

[$lbl, $kls] = label_status_orang($o['status_orang']);
$punyaPeta   = $o['lintang_terakhir'] !== null && $o['bujur_terakhir'] !== null;
$linkDetail  = url_folder_ini() . 'detail.php?kode=' . urlencode($o['kode_laporan']);

$teksShare = ($o['status_orang'] === 'hilang' ? 'DICARI: ' : 'UPDATE: ')
    . $o['nama_lengkap']
    . ($o['usia'] !== null ? ' (' . $o['usia'] . ' th)' : '')
    . '. Terakhir terlihat di ' . $o['lokasi_terakhir'] . '. Status: ' . $lbl
    . '. Info selengkapnya: ' . $linkDetail;
$linkWaShare = 'https://wa.me/?text=' . rawurlencode($teksShare);
$waPelapor   = nomor_wa($o['kontak_pelapor']);

$judul = $o['nama_lengkap'] . ' - Orang Hilang - SIMB';
$base  = '../';
$aktif = 'orang-hilang';
$tanpaAlert = true;
$extraJs = $punyaPeta ? ['assets/js/detail.js'] : [];
require __DIR__ . '/../includes/header.php';
?>

<main class="container-xl py-3" style="max-width: 960px;">
  <a class="small" href="cari.php">&laquo; Kembali ke pencarian</a>

  <?php if ($o['status_orang'] === 'ditemukan'): ?>
    <div class="alert alert-success mt-2"><i class="bi bi-check-circle-fill"></i> <strong>Orang ini sudah ditemukan.</strong> Terima kasih atas bantuan semua pihak.</div>
  <?php elseif ($o['status_orang'] === 'dalam_perawatan'): ?>
    <div class="alert alert-warning mt-2"><i class="bi bi-hospital"></i> <strong>Orang ini sedang dalam perawatan.</strong> Hubungi pelapor untuk keterangan lebih lanjut.</div>
  <?php endif; ?>

  <div class="row g-3 mt-1">
    <div class="col-md-5">
      <div class="kartu overflow-hidden">
        <?php if ($o['foto']): ?>
          <img src="../uploads/<?= e($o['foto']) ?>" alt="Foto <?= e($o['nama_lengkap']) ?>" class="w-100">
        <?php else: ?>
          <div class="d-flex align-items-center justify-content-center bg-light text-secondary" style="height:300px;">
            <i class="bi bi-person-fill" style="font-size:6rem;"></i>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <div class="col-md-7">
      <div class="kartu p-3">
        <span class="badge <?= $kls ?> mb-2"><?= e($lbl) ?></span>
        <h1 class="h4 fw-bold mb-0"><?= e($o['nama_lengkap']) ?></h1>
        <div class="text-muted mb-3">
          <?= $o['usia'] !== null ? e($o['usia']) . ' tahun &middot; ' : '' ?><?= $o['jenis_kelamin'] === 'L' ? 'Laki-laki' : 'Perempuan' ?>
          &middot; Kode <span class="font-monospace"><?= e($o['kode_laporan']) ?></span>
        </div>

        <dl class="row mb-0 small">
          <dt class="col-sm-4">Terakhir terlihat</dt>
          <dd class="col-sm-8"><?= e($o['lokasi_terakhir']) ?>
            <?php if ($o['waktu_terakhir']): ?><br><span class="text-muted"><?= e(date('d M Y, H:i', strtotime($o['waktu_terakhir']))) ?> WIB</span><?php endif; ?>
          </dd>
          <dt class="col-sm-4">Domisili asal</dt>
          <dd class="col-sm-8"><?= e($o['domisili']) ?></dd>
          <dt class="col-sm-4">Ciri-ciri</dt>
          <dd class="col-sm-8"><?= $o['ciri_ciri'] ? nl2br(e($o['ciri_ciri'])) : '<span class="text-muted">-</span>' ?></dd>
          <dt class="col-sm-4">Dilaporkan</dt>
          <dd class="col-sm-8"><?= e(date('d M Y, H:i', strtotime($o['created_at']))) ?> WIB</dd>
        </dl>
      </div>

      <div class="kartu p-3 mt-3">
        <h2 class="h6 fw-bold"><i class="bi bi-telephone"></i> Punya informasi? Hubungi pelapor</h2>
        <div class="small mb-2">
          <?= e($o['nama_pelapor']) ?><?= $o['hubungan'] ? ' (' . e($o['hubungan']) . ')' : '' ?> &middot;
          <span class="font-monospace"><?= e($o['kontak_pelapor']) ?></span>
        </div>
        <div class="d-flex flex-wrap gap-2">
          <a class="btn btn-danger" href="tel:<?= e(preg_replace('/[^0-9+]/', '', $o['kontak_pelapor'])) ?>"><i class="bi bi-telephone-fill"></i> Telepon</a>
          <a class="btn btn-success" href="https://wa.me/<?= e($waPelapor) ?>" target="_blank" rel="noopener"><i class="bi bi-whatsapp"></i> WhatsApp pelapor</a>
        </div>
        <div class="small text-muted mt-2">Jika menemukan orang ini, segera hubungi pelapor atau Basarnas <a href="tel:115">115</a>.</div>
      </div>

      <a class="btn btn-outline-success w-100 mt-3" href="<?= e($linkWaShare) ?>" target="_blank" rel="noopener">
        <i class="bi bi-whatsapp"></i> Bagikan ke WhatsApp
      </a>
    </div>
  </div>

  <?php if ($punyaPeta): ?>
    <section class="kartu mt-3">
      <div class="kartu-judul"><i class="bi bi-geo-alt text-danger"></i> Lokasi terakhir terlihat</div>
      <div class="p-2">
        <div id="petaDetail" style="height:320px;border-radius:.5rem;"
             data-lat="<?= e($o['lintang_terakhir']) ?>" data-lng="<?= e($o['bujur_terakhir']) ?>"
             data-label="<?= e($o['lokasi_terakhir']) ?>"></div>
      </div>
    </section>
  <?php endif; ?>
</main>

<?php require __DIR__ . '/../includes/footer.php'; ?>
