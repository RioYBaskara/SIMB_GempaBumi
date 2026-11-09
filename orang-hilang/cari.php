<?php
/**
 * Pencarian publik orang hilang. Hanya laporan berstatus "terverifikasi" yang tampil.
 */
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../includes/fungsi.php';

$q      = mb_substr(trim((string) ($_GET['q'] ?? '')), 0, 100);
$jk     = in_array($_GET['jk'] ?? '', ['L', 'P'], true) ? $_GET['jk'] : '';
$status = in_array($_GET['status'] ?? '', ['hilang', 'ditemukan', 'dalam_perawatan'], true) ? $_GET['status'] : '';
$usiaMin = ctype_digit((string) ($_GET['usia_min'] ?? '')) ? (int) $_GET['usia_min'] : null;
$usiaMax = ctype_digit((string) ($_GET['usia_max'] ?? '')) ? (int) $_GET['usia_max'] : null;
$hal    = max(1, (int) ($_GET['hal'] ?? 1));
$perHal = 12;

// ---- bangun WHERE dinamis (tetap pakai prepared statement) ----
$where  = ["status_laporan = 'terverifikasi'"];
$types  = '';
$params = [];

if ($q !== '') {
    // escape wildcard LIKE agar input "%" atau "_" tidak dianggap pola
    $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $q) . '%';
    $where[] = '(nama_lengkap LIKE ? OR lokasi_terakhir LIKE ? OR domisili LIKE ?)';
    $types .= 'sss';
    array_push($params, $like, $like, $like);
}
if ($jk !== '')     { $where[] = 'jenis_kelamin = ?'; $types .= 's'; $params[] = $jk; }
if ($status !== '') { $where[] = 'status_orang = ?';  $types .= 's'; $params[] = $status; }
if ($usiaMin !== null) { $where[] = 'usia >= ?'; $types .= 'i'; $params[] = $usiaMin; }
if ($usiaMax !== null) { $where[] = 'usia <= ?'; $types .= 'i'; $params[] = $usiaMax; }
$whereSql = implode(' AND ', $where);

// ---- total ----
$stmt = $koneksi->prepare("SELECT COUNT(*) AS c FROM orang_hilang WHERE $whereSql");
if ($types) { $stmt->bind_param($types, ...$params); }
$stmt->execute();
$total = (int) $stmt->get_result()->fetch_assoc()['c'];
$stmt->close();

$totalHal = max(1, (int) ceil($total / $perHal));
$hal      = min($hal, $totalHal);
$offset   = ($hal - 1) * $perHal;

// ---- data: yang masih dicari tampil lebih dulu ----
$stmt = $koneksi->prepare(
    "SELECT kode_laporan, nama_lengkap, usia, jenis_kelamin, foto, lokasi_terakhir,
            domisili, status_orang, created_at
       FROM orang_hilang
      WHERE $whereSql
      ORDER BY (status_orang = 'hilang') DESC, created_at DESC
      LIMIT ? OFFSET ?"
);
$t2 = $types . 'ii';
$p2 = array_merge($params, [$perHal, $offset]);
$stmt->bind_param($t2, ...$p2);
$stmt->execute();
$daftar = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// ---- ringkasan angka (semua laporan terverifikasi) ----
$ringkas = ['hilang' => 0, 'ditemukan' => 0, 'dalam_perawatan' => 0];
$r = $koneksi->query("SELECT status_orang, COUNT(*) c FROM orang_hilang WHERE status_laporan='terverifikasi' GROUP BY status_orang");
while ($row = $r->fetch_assoc()) { $ringkas[$row['status_orang']] = (int) $row['c']; }

function url_hal(int $h): string
{
    $p = $_GET;
    $p['hal'] = $h;
    return '?' . http_build_query($p);
}

$judul = 'Cari Orang Hilang - SIMB';
$base  = '../';
$aktif = 'orang-hilang';
$tanpaAlert = true;
require __DIR__ . '/../includes/header.php';
?>

