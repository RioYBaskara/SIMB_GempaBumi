<?php
/**
 * api-info.php — Halaman dokumentasi API data gempa BMKG
 * Menjelaskan sumber data, endpoint, contoh respons, dan cara penggunaan
 * Tersedia tombol "Coba Endpoint" live
 */

require_once __DIR__ . '/config/koneksi.php';
require_once __DIR__ . '/includes/fungsi.php';

$judul_halaman = 'Dokumentasi API';
$deskripsi     = 'Dokumentasi endpoint API data gempa bumi BMKG dan cara penggunaannya';
require_once __DIR__ . '/includes/header.php';
?>

<script>var BASE_URL = '<?= BASE_URL ?>';</script>

<div class="container py-4">
    <div class="mb-4">
        <h1 class="h3 fw-bold text-primary-dark mb-1">🔌 Dokumentasi API Data Gempa</h1>
        <p class="text-muted">
            Informasi tentang endpoint API data gempa bumi yang digunakan SIMB GempaBumi,
            bersumber dari BMKG (Badan Meteorologi, Klimatologi, dan Geofisika) Indonesia.
        </p>
    </div>

    <!-- Penjelasan sumber data -->
    <div class="card mb-4">
        <div class="card-header">📡 Sumber Data Gempa Bumi</div>
        <div class="card-body">
            <div class="row g-4">
                <div class="col-md-7">
                    <p>Data gempa bumi yang ditampilkan di SIMB GempaBumi berasal dari
                    <strong>Open Data BMKG</strong> — layanan data publik resmi yang disediakan oleh
                    <a href="https://www.bmkg.go.id" target="_blank" rel="noopener noreferrer">
                    Badan Meteorologi, Klimatologi, dan Geofisika (BMKG)</a>.</p>
                    <ul>
                        <li>✅ <strong>Data resmi pemerintah Indonesia</strong> — akurat dan terpercaya</li>
                        <li>✅ <strong>Diperbarui secara real-time</strong> — setiap gempa signifikan langsung tersedia</li>
                        <li>✅ <strong>Tersedia gratis</strong> — tidak memerlukan API key</li>
                        <li>✅ <strong>Format JSON</strong> — mudah dikonsumsi oleh aplikasi apa pun</li>
                    </ul>
                    <p>Referensi: <a href="https://data.bmkg.go.id/gempabumi/" target="_blank" rel="noopener noreferrer">
                    data.bmkg.go.id/gempabumi/</a></p>
                </div>
                <div class="col-md-5">
                    <div class="p-3 rounded" style="background:#e8f0f9">
                        <h6 class="fw-bold text-primary-dark">📌 Field yang Tersedia</h6>
                        <table class="table table-sm table-borderless mb-0 small">
                            <tr><td class="fw-mono text-primary">Tanggal</td><td>Tanggal kejadian</td></tr>
                            <tr><td class="fw-mono text-primary">Jam</td><td>Waktu (WIB)</td></tr>
                            <tr><td class="fw-mono text-primary">DateTime</td><td>ISO 8601 datetime</td></tr>
                            <tr><td class="fw-mono text-primary">Coordinates</td><td>lat,lon</td></tr>
                            <tr><td class="fw-mono text-primary">Lintang</td><td>Lintang (LS/LU)</td></tr>
                            <tr><td class="fw-mono text-primary">Bujur</td><td>Bujur (BT/BB)</td></tr>
                            <tr><td class="fw-mono text-primary">Magnitude</td><td>Skala Richter</td></tr>
                            <tr><td class="fw-mono text-primary">Kedalaman</td><td>Dalam km</td></tr>
                            <tr><td class="fw-mono text-primary">Wilayah</td><td>Deskripsi lokasi</td></tr>
                            <tr><td class="fw-mono text-primary">Potensi</td><td>Potensi tsunami</td></tr>
                            <tr><td class="fw-mono text-primary">Dirasakan</td><td>Wilayah yang merasakan</td></tr>
                            <tr><td class="fw-mono text-primary">Shakemap</td><td>Nama file peta guncangan</td></tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- DAFTAR ENDPOINT BMKG (Sumber langsung)                        -->
    <!-- ============================================================ -->
    <h5 class="section-title">🌐 Endpoint BMKG (Sumber Asli)</h5>
    <div class="row g-3 mb-4">

        <!-- Autogempa -->
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center py-2">
                    <span class="small fw-bold">Gempa Terbaru (1 data)</span>
                    <span class="badge bg-success">GET</span>
                </div>
                <div class="card-body">
                    <div class="kode-snippet">https://data.bmkg.go.id/DataMKG/TEWS/autogempa.json</div>
                    <p class="small text-muted mt-2 mb-0">
                        Mengembalikan 1 data gempa paling terbaru yang terdeteksi BMKG.
                        Cocok untuk menampilkan info gempa terakhir di beranda.
                    </p>
                </div>
            </div>
        </div>

        <!-- Gempa terkini -->
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center py-2">
                    <span class="small fw-bold">15 Gempa M 5.0+ Terkini</span>
                    <span class="badge bg-success">GET</span>
                </div>
                <div class="card-body">
                    <div class="kode-snippet">https://data.bmkg.go.id/DataMKG/TEWS/gempaterkini.json</div>
                    <p class="small text-muted mt-2 mb-0">
                        Mengembalikan 15 gempa dengan magnitudo ≥ 5.0 terbaru. Cocok untuk peta dan tabel pemantauan.
                    </p>
                </div>
            </div>
        </div>

        <!-- Gempa dirasakan -->
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center py-2">
                    <span class="small fw-bold">15 Gempa Dirasakan</span>
                    <span class="badge bg-success">GET</span>
                </div>
                <div class="card-body">
                    <div class="kode-snippet">https://data.bmkg.go.id/DataMKG/TEWS/gempadirasakan.json</div>
                    <p class="small text-muted mt-2 mb-0">
                        15 gempa yang dilaporkan dirasakan oleh masyarakat. Mengandung field <code>Dirasakan</code>.
                    </p>
                </div>
            </div>
        </div>

        <!-- Shakemap -->
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center py-2">
                    <span class="small fw-bold">Peta Guncangan (Shakemap)</span>
                    <span class="badge bg-info text-dark">GET</span>
                </div>
                <div class="card-body">
                    <div class="kode-snippet">https://data.bmkg.go.id/DataMKG/TEWS/{nama_file}</div>
                    <p class="small text-muted mt-2 mb-0">
                        Gambar JPG peta guncangan seismik. Nama file diambil dari field <code>Shakemap</code>
                        pada respons JSON gempa.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- ENDPOINT INTERNAL (dari SIMB GempaBumi)                       -->
    <!-- ============================================================ -->
    <h5 class="section-title">🔧 Endpoint Internal SIMB GempaBumi</h5>
    <p class="text-muted small mb-3">
        Endpoint berikut dibuat oleh SIMB GempaBumi sebagai proxy dengan fitur cache, fallback database,
        dan format respons yang konsisten. Gunakan endpoint ini jika ingin mengintegrasikan data gempa
        ke aplikasi lain dengan keandalan lebih tinggi.
    </p>

    <div class="row g-3 mb-4">

        <?php
        // Daftar endpoint internal
        $endpoints = [
            [
                'judul'   => 'Gempa Terbaru (1 data)',
                'url'     => '/api/gempa_terbaru.php',
                'metode'  => 'GET',
                'deskripsi' => 'Mengembalikan 1 gempa terbaru dari BMKG (cache 60 detik). Fallback ke database jika BMKG tidak bisa diakses.',
                'contoh'  => '{
  "gempa": {
    "Tanggal": "07-Nov-24",
    "Jam": "14:32:00 WIB",
    "DateTime": "2024-11-07T14:32:00+07:00",
    "Coordinates": "-8.5234,115.3456",
    "Lintang": "-8.52",
    "Bujur": "115.35",
    "Magnitude": "6.2",
    "Kedalaman": "15 km",
    "Wilayah": "27 km BaratLaut GIANYAR-BALI",
    "Potensi": "Tidak berpotensi tsunami",
    "Dirasakan": "Dirasakan (IV MMI) di Gianyar",
    "Shakemap": "20241107143200.mmi.jpg"
  },
  "sumber": "bmkg",
  "pesan": ""
}',
            ],
            [
                'judul'   => 'Daftar Gempa M 5.0+',
                'url'     => '/api/gempa_terkini.php',
                'metode'  => 'GET',
                'deskripsi' => '15 gempa M 5.0+ terbaru (cache 5 menit). Array gempa dalam field "gempa".',
                'contoh'  => '{
  "gempa": [ {...}, {...} ],
  "sumber": "bmkg",
  "total": 15,
  "pesan": ""
}',
            ],
            [
                'judul'   => 'Daftar Gempa Dirasakan',
                'url'     => '/api/gempa_dirasakan.php',
                'metode'  => 'GET',
                'deskripsi' => '15 gempa yang dirasakan masyarakat (cache 5 menit).',
                'contoh'  => '{
  "gempa": [ {...}, {...} ],
  "sumber": "bmkg",
  "total": 15,
  "pesan": ""
}',
            ],
            [
                'judul'   => 'Statistik Grafik',
                'url'     => '/api/statistik.php',
                'metode'  => 'GET',
                'deskripsi' => 'Data statistik untuk Chart.js: jumlah per hari, distribusi magnitudo, distribusi kedalaman, tren, dan ringkasan.',
                'contoh'  => '{
  "per_hari": { "label": [...], "data": [...] },
  "distribusi_mag": { "label": [...], "data": [...], "warna": [...] },
  "kedalaman": { "label": [...], "data": [...], "warna": [...] },
  "tren": { "label": [...], "rata": [...], "maks": [...] },
  "ringkasan": {
    "total": 120, "terbesar": 6.5,
    "rata_rata": 4.3, "hari_ini": 3
  }
}',
            ],
            [
                'judul'   => 'Cari Gambar Edukasi',
                'url'     => '/api/cari_gambar.php?q=gempa+bumi&bagian=definisi',
                'metode'  => 'GET',
                'deskripsi' => 'Mencari gambar via Google Custom Search (cache 7 hari). Parameter wajib: q (kata kunci), bagian (definisi/penyebab/dampak/penanggulangan/mitigasi).',
                'contoh'  => '{
  "gambar": [
    {
      "url": "https://example.com/image.jpg",
      "thumbnail": "https://example.com/thumb.jpg",
      "judul": "Ilustrasi Gempa Bumi",
      "sumber": "example.com",
      "link_sumber": "https://example.com/halaman"
    }
  ],
  "sumber": "google",
  "total": 3
}',
            ],
        ];
        ?>

        <?php foreach ($endpoints as $i => $ep): ?>
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <span class="badge bg-success me-2"><?= e($ep['metode']) ?></span>
                        <strong><?= e($ep['judul']) ?></strong>
                    </div>
                    <button class="btn btn-sm btn-outline-primary"
                            onclick="cobaEndpoint('<?= e($ep['url']) ?>', 'output-<?= $i ?>')">
                        ▶ Coba Endpoint
                    </button>
                </div>
                <div class="card-body">
                    <div class="kode-snippet mb-2"><?= e(BASE_URL . $ep['url']) ?></div>
                    <p class="small text-muted mb-3"><?= e($ep['deskripsi']) ?></p>

                    <!-- Contoh respons -->
                    <div class="mb-2">
                        <small class="text-muted fw-bold">Contoh Respons JSON:</small>
                    </div>
                    <div class="kode-snippet"><?= e($ep['contoh']) ?></div>

                    <!-- Area output live -->
                    <div class="mt-3" id="output-<?= $i ?>-wrapper" style="display:none">
                        <small class="text-muted fw-bold">Output Live:</small>
                        <pre id="output-<?= $i ?>" class="kode-snippet mt-1"
                             style="max-height:300px; overflow-y:auto; white-space:pre-wrap"></pre>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Cara menggunakan dengan JavaScript -->
    <div class="card mb-4">
        <div class="card-header">💻 Cara Menggunakan dengan JavaScript (fetch)</div>
        <div class="card-body">
            <p class="small text-muted mb-2">Contoh memanggil endpoint gempa terbaru dari halaman web:</p>
            <div class="kode-snippet">// Ambil gempa terbaru
