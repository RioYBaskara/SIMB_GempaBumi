<?php
/**
 * Form lapor orang hilang (tanpa login).
 * Perlindungan: CSRF, honeypot, rate limit per IP, validasi server, re-encode foto,
 * dan semua laporan masuk status "menunggu" sampai diverifikasi admin.
 */
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../includes/fungsi.php';

$errors = [];
$old    = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old = $_POST;
    $in  = fn(string $k, int $maks = 255): string => mb_substr(trim((string) ($_POST[$k] ?? '')), 0, $maks);

    if (!csrf_valid()) {
        $errors[] = 'Sesi formulir kedaluwarsa. Muat ulang halaman lalu coba lagi.';
    } elseif (!empty($_POST['website'])) {
        // Honeypot terisi = bot. Pura-pura sukses, jangan simpan apa pun.
        header('Location: ../index.php');
        exit;
    } elseif (batas_laporan_terlampaui($koneksi, ip_klien())) {
        $errors[] = 'Terlalu banyak laporan dari jaringan Anda dalam 1 jam terakhir. Coba lagi nanti, atau hubungi Basarnas 115.';
    } else {
        $nama      = $in('nama_lengkap', 100);
        $usiaRaw   = $in('usia', 3);
        $jk        = $in('jenis_kelamin', 1);
        $ciri      = $in('ciri_ciri', 1000);
        $lokasi    = $in('lokasi_terakhir');
        $domisili  = $in('domisili');
        $pelapor   = $in('nama_pelapor', 100);
        $hubungan  = $in('hubungan', 50);
        $kontak    = $in('kontak_pelapor', 30);

        if ($nama === '')                    $errors[] = 'Nama lengkap wajib diisi.';
        if (!in_array($jk, ['L', 'P'], true)) $errors[] = 'Pilih jenis kelamin.';
        if ($lokasi === '')                  $errors[] = 'Lokasi terakhir terlihat wajib diisi.';
        if ($domisili === '')                $errors[] = 'Domisili / alamat asal wajib diisi.';
        if ($pelapor === '')                 $errors[] = 'Nama pelapor wajib diisi.';
        if (!preg_match('/^\+?[0-9 \-]{8,20}$/', $kontak)) {
            $errors[] = 'Nomor kontak tidak valid (8-20 digit, contoh 0812-3456-7890).';
        }
        if (empty($_POST['setuju'])) {
            $errors[] = 'Anda harus menyetujui bahwa data ini akan ditampilkan untuk publik setelah diverifikasi.';
        }

        $usia = null;
        if ($usiaRaw !== '') {
            if (!ctype_digit($usiaRaw) || (int) $usiaRaw > 120) {
                $errors[] = 'Usia harus berupa angka 0-120.';
            } else {
                $usia = (int) $usiaRaw;
            }
        }

        // Koordinat opsional (dari peta)
        $lat = $lng = null;
        if (($_POST['lintang'] ?? '') !== '' && ($_POST['bujur'] ?? '') !== '') {
            $la = filter_var($_POST['lintang'], FILTER_VALIDATE_FLOAT);
            $lo = filter_var($_POST['bujur'], FILTER_VALIDATE_FLOAT);
            if ($la !== false && $lo !== false && abs($la) <= 90 && abs($lo) <= 180) {
                $lat = $la; $lng = $lo;
            }
        }

        // Waktu terakhir terlihat (opsional)
        $waktu = null;
        if (!empty($_POST['waktu_terakhir'])) {
            $dt = DateTime::createFromFormat('Y-m-d\TH:i', (string) $_POST['waktu_terakhir']);
            if ($dt && $dt <= new DateTime('+5 minutes')) {
                $waktu = $dt->format('Y-m-d H:i:s');
            } else {
                $errors[] = 'Waktu terakhir terlihat tidak valid atau berada di masa depan.';
            }
        }

        $foto = null;
        if (!$errors) {
            $hf = simpan_foto($_FILES['foto'] ?? []);
            if (!$hf['ok']) { $errors[] = $hf['pesan']; } else { $foto = $hf['nama']; }
        }

        if (!$errors) {
            $ip = ip_klien();
            $sql = "INSERT INTO orang_hilang
                (kode_laporan, token_kelola, nama_lengkap, usia, jenis_kelamin, ciri_ciri, foto,
                 lokasi_terakhir, lintang_terakhir, bujur_terakhir, waktu_terakhir, domisili,
                 nama_pelapor, hubungan, kontak_pelapor, ip_pelapor)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
            $stmt = $koneksi->prepare($sql);

            $berhasil = false;
            for ($i = 0; $i < 5 && !$berhasil; $i++) {      // ulangi bila kode kebetulan bentrok
                $kode  = kode_laporan_baru();
                $token = token_baru();
                $cirik = ($ciri === '') ? null : $ciri;
                $hub   = ($hubungan === '') ? null : $hubungan;
                $stmt->bind_param('sssissssddssssss',
                    $kode, $token, $nama, $usia, $jk, $cirik, $foto,
                    $lokasi, $lat, $lng, $waktu, $domisili,
                    $pelapor, $hub, $kontak, $ip);
                try {
                    $stmt->execute();
                    $berhasil = true;
                } catch (mysqli_sql_exception $ex) {
                    if ($ex->getCode() !== 1062) { throw $ex; }
                }
            }
            $stmt->close();

            if ($berhasil) {
                $_SESSION['lapor_sukses'] = ['kode' => $kode, 'token' => $token];
                header('Location: sukses.php');
                exit;
            }
            $errors[] = 'Terjadi kesalahan saat menyimpan laporan. Silakan coba lagi.';
        }
    }
}