<main class="container-xl py-3">
  <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h1 class="h4 fw-bold mb-0"><i class="bi bi-search text-danger"></i> Cari Orang Hilang</h1>
    <a class="btn btn-danger fw-bold" href="lapor.php"><i class="bi bi-person-plus"></i> Laporkan Orang Hilang</a>
  </div>

  <div class="row g-2 mb-3 text-center">
    <div class="col-4"><div class="kartu p-2"><div class="fs-4 fw-bold text-danger"><?= $ringkas['hilang'] ?></div><div class="small">Masih dicari</div></div></div>
    <div class="col-4"><div class="kartu p-2"><div class="fs-4 fw-bold text-success"><?= $ringkas['ditemukan'] ?></div><div class="small">Ditemukan</div></div></div>
    <div class="col-4"><div class="kartu p-2"><div class="fs-4 fw-bold text-warning"><?= $ringkas['dalam_perawatan'] ?></div><div class="small">Dalam perawatan</div></div></div>
  </div>

  <form class="kartu p-3 mb-3" method="get">
    <div class="row g-2 align-items-end">
      <div class="col-md-4">
        <label class="form-label small mb-1" for="q">Nama / lokasi / domisili</label>
        <input class="form-control" id="q" name="q" value="<?= e($q) ?>" placeholder="mis. Budi, Cianjur">
      </div>
      <div class="col-6 col-md-2">
        <label class="form-label small mb-1" for="status">Status</label>
        <select class="form-select" id="status" name="status">
          <option value="">Semua</option>
          <option value="hilang" <?= $status === 'hilang' ? 'selected' : '' ?>>Masih dicari</option>
          <option value="ditemukan" <?= $status === 'ditemukan' ? 'selected' : '' ?>>Ditemukan</option>
          <option value="dalam_perawatan" <?= $status === 'dalam_perawatan' ? 'selected' : '' ?>>Dalam perawatan</option>
        </select>
      </div>
      <div class="col-6 col-md-2">
        <label class="form-label small mb-1" for="jk">Jenis kelamin</label>
        <select class="form-select" id="jk" name="jk">
          <option value="">Semua</option>
          <option value="L" <?= $jk === 'L' ? 'selected' : '' ?>>Laki-laki</option>
          <option value="P" <?= $jk === 'P' ? 'selected' : '' ?>>Perempuan</option>
        </select>
      </div>
      <div class="col-3 col-md-1">
        <label class="form-label small mb-1" for="usia_min">Usia dari</label>
        <input class="form-control" id="usia_min" name="usia_min" type="number" min="0" max="120" value="<?= $usiaMin !== null ? $usiaMin : '' ?>">
      </div>
      <div class="col-3 col-md-1">
        <label class="form-label small mb-1" for="usia_max">sampai</label>
        <input class="form-control" id="usia_max" name="usia_max" type="number" min="0" max="120" value="<?= $usiaMax !== null ? $usiaMax : '' ?>">
      </div>
      <div class="col-6 col-md-2 d-grid">
        <button class="btn btn-dark" type="submit"><i class="bi bi-search"></i> Cari</button>
      </div>
    </div>
  </form>

  <div class="small text-muted mb-2"><?= $total ?> laporan ditemukan<?= $totalHal > 1 ? " &middot; halaman $hal dari $totalHal" : '' ?></div>

  <?php if (!$daftar): ?>
    <div class="kartu p-4 text-center text-muted">
      Tidak ada laporan yang cocok. Coba kata kunci lain, atau <a href="lapor.php">buat laporan baru</a>.
    </div>
  <?php else: ?>
    <div class="row g-3">
      <?php foreach ($daftar as $o): [$lbl, $kls] = label_status_orang($o['status_orang']); ?>
        <div class="col-6 col-md-4 col-lg-3">
          <a class="text-decoration-none text-dark" href="detail.php?kode=<?= e(urlencode($o['kode_laporan'])) ?>">
            <div class="kartu h-100 overflow-hidden">
              <?php if ($o['foto']): ?>
                <img src="../uploads/<?= e($o['foto']) ?>" alt="Foto <?= e($o['nama_lengkap']) ?>"
                     class="w-100" style="height:170px;object-fit:cover;" loading="lazy">
              <?php else: ?>
                <div class="d-flex align-items-center justify-content-center bg-light text-secondary" style="height:170px;">
                  <i class="bi bi-person-fill" style="font-size:4rem;"></i>
                </div>
              <?php endif; ?>
              <div class="p-2">
                <span class="badge <?= $kls ?> mb-1"><?= e($lbl) ?></span>
                <div class="fw-bold lh-sm"><?= e($o['nama_lengkap']) ?></div>
                <div class="small text-muted">
                  <?= $o['usia'] !== null ? e($o['usia']) . ' th, ' : '' ?><?= $o['jenis_kelamin'] === 'L' ? 'Laki-laki' : 'Perempuan' ?>
                </div>
                <div class="small mt-1"><i class="bi bi-geo-alt"></i> <?= e($o['lokasi_terakhir']) ?></div>
              </div>
            </div>
          </a>
        </div>
      <?php endforeach; ?>
    </div>

    <?php if ($totalHal > 1): ?>
      <nav class="mt-3" aria-label="Halaman">
        <ul class="pagination justify-content-center flex-wrap">
          <li class="page-item <?= $hal <= 1 ? 'disabled' : '' ?>"><a class="page-link" href="<?= e(url_hal($hal - 1)) ?>">&laquo;</a></li>
          <?php for ($i = max(1, $hal - 2); $i <= min($totalHal, $hal + 2); $i++): ?>
            <li class="page-item <?= $i === $hal ? 'active' : '' ?>"><a class="page-link" href="<?= e(url_hal($i)) ?>"><?= $i ?></a></li>
          <?php endfor; ?>
          <li class="page-item <?= $hal >= $totalHal ? 'disabled' : '' ?>"><a class="page-link" href="<?= e(url_hal($hal + 1)) ?>">&raquo;</a></li>
        </ul>
      </nav>
    <?php endif; ?>
  <?php endif; ?>
</main>

<?php require __DIR__ . '/../includes/footer.php'; ?>