fetch('/SIMB_GempaBumi/api/gempa_terbaru.php')
  .then(response => response.json())
  .then(data => {
    if (data.gempa) {
      console.log('Magnitudo:', data.gempa.Magnitude);
      console.log('Wilayah:', data.gempa.Wilayah);
      console.log('Waktu:', data.gempa.DateTime);

      // Cek pesan fallback
      if (data.pesan) {
        console.warn(data.pesan); // "Data dari database" bukan BMKG langsung
      }
    }
  })
  .catch(err => console.error('Gagal:', err));</div>
        </div>
    </div>

    <!-- Cara menggunakan dengan PHP -->
    <div class="card mb-4">
        <div class="card-header">🐘 Cara Menggunakan dengan PHP (cURL)</div>
        <div class="card-body">
            <div class="kode-snippet">&lt;?php
// Ambil data gempa dari BMKG langsung (atau gunakan endpoint internal)
$ch = curl_init('https://data.bmkg.go.id/DataMKG/TEWS/autogempa.json');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 10,
    CURLOPT_USERAGENT      => 'MyApp/1.0',
    CURLOPT_SSL_VERIFYPEER => false, // Hanya untuk dev lokal
]);
$raw = curl_exec($ch);
curl_close($ch);

$data = json_decode($raw, true);
$gempa = $data['Infogempa']['gempa'] ?? null;

