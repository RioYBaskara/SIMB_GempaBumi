<?php
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../includes/admin.php';
admin_wajib();

$tab = in_array($_GET['s'] ?? '', ['menunggu', 'terverifikasi', 'ditolak'], true) ? $_GET['s'] : 'menunggu';
$q   = mb_substr(trim((string) ($_GET['q'] ?? '')), 0, 100);
$hal = max(1, (int) ($_GET['hal'] ?? 1));
$per = 20;

// Jumlah per tab
$jml = ['menunggu' => 0, 'terverifikasi' => 0, 'ditolak' => 0];
$r = $koneksi->query("SELECT status_laporan, COUNT(*) c FROM orang_hilang GROUP BY status_laporan");
while ($x = $r->fetch_assoc()) { $jml[$x['status_laporan']] = (int) $x['c']; }

// Filter
$where  = 'status_laporan = ?';
$types  = 's';
$params = [$tab];
if ($q !== '') {
    $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $q) . '%';
    $where .= ' AND (nama_lengkap LIKE ? OR kode_laporan LIKE ? OR nama_pelapor LIKE ? OR kontak_pelapor LIKE ?)';
    $types .= 'ssss';
    array_push($params, $like, $like, $like, $like);
}

$stmt = $koneksi->prepare("SELECT COUNT(*) AS c FROM orang_hilang WHERE $where");
$stmt->bind_param($types, ...$params);
$stmt->execute();
$total = (int) $stmt->get_result()->fetch_assoc()['c'];
$stmt->close();

$totalHal = max(1, (int) ceil($total / $per));
$hal = min($hal, $totalHal);
$offset = ($hal - 1) * $per;

// Menunggu: terlama dulu (adil/antrean); lainnya: terbaru dulu
$urut = $tab === 'menunggu' ? 'created_at ASC' : 'created_at DESC';
$stmt = $koneksi->prepare(
    "SELECT id, kode_laporan, nama_lengkap, usia, jenis_kelamin, lokasi_terakhir, nama_pelapor,
            kontak_pelapor, status_orang, created_at
       FROM orang_hilang WHERE $where ORDER BY $urut LIMIT ? OFFSET ?"
);
$p2 = array_merge($params, [$per, $offset]);
$stmt->bind_param($types . 'ii', ...$p2);
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

function url_laporan(array $ubah): string
{
    return '?' . http_build_query(array_merge($_GET, $ubah));
}

$tabInfo = ['menunggu' => 'Menunggu', 'terverifikasi' => 'Terverifikasi', 'ditolak' => 'Ditolak'];

admin_atas('Laporan Orang Hilang', 'laporan');
?>
<h1 class="h4 fw-bold mb-3">Laporan Orang Hilang</h1>

<ul class="nav nav-tabs mb-3">
  <?php foreach ($tabInfo as $k => $teks): ?>
    <li class="nav-item">
      <a class="nav-link <?= $tab === $k ? 'active fw-bold' : '' ?>" href="?s=<?= $k ?>">
        <?= e($teks) ?> <span class="badge <?= $k === 'menunggu' && $jml[$k] ? 'bg-danger' : 'bg-secondary' ?>"><?= $jml[$k] ?></span>
      </a>
    </li>
  <?php endforeach; ?>
</ul>

<form class="row g-2 mb-3" method="get">
  <input type="hidden" name="s" value="<?= e($tab) ?>">
  <div class="col-sm-8 col-md-5">
    <input class="form-control" name="q" value="<?= e($q) ?>" placeholder="Cari nama, kode, pelapor, atau nomor HP">
  </div>
  <div class="col-auto"><button class="btn btn-dark" type="submit"><i class="bi bi-search"></i> Cari</button></div>
  <?php if ($q !== ''): ?><div class="col-auto"><a class="btn btn-outline-secondary" href="?s=<?= e($tab) ?>">Reset</a></div><?php endif; ?>
</form>

<div class="kartu table-responsive">
  <table class="table table-sm table-hover align-middle mb-0">
    <thead class="table-light">
      <tr><th>Kode</th><th>Nama</th><th>Lokasi terakhir</th><th>Pelapor</th><th>Status orang</th><th>Masuk</th><th></th></tr>
    </thead>
    <tbody>
    <?php if (!$rows): ?>
      <tr><td colspan="7" class="text-center text-muted py-4">Tidak ada laporan.</td></tr>
    <?php endif; ?>
    <?php foreach ($rows as $o): [$lbl, $kls] = label_status_orang($o['status_orang']); ?>
      <tr>
        <td class="font-monospace small"><?= e($o['kode_laporan']) ?></td>
        <td><?= e($o['nama_lengkap']) ?>
          <span class="text-muted small">(<?= $o['usia'] !== null ? e($o['usia']) . ' th, ' : '' ?><?= $o['jenis_kelamin'] ?>)</span></td>
        <td class="small"><?= e($o['lokasi_terakhir']) ?></td>
        <td class="small"><?= e($o['nama_pelapor']) ?><br><span class="text-muted"><?= e($o['kontak_pelapor']) ?></span></td>
        <td><span class="badge <?= $kls ?>"><?= e($lbl) ?></span></td>
        <td class="small text-nowrap"><?= e(date('d/m/y H:i', strtotime($o['created_at']))) ?></td>
        <td><a class="btn btn-sm btn-dark" href="tinjau.php?id=<?= (int) $o['id'] ?>">Tinjau</a></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php if ($totalHal > 1): ?>
  <nav class="mt-3" aria-label="Halaman">
    <ul class="pagination justify-content-center flex-wrap">
      <li class="page-item <?= $hal <= 1 ? 'disabled' : '' ?>"><a class="page-link" href="<?= e(url_laporan(['hal' => $hal - 1])) ?>">&laquo;</a></li>
      <?php for ($i = max(1, $hal - 2); $i <= min($totalHal, $hal + 2); $i++): ?>
        <li class="page-item <?= $i === $hal ? 'active' : '' ?>"><a class="page-link" href="<?= e(url_laporan(['hal' => $i])) ?>"><?= $i ?></a></li>
      <?php endfor; ?>
      <li class="page-item <?= $hal >= $totalHal ? 'disabled' : '' ?>"><a class="page-link" href="<?= e(url_laporan(['hal' => $hal + 1])) ?>">&raquo;</a></li>
    </ul>
  </nav>
<?php endif; ?>
<?php admin_bawah(); ?>
