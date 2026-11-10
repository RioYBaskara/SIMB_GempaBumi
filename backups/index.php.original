<?php
/**
 * Halaman utama SIMB
 * Urutan: alert bar -> gempa terbaru + peta + kontak darurat -> akses cepat
 *         -> gempa dirasakan -> daftar M5+ -> statistik -> edukasi
 */
require_once __DIR__ . '/config/koneksi.php';

$judul = 'SIMB - Pemantauan Gempa Bumi Terkini & Layanan Darurat';
$aktif = 'beranda';
$base  = '';
$extraJs = ['assets/js/app.js', 'assets/js/statistik.js'];

// Kontak darurat (dikelola dari database)
$kontak = [];
$res = $koneksi->query("SELECT instansi, nomor, keterangan FROM kontak_darurat WHERE aktif = 1 ORDER BY urutan, id");
while ($row = $res->fetch_assoc()) {
    $kontak[] = $row;
}

require __DIR__ . '/includes/header.php';
?>

<main class="container-xl py-3">

  <!-- ============ BARIS 1: DETAIL TERBARU + KONTAK DARURAT ============ -->
  <div class="row g-3 mb-3">
    <div class="col-lg-8">
      <section class="kartu h-100">
        <div class="kartu-judul"><i class="bi bi-activity text-danger"></i> Gempa Terbaru</div>
        <div class="p-3" id="detailTerbaru">
          <span class="spinner-border spinner-border-sm me-2"></span>Memuat…
        </div>
        <div class="px-3 pb-2 small text-muted" id="statusData"></div>
      </section>
    </div>

    <div class="col-lg-4">
      <section class="kartu h-100" id="kontak">
        <div class="kartu-judul"><i class="bi bi-telephone-fill text-danger"></i> Kontak Darurat (Satu Sentuhan)</div>
        <div class="p-3 d-grid gap-2">
          <?php foreach ($kontak as $k): ?>
            <a class="btn btn-danger btn-darurat"
               href="tel:<?= htmlspecialchars(preg_replace('/[^0-9+]/', '', $k['nomor'])) ?>">
              <span class="nomor"><?= htmlspecialchars($k['nomor']) ?></span>
              <?= htmlspecialchars($k['instansi']) ?>
              <?php if ($k['keterangan']): ?><small><?= htmlspecialchars($k['keterangan']) ?></small><?php endif; ?>
            </a>
          <?php endforeach; ?>
          <?php if (!$kontak): ?>
            <div class="text-muted">Belum ada kontak darurat.</div>
          <?php endif; ?>
        </div>
      </section>
    </div>
  </div>

  <!-- ============ AKSES CEPAT ============ -->
  <div class="row g-3 mb-3 akses-cepat">
    <div class="col-6 col-md-3">
      <a href="orang-hilang/cari.php"><div class="kartu text-dark">
        <i class="bi bi-search text-danger"></i>
        <div class="fw-bold mt-1">Cari Orang Hilang</div>
        <div class="small text-muted">Cari berdasarkan nama atau lokasi</div>
      </div></a>
    </div>
    <div class="col-6 col-md-3">
      <a href="orang-hilang/lapor.php"><div class="kartu text-dark">
        <i class="bi bi-person-plus text-danger"></i>
        <div class="fw-bold mt-1">Laporkan Orang Hilang</div>
        <div class="small text-muted">Tanpa perlu login</div>
      </div></a>
    </div>
    <div class="col-6 col-md-3">
      <a href="#dirasakan"><div class="kartu text-dark">
        <i class="bi bi-people text-warning"></i>
        <div class="fw-bold mt-1">Gempa Dirasakan</div>
        <div class="small text-muted">Daerah yang merasakan guncangan</div>
      </div></a>
    </div>
    <div class="col-6 col-md-3">
      <a href="#jarak"><div class="kartu text-dark">
        <i class="bi bi-crosshair text-secondary"></i>
        <div class="fw-bold mt-1">Jarak dari Lokasi Saya</div>
        <div class="small text-muted">Seberapa jauh gempa terbaru</div>
      </div></a>
    </div>
  </div>

  <!-- ============ PETA + JARAK ============ -->
  <div class="row g-3 mb-3">
    <div class="col-lg-8">
      <section class="kartu" id="peta">
        <div class="kartu-judul d-flex flex-wrap justify-content-between align-items-center gap-2">
          <span><i class="bi bi-map text-danger"></i> Peta Gempa (30 Hari Terakhir)</span>
          <span class="small fw-normal">
            <span class="legend-dot" style="background:#c62828"></span> M&ge;6
            <span class="legend-dot ms-2" style="background:#ef6c00"></span> M5-5,9
            <span class="legend-dot ms-2" style="background:#f9a825"></span> M4-4,9
            <span class="legend-dot ms-2" style="background:#607d8b"></span> &lt;M4
          </span>
        </div>
        <div class="p-2"><div id="petaGempa" aria-label="Peta sebaran gempa"></div></div>
      </section>
    </div>

    <div class="col-lg-4">
      <section class="kartu mb-3" id="jarak">
        <div class="kartu-judul"><i class="bi bi-crosshair"></i> Jarak dari Lokasi Anda</div>
        <div class="p-3">
          <button class="btn btn-dark w-100 mb-2" id="btnLokasi"><i class="bi bi-crosshair"></i> Gunakan lokasi saya</button>
          <div id="hasilJarak" class="small text-muted">
            Lokasi hanya diproses di browser Anda dan tidak dikirim ke server.
          </div>
        </div>
      </section>

      <section class="kartu">
        <div class="kartu-judul"><i class="bi bi-bell"></i> Notifikasi</div>
        <div class="p-3">
          <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" role="switch" id="toggleNotif">
            <label class="form-check-label" for="toggleNotif">Bunyi &amp; notifikasi saat ada gempa baru</label>
          </div>
          <div class="small text-muted mt-1">Aktif selama halaman ini terbuka.</div>
        </div>
      </section>
    </div>
  </div>

  <!-- ============ GEMPA DIRASAKAN ============ -->
  <section class="mb-4" id="dirasakan">
    <h2 class="h5 fw-bold border-bottom border-2 pb-2"><i class="bi bi-people text-warning"></i> Gempa Dirasakan</h2>
    <p class="small text-muted">Gempa yang dirasakan warga, lengkap dengan wilayah dan skala MMI (intensitas guncangan).</p>
    <div class="row g-3" id="listDirasakan">
      <div class="col-12 text-muted"><span class="spinner-border spinner-border-sm me-2"></span>Memuat…</div>
    </div>
  </section>

  <!-- ============ DAFTAR GEMPA M5+ ============ -->
  <section class="mb-4" id="daftar">
    <h2 class="h5 fw-bold border-bottom border-2 pb-2"><i class="bi bi-list-ul"></i> Gempa M 5,0+ Terkini</h2>
    <div class="kartu table-responsive">
      <table class="table table-sm align-middle mb-0 tabel-gempa">
        <thead class="table-light">
          <tr><th>Waktu</th><th>Mag.</th><th>Kedalaman</th><th>Wilayah</th><th>Jarak</th></tr>
        </thead>
        <tbody id="tabelGempa">
          <tr><td colspan="5" class="text-center text-muted">Memuat…</td></tr>
        </tbody>
      </table>
    </div>
    <div class="small text-muted mt-1">Klik baris untuk melihat lokasinya di peta.</div>
  </section>

  <!-- ============ STATISTIK (tahap berikutnya) ============ -->
  <section class="mb-4" id="statistik">
    <h2 class="h5 fw-bold border-bottom border-2 pb-2"><i class="bi bi-bar-chart-line"></i> Statistik Gempa</h2>

    <!-- Filter rentang tanggal -->
    <div class="kartu p-3 mb-3">
      <div class="d-flex flex-wrap gap-2 align-items-end">
        <div class="btn-group" role="group" aria-label="Rentang cepat">
          <button type="button" class="btn btn-sm btn-outline-dark btn-preset" data-hari="7">7 hari</button>
          <button type="button" class="btn btn-sm btn-outline-dark btn-preset" data-hari="30">30 hari</button>
          <button type="button" class="btn btn-sm btn-outline-dark btn-preset" data-hari="90">90 hari</button>
          <button type="button" class="btn btn-sm btn-outline-dark btn-preset" data-hari="365">1 tahun</button>
        </div>
        <form class="d-flex flex-wrap gap-2 align-items-end" id="formStat">
          <div>
            <label class="form-label small mb-0" for="statDari">Dari</label>
            <input class="form-control form-control-sm" type="date" id="statDari" required>
          </div>
          <div>
            <label class="form-label small mb-0" for="statSampai">Sampai</label>
            <input class="form-control form-control-sm" type="date" id="statSampai" required>
          </div>
          <button class="btn btn-sm btn-dark" type="submit">Terapkan</button>
        </form>
        <div class="small text-muted ms-md-auto" id="statStatus" aria-live="polite"></div>
      </div>
    </div>

    <div class="row g-2 mb-3" id="statRingkasan"></div>

    <div id="statKosong" class="kartu p-4 text-center text-muted d-none">
      Tidak ada data gempa pada rentang tanggal ini.
    </div>

    <div id="statGrafik" class="row g-3">
      <div class="col-12">
        <div class="kartu p-3">
          <div class="fw-bold mb-2">Frekuensi &amp; magnitudo maksimum</div>
          <div class="kotak-grafik"><canvas id="chartWaktu" aria-label="Grafik frekuensi gempa" role="img"></canvas></div>
        </div>
      </div>
      <div class="col-lg-4">
        <div class="kartu p-3 h-100">
          <div class="fw-bold mb-2">Distribusi magnitudo</div>
          <div class="kotak-grafik"><canvas id="chartMag" aria-label="Distribusi magnitudo" role="img"></canvas></div>
        </div>
      </div>
      <div class="col-lg-4">
        <div class="kartu p-3 h-100">
          <div class="fw-bold mb-2">Distribusi kedalaman</div>
          <div class="kotak-grafik"><canvas id="chartDalam" aria-label="Distribusi kedalaman" role="img"></canvas></div>
        </div>
      </div>
      <div class="col-lg-4">
        <div class="kartu p-3 h-100">
          <div class="fw-bold mb-2">5 lokasi teratas</div>
          <div class="kotak-grafik"><canvas id="chartLokasi" aria-label="Lima lokasi dengan gempa terbanyak" role="img"></canvas></div>
        </div>
      </div>
    </div>

    <div class="small text-muted mt-2" id="statCatatan"></div>
  </section>

  <!-- ============ EDUKASI (di bawah, setelah info urgent) ============ -->
  <section class="mb-4" id="edukasi">
    <h2 class="h5 fw-bold border-bottom border-2 pb-2"><i class="bi bi-book"></i> Edukasi Gempa Bumi</h2>
    <div class="row g-3">
      <?php
      $edu = [
        ['Apa itu Gempa Bumi?',  'Pengertian dan cara terjadinya getaran bumi.',      '#definisi'],
        ['Penyebab',             'Tektonik, vulkanik, runtuhan, dan aktivitas manusia.', '#penyebab'],
        ['Dampak',               'Dampak bagi manusia, ekonomi, serta pemulihan.',    '#dampak'],
        ['Penanggulangan',       'Sebelum, saat, dan sesudah gempa.',                 '#penanggulangan'],
        ['Mitigasi',             'Upaya mengurangi risiko dan kerugian.',             '#mitigasi'],
        ['Tas Siaga & Mitos',    'Checklist tas siaga, skala gempa, mitos vs fakta.', '#tas-siaga'],
      ];
      foreach ($edu as $e): ?>
        <div class="col-md-6 col-lg-4">
          <a class="text-decoration-none text-dark" href="edukasi.php<?= $e[2] ?>">
            <div class="kartu p-3 h-100">
              <div class="fw-bold"><?= htmlspecialchars($e[0]) ?></div>
              <div class="small text-muted"><?= htmlspecialchars($e[1]) ?></div>
            </div>
          </a>
        </div>
      <?php endforeach; ?>
    </div>
  </section>

</main>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<?php require __DIR__ . '/includes/footer.php'; ?>