if ($gempa) {
    echo "Gempa terbaru: M" . $gempa['Magnitude'];
    echo " di " . $gempa['Wilayah'];
}</div>
        </div>
    </div>

    <!-- Catatan penggunaan -->
    <div class="alert alert-info small">
        <strong>📌 Catatan Penggunaan:</strong>
        <ul class="mb-0 mt-1">
            <li>Data BMKG bersifat publik dan dapat digunakan bebas, tetapi mohon cantumkan sumber data BMKG dalam aplikasi Anda.</li>
            <li>Gunakan cache agar tidak membebani server BMKG — aplikasi ini menggunakan cache minimal 60 detik.</li>
            <li>Endpoint internal SIMB GempaBumi hanya untuk lingkungan lokal (localhost). Untuk produksi, akses langsung API BMKG lebih dianjurkan.</li>
            <li>BMKG juga menyediakan RSS Feed dan format lain. Kunjungi <a href="https://data.bmkg.go.id" target="_blank" rel="noopener noreferrer">data.bmkg.go.id</a> untuk informasi lengkap.</li>
        </ul>
    </div>

</div><!-- /container -->

<?php
$skrip_tambahan = '<script>
// Override cobaEndpoint agar tampilkan wrapper
function cobaEndpoint(url, idOutput) {
    var wrapper = document.getElementById(idOutput + "-wrapper");
    var el = document.getElementById(idOutput);
    if (!el) return;
    if (wrapper) wrapper.style.display = "block";
    el.textContent = "Memuat...";
    fetch(BASE_URL + url)
        .then(function(r) { return r.json(); })
        .then(function(data) {
            el.textContent = JSON.stringify(data, null, 2);
        })
        .catch(function(err) {
            el.textContent = "Gagal: " + err.message;
        });
}
</script>';
require_once __DIR__ . '/includes/footer.php';
?>