$val = fn(string $k): string => e($old[$k] ?? '');

$judul   = 'Laporkan Orang Hilang - SIMB';
$base    = '../';
$aktif   = 'orang-hilang';
$tanpaAlert = true;
$extraJs = ['assets/js/lapor.js'];
require __DIR__ . '/../includes/header.php';
?>

<main class="container-xl py-3" style="max-width: 900px;">
  <h1 class="h4 fw-bold"><i class="bi bi-person-plus text-danger"></i> Laporkan Orang Hilang</h1>
  <p class="text-muted">
    Tidak perlu login. Laporan akan <strong>diperiksa admin</strong> sebelum tampil untuk publik.
    Jika situasi mendesak, hubungi <a href="tel:115">Basarnas 115</a> atau <a href="tel:117">BNPB 117</a> sekarang.
  </p>

  <?php if ($errors): ?>
    <div class="alert alert-danger">
      <strong>Periksa kembali isian Anda:</strong>
      <ul class="mb-0"><?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul>
    </div>
  <?php endif; ?>

  <form method="post" enctype="multipart/form-data" class="kartu p-3 p-md-4" novalidate>
    <?= csrf_field() ?>
    <!-- Honeypot: disembunyikan dari manusia, bot biasanya mengisinya -->
    <div style="position:absolute;left:-9999px;" aria-hidden="true">
      <label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label>
    </div>

    <h2 class="h6 fw-bold border-bottom pb-2">1. Data orang yang dicari</h2>
    <div class="row g-3 mb-4">
      <div class="col-md-8">
        <label class="form-label" for="nama_lengkap">Nama lengkap <span class="text-danger">*</span></label>
        <input class="form-control" id="nama_lengkap" name="nama_lengkap" maxlength="100" required value="<?= $val('nama_lengkap') ?>">
      </div>
      <div class="col-6 col-md-2">
        <label class="form-label" for="usia">Usia</label>
        <input class="form-control" id="usia" name="usia" type="number" min="0" max="120" inputmode="numeric" value="<?= $val('usia') ?>">
      </div>
      <div class="col-6 col-md-2">
        <label class="form-label" for="jenis_kelamin">Jenis kelamin <span class="text-danger">*</span></label>
        <select class="form-select" id="jenis_kelamin" name="jenis_kelamin" required>
          <option value="">Pilih</option>
          <option value="L" <?= ($old['jenis_kelamin'] ?? '') === 'L' ? 'selected' : '' ?>>Laki-laki</option>
          <option value="P" <?= ($old['jenis_kelamin'] ?? '') === 'P' ? 'selected' : '' ?>>Perempuan</option>
        </select>
      </div>
      <div class="col-12">
        <label class="form-label" for="ciri_ciri">Ciri-ciri</label>
        <textarea class="form-control" id="ciri_ciri" name="ciri_ciri" rows="3" maxlength="1000"
          placeholder="Tinggi badan, warna kulit/rambut, pakaian terakhir, tanda khusus (tahi lalat, bekas luka), kondisi kesehatan…"><?= $val('ciri_ciri') ?></textarea>
      </div>
      <div class="col-12">
        <label class="form-label" for="foto">Foto (JPG/PNG/WebP, maks 2 MB)</label>
        <input class="form-control" id="foto" name="foto" type="file" accept="image/jpeg,image/png,image/webp">
        <div class="form-text">Foto yang jelas wajah membantu pencarian. Data lokasi (GPS) pada foto otomatis dihapus.</div>
      </div>
    </div>

    <h2 class="h6 fw-bold border-bottom pb-2">2. Lokasi &amp; domisili</h2>
    <div class="row g-3 mb-4">
      <div class="col-md-8">
        <label class="form-label" for="lokasi_terakhir">Lokasi terakhir terlihat <span class="text-danger">*</span></label>
        <input class="form-control" id="lokasi_terakhir" name="lokasi_terakhir" maxlength="255" required
               placeholder="Nama tempat / desa / kecamatan / kota" value="<?= $val('lokasi_terakhir') ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label" for="waktu_terakhir">Waktu terakhir terlihat</label>
        <input class="form-control" id="waktu_terakhir" name="waktu_terakhir" type="datetime-local" value="<?= $val('waktu_terakhir') ?>">
      </div>
      <div class="col-12">
        <label class="form-label">Tandai di peta (opsional)</label>
        <div id="petaLapor" style="height:280px;border:2px solid #cfd4da;border-radius:.5rem;"></div>
        <div class="d-flex flex-wrap gap-2 mt-2">
          <button type="button" class="btn btn-sm btn-outline-dark" id="btnGps"><i class="bi bi-crosshair"></i> Gunakan lokasi saya</button>
          <button type="button" class="btn btn-sm btn-outline-secondary" id="btnHapusTitik">Hapus titik</button>
          <span class="small text-muted align-self-center">Klik peta untuk menandai posisi.</span>
        </div>
        <input type="hidden" id="lintang" name="lintang" value="<?= $val('lintang') ?>">
        <input type="hidden" id="bujur"   name="bujur"   value="<?= $val('bujur') ?>">
      </div>
      <div class="col-12">
        <label class="form-label" for="domisili">Domisili / alamat asal <span class="text-danger">*</span></label>
        <input class="form-control" id="domisili" name="domisili" maxlength="255" required value="<?= $val('domisili') ?>">
      </div>
    </div>

    <h2 class="h6 fw-bold border-bottom pb-2">3. Data pelapor</h2>
    <div class="row g-3 mb-4">
      <div class="col-md-5">
        <label class="form-label" for="nama_pelapor">Nama pelapor <span class="text-danger">*</span></label>
        <input class="form-control" id="nama_pelapor" name="nama_pelapor" maxlength="100" required value="<?= $val('nama_pelapor') ?>">
      </div>
      <div class="col-md-3">
        <label class="form-label" for="hubungan">Hubungan</label>
        <input class="form-control" id="hubungan" name="hubungan" maxlength="50" placeholder="Ayah, anak, teman…" value="<?= $val('hubungan') ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label" for="kontak_pelapor">No. HP / WhatsApp <span class="text-danger">*</span></label>
        <input class="form-control" id="kontak_pelapor" name="kontak_pelapor" type="tel" maxlength="30" required
               placeholder="0812-3456-7890" value="<?= $val('kontak_pelapor') ?>">
      </div>
    </div>

    <div class="form-check mb-3">
      <input class="form-check-input" type="checkbox" id="setuju" name="setuju" value="1" <?= !empty($old['setuju']) ? 'checked' : '' ?> required>
      <label class="form-check-label" for="setuju">
        Saya menyatakan data ini benar dan menyetujui <strong>data (termasuk foto dan nomor kontak)
        ditampilkan untuk publik</strong> setelah diverifikasi, demi membantu pencarian.
      </label>
    </div>

    <button class="btn btn-danger btn-lg fw-bold w-100" type="submit"><i class="bi bi-send"></i> Kirim Laporan</button>
  </form>
</main>

<?php require __DIR__ . '/../includes/footer.php'; ?>
