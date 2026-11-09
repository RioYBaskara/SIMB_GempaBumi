<?php
/**
 * Halaman Edukasi Gempa Bumi
 * Definisi, penyebab, dampak, penanggulangan, mitigasi, skala magnitudo & MMI,
 * mitos vs fakta, dan checklist tas siaga bencana (interaktif).
 */
require_once __DIR__ . '/includes/fungsi.php';

$judul = 'Edukasi Gempa Bumi - SIMB';
$base  = '';
$aktif = 'edukasi';
$tanpaAlert = true;
$extraJs = ['assets/js/edukasi.js'];

/* ---------- Data: skala MMI (dengan padanan SIG-BMKG) ---------- */
$mmi = [
    ['I',   'Tidak dirasakan, kecuali dalam keadaan luar biasa oleh sedikit orang.', 'I (putih)'],
    ['II',  'Dirasakan sedikit orang; benda ringan yang tergantung bergoyang.', 'I (putih)'],
    ['III', 'Terasa nyata di dalam rumah, seperti ada truk lewat.', 'II (hijau)'],
    ['IV',  'Siang hari dirasakan banyak orang di dalam rumah; gerabah dan jendela/pintu berderik.', 'II (hijau)'],
    ['V',   'Dirasakan hampir semua orang; banyak yang terbangun, barang kecil terpelanting, tiang tampak bergoyang.', 'II (hijau)'],
    ['VI',  'Dirasakan semua orang; banyak yang berlari keluar, plester dinding retak/jatuh, kerusakan ringan.', 'III (kuning)'],
    ['VII', 'Semua orang keluar rumah; kerusakan ringan pada bangunan kuat, sedang pada bangunan biasa.', 'IV (jingga)'],
    ['VIII', 'Kerusakan pada bangunan biasa, dinding dapat roboh; cerobong dan monumen runtuh.', 'IV (jingga)'],
    ['IX',  'Kerusakan berat; rangka bangunan miring/bergeser, pondasi rusak, pipa bawah tanah putus.', 'V (merah)'],
    ['X',   'Sebagian besar bangunan rusak parah; tanah retak, longsor terjadi.', 'V (merah)'],
    ['XI',  'Hampir semua bangunan hancur; rel melengkung, jaringan pipa tidak berfungsi.', 'V (merah)'],
    ['XII', 'Kerusakan total; gelombang tampak di permukaan tanah, benda terlempar.', 'V (merah)'],
];

/* ---------- Data: magnitudo ---------- */
$magnitudo = [
    ['< 3,0',     'Umumnya tidak dirasakan, hanya terekam alat.'],
    ['3,0 - 3,9', 'Sering dirasakan di dekat pusat gempa, jarang menimbulkan kerusakan.'],
    ['4,0 - 4,9', 'Getaran nyata dan barang bergoyang; kerusakan kecil mungkin terjadi.'],
    ['5,0 - 5,9', 'Dapat merusak bangunan yang lemah atau tidak tahan gempa di sekitar pusat gempa.'],
    ['6,0 - 6,9', 'Dapat merusak bangunan di area yang cukup luas, terutama bila dangkal dan dekat permukiman.'],
    ['7,0 ke atas', 'Gempa besar; berpotensi merusak luas dan, bila terjadi di laut, memicu tsunami.'],
];

/* ---------- Data: mitos vs fakta ---------- */
$mitos = [
    ['Gempa bumi bisa diprediksi kapan, di mana, dan sebesar apa.',
     'Sampai saat ini belum ada teknologi yang dapat memprediksi waktu gempa secara pasti. Yang tersedia adalah peta daerah rawan dan sistem peringatan dini tsunami. Pesan berantai berisi "tanggal gempa besar" adalah hoaks.'],
    ['Berdiri di ambang pintu adalah tempat paling aman.',
     'Anjuran ini berasal dari konstruksi rumah tradisional lama. Pada bangunan modern, ambang pintu tidak lebih kuat dari bagian lain. Lakukan Drop, Cover, Hold On: merunduk, berlindung di bawah meja kokoh, dan berpegangan.'],
    ['Lari keluar secepat mungkin saat gempa terjadi.',
     'Saat guncangan, berlari justru berisiko tertimpa reruntuhan atau pecahan kaca. Berlindung dulu, lalu keluar menuju area terbuka setelah guncangan berhenti.'],
    ['Lift aman dipakai saat gempa.',
     'Jangan memakai lift saat gempa atau setelahnya. Listrik dapat padam dan lift bisa terjebak. Gunakan tangga.'],
    ['Gempa kecil mencegah terjadinya gempa besar.',
     'Gempa kecil tidak melepaskan energi sebanyak gempa besar, sehingga tidak bisa dianggap "pencegah". Gempa yang kecil kadang justru mendahului gempa yang lebih besar.'],
    ['Gempa dipengaruhi cuaca atau awan tertentu.',
     'Gempa tektonik terjadi jauh di dalam bumi dan tidak dipengaruhi cuaca, awan, maupun fase bulan.'],
    ['Hewan dapat memprediksi gempa dengan andal.',
     'Perilaku aneh hewan sering diceritakan, tetapi belum terbukti ilmiah sebagai alat prediksi yang dapat diandalkan.'],
    ['Jika guncangan terasa lemah, tidak mungkin ada tsunami.',
     'Gempa kuat di laut dapat memicu tsunami walau guncangan di darat terasa lemah. Jika Anda di pantai dan merasakan guncangan kuat atau lama, segera menuju tempat tinggi tanpa menunggu peringatan, lalu ikuti info resmi BMKG.'],
];

/* ---------- Data: checklist tas siaga ---------- */
$tas = [
    'Dokumen & uang' => [
        'Fotokopi KTP, Kartu Keluarga, ijazah, dan surat berharga (dalam plastik kedap air)',
        'Uang tunai pecahan kecil',
        'Daftar nomor telepon penting keluarga dan darurat (ditulis di kertas)',
    ],
    'Makanan & minuman' => [
        'Air minum kemasan (minimal untuk 3 hari)',
        'Makanan siap saji tahan lama (biskuit, roti kering, makanan kaleng)',
        'Alat makan sekali pakai atau sendok dan gelas lipat',
    ],
    'Kesehatan' => [
        'Kotak P3K (perban, kasa, plester, antiseptik)',
        'Obat pribadi dan resep dokter (untuk beberapa hari)',
        'Masker dan hand sanitizer',
        'Kacamata cadangan bila diperlukan',
    ],
    'Penerangan & komunikasi' => [
        'Senter dan baterai cadangan',
        'Power bank dan kabel pengisi daya',
        'Radio portabel bertenaga baterai',
        'Peluit (untuk memberi tanda bila terjebak)',
    ],
    'Pakaian & perlengkapan' => [
        'Pakaian ganti, jas hujan atau ponco',
        'Selimut tipis atau sleeping bag',
        'Sepatu tertutup yang kuat',
        'Perlengkapan kebersihan (sabun, sikat gigi, pembalut, tisu)',
    ],
    'Kebutuhan khusus' => [
        'Perlengkapan bayi (susu, popok) bila ada bayi',
        'Kebutuhan lansia atau penyandang disabilitas (obat, alat bantu)',
        'Perlengkapan hewan peliharaan bila ada',
    ],
];

require __DIR__ . '/includes/header.php';
?>

<main class="container-xl py-3" style="max-width: 1000px;">
  <h1 class="h3 fw-bold"><i class="bi bi-book text-danger"></i> Edukasi Gempa Bumi</h1>
  <p class="text-muted">Pengetahuan dasar untuk memahami gempa bumi dan bersiap menghadapinya. Bagian paling penting: <a href="#saat">apa yang dilakukan saat gempa terjadi</a>.</p>

  <!-- Navigasi cepat -->
  <nav class="d-flex flex-wrap gap-2 mb-4 no-print" aria-label="Daftar isi">
    <a class="btn btn-sm btn-outline-dark" href="#definisi">Definisi</a>
    <a class="btn btn-sm btn-outline-dark" href="#penyebab">Penyebab</a>
    <a class="btn btn-sm btn-outline-dark" href="#dampak">Dampak</a>
    <a class="btn btn-sm btn-danger" href="#penanggulangan">Penanggulangan</a>
    <a class="btn btn-sm btn-outline-dark" href="#mitigasi">Mitigasi</a>
    <a class="btn btn-sm btn-outline-dark" href="#skala">Skala Gempa</a>
    <a class="btn btn-sm btn-outline-dark" href="#mitos">Mitos vs Fakta</a>
    <a class="btn btn-sm btn-outline-dark" href="#tas-siaga">Tas Siaga</a>
  </nav>

  <!-- ================= DEFINISI ================= -->
  <section id="definisi" class="kartu p-3 p-md-4 mb-3">
    <h2 class="h5 fw-bold">1. Apa itu Gempa Bumi?</h2>
    <p>Gempa bumi adalah getaran atau guncangan di permukaan bumi akibat pelepasan energi secara tiba-tiba dari dalam bumi. Energi ini umumnya berasal dari pergeseran batuan di kerak bumi yang sudah menahan tekanan dalam waktu lama.</p>
    <div class="row g-3">
      <div class="col-md-6">
        <div class="border rounded p-3 h-100">
          <div class="fw-bold">Istilah penting</div>
          <ul class="small mb-0">
            <li><strong>Hiposenter (fokus)</strong>: titik di dalam bumi tempat gempa bermula.</li>
            <li><strong>Episenter</strong>: titik di permukaan tepat di atas hiposenter.</li>
            <li><strong>Magnitudo</strong>: ukuran kekuatan (energi) gempa di sumbernya.</li>
            <li><strong>Intensitas</strong>: tingkat guncangan dan dampak yang dirasakan di suatu tempat.</li>
          </ul>
        </div>
      </div>
      <div class="col-md-6">
        <div class="border rounded p-3 h-100">
          <div class="fw-bold">Mengapa Indonesia rawan gempa?</div>
          <p class="small mb-0">Indonesia berada di pertemuan beberapa lempeng tektonik besar (Indo-Australia, Eurasia, Pasifik, dan Filipina) dan berada di jalur <em>Cincin Api Pasifik</em>. Tumbukan antarlempeng ini menjadikan Indonesia salah satu wilayah paling aktif secara seismik di dunia.</p>
        </div>
      </div>
    </div>
  </section>

  <!-- ================= PENYEBAB ================= -->
  <section id="penyebab" class="kartu p-3 p-md-4 mb-3">
    <h2 class="h5 fw-bold">2. Penyebab Gempa Bumi</h2>
    <div class="row g-3">
      <div class="col-md-6 col-lg-3"><div class="border rounded p-3 h-100">
        <div class="fw-bold"><i class="bi bi-layers"></i> Tektonik</div>
        <p class="small mb-0">Pergerakan dan tumbukan lempeng bumi. Ini penyebab sebagian besar gempa, termasuk gempa-gempa besar yang merusak.</p></div></div>
      <div class="col-md-6 col-lg-3"><div class="border rounded p-3 h-100">
        <div class="fw-bold"><i class="bi bi-fire"></i> Vulkanik</div>
        <p class="small mb-0">Pergerakan magma dan aktivitas gunung api. Umumnya berkekuatan lebih kecil dan terbatas di sekitar gunung.</p></div></div>
      <div class="col-md-6 col-lg-3"><div class="border rounded p-3 h-100">
        <div class="fw-bold"><i class="bi bi-arrow-down-square"></i> Runtuhan</div>
        <p class="small mb-0">Runtuhnya rongga bawah tanah seperti gua kapur atau terowongan tambang. Dampaknya lokal.</p></div></div>
      <div class="col-md-6 col-lg-3"><div class="border rounded p-3 h-100">
        <div class="fw-bold"><i class="bi bi-gear"></i> Aktivitas manusia</div>
        <p class="small mb-0">Peledakan, penambangan, pengisian waduk besar, atau injeksi fluida ke dalam tanah dapat memicu getaran (biasanya kecil).</p></div></div>
    </div>
    <p class="small mt-3 mb-0"><strong>Jenis pergeseran pada patahan (sesar):</strong> sesar naik (batuan terdorong naik), sesar turun (batuan turun), dan sesar mendatar (batuan bergeser menyamping). Di zona <em>subduksi</em>, satu lempeng menunjam ke bawah lempeng lain dan dapat menghasilkan gempa besar yang memicu tsunami.</p>
  </section>

  <!-- ================= DAMPAK ================= -->
  <section id="dampak" class="kartu p-3 p-md-4 mb-3">
    <h2 class="h5 fw-bold">3. Dampak Gempa Bumi</h2>
    <div class="row g-3">
      <div class="col-md-6">
        <div class="border border-danger rounded p-3 h-100">
          <div class="fw-bold text-danger"><i class="bi bi-people"></i> Dampak terhadap manusia</div>
          <ul class="small mb-0">
            <li>Korban jiwa dan luka-luka, termasuk tertimpa reruntuhan.</li>
            <li>Kehilangan tempat tinggal dan harus mengungsi.</li>
            <li>Trauma psikologis, terutama pada anak-anak dan lansia.</li>
            <li>Masalah kesehatan di pengungsian: sanitasi buruk, penyakit menular, kekurangan air bersih.</li>
            <li>Terganggunya pendidikan dan layanan kesehatan.</li>
          </ul>
        </div>
      </div>
      <div class="col-md-6">
        <div class="border border-warning rounded p-3 h-100">
          <div class="fw-bold"><i class="bi bi-cash-coin"></i> Dampak terhadap ekonomi</div>
          <ul class="small mb-0">
            <li>Kerusakan rumah, gedung, jalan, jembatan, listrik, dan jaringan komunikasi.</li>
            <li>Usaha terhenti dan masyarakat kehilangan mata pencaharian.</li>
            <li>Biaya besar untuk tanggap darurat dan pembangunan kembali.</li>
            <li>Terganggunya rantai pasok, perdagangan, dan pariwisata.</li>
          </ul>
        </div>
      </div>
      <div class="col-md-6">
        <div class="border rounded p-3 h-100">
          <div class="fw-bold"><i class="bi bi-exclamation-triangle"></i> Bahaya ikutan</div>
          <p class="small mb-0">Gempa dapat diikuti tsunami, tanah longsor, likuefaksi (tanah berubah seperti cairan), kebakaran akibat korsleting atau kebocoran gas, serta gempa susulan.</p>
        </div>
      </div>
      <div class="col-md-6">
        <div class="border border-success rounded p-3 h-100">
          <div class="fw-bold text-success"><i class="bi bi-arrow-repeat"></i> Sisi pemulihan dan pelajaran</div>
          <p class="small mb-0">Bencana tidak pernah "bermanfaat", tetapi pengalaman pahit dapat mendorong perbaikan: rekonstruksi dengan bangunan tahan gempa, kesadaran struktural dan regulasi bangunan yang lebih ketat, pengembangan sistem peringatan dini, serta tumbuhnya solidaritas dan kesiapsiagaan masyarakat.</p>
        </div>
      </div>
    </div>
  </section>

  <!-- ================= PENANGGULANGAN ================= -->
  <section id="penanggulangan" class="kartu p-3 p-md-4 mb-3">
    <h2 class="h5 fw-bold">4. Cara Penanggulangan</h2>

    <ul class="nav nav-pills mb-3 no-print" id="tabAksi" role="tablist">
      <li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#aksi-sebelum" type="button" role="tab">Sebelum gempa</button></li>
      <li class="nav-item" role="presentation"><button class="nav-link active bg-danger" id="saat" data-bs-toggle="pill" data-bs-target="#aksi-saat" type="button" role="tab">SAAT gempa</button></li>
      <li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#aksi-sesudah" type="button" role="tab">Sesudah gempa</button></li>
    </ul>

    <div class="tab-content">
      <!-- SEBELUM -->
      <div class="tab-pane fade" id="aksi-sebelum" role="tabpanel">
        <ul>
          <li>Kenali tingkat risiko gempa di wilayah tempat tinggal, sekolah, dan tempat kerja.</li>
          <li>Siapkan <a href="#tas-siaga">tas siaga bencana</a> dan simpan di tempat yang mudah dijangkau.</li>
          <li>Buat rencana keluarga: jalur evakuasi, titik kumpul, dan cara saling menghubungi.</li>
          <li>Amankan perabot berat (lemari, rak, televisi) ke dinding; simpan barang berat di bagian bawah.</li>
          <li>Ketahui cara mematikan listrik, gas, dan air.</li>
          <li>Ikuti simulasi atau latihan evakuasi bila ada di lingkungan Anda.</li>
          <li>Simpan nomor penting dan unduh informasi resmi BMKG serta BPBD setempat.</li>
        </ul>
      </div>

      <!-- SAAT -->
      <div class="tab-pane fade show active" id="aksi-saat" role="tabpanel">
        <div class="alert alert-danger fw-bold fs-5 text-center">
          MERUNDUK &middot; BERLINDUNG &middot; BERPEGANGAN<br>
          <span class="fs-6 fw-normal">(Drop, Cover, Hold On)</span>
        </div>
        <div class="row g-3">
          <div class="col-md-6"><div class="border rounded p-3 h-100">
            <div class="fw-bold"><i class="bi bi-house"></i> Di dalam bangunan</div>
            <ul class="small mb-0">
              <li>Merunduk, berlindung di bawah meja yang kokoh, dan berpegangan pada kakinya.</li>
              <li>Jika tidak ada meja, lindungi kepala dan leher dengan tangan, merapat ke dinding dalam.</li>
              <li>Jauhi kaca, jendela, lemari, dan benda yang bisa jatuh.</li>
              <li>Jangan menggunakan lift. Jangan berlari keluar saat masih berguncang.</li>
            </ul></div></div>
          <div class="col-md-6"><div class="border rounded p-3 h-100">
            <div class="fw-bold"><i class="bi bi-tree"></i> Di luar ruangan</div>
            <ul class="small mb-0">
              <li>Menjauh dari bangunan, tiang listrik, papan reklame, dan pohon.</li>
              <li>Cari area terbuka dan lindungi kepala.</li>
            </ul></div></div>
          <div class="col-md-6"><div class="border rounded p-3 h-100">
            <div class="fw-bold"><i class="bi bi-car-front"></i> Di dalam kendaraan</div>
            <ul class="small mb-0">
              <li>Hentikan kendaraan di tempat aman, jauh dari jembatan, jalan layang, dan terowongan.</li>
              <li>Tetap di dalam kendaraan sampai guncangan berhenti.</li>
            </ul></div></div>
          <div class="col-md-6"><div class="border border-danger rounded p-3 h-100">
            <div class="fw-bold text-danger"><i class="bi bi-water"></i> Di daerah pantai</div>
            <ul class="small mb-0">
              <li>Jika guncangan <strong>kuat atau berlangsung lama</strong>, langsung menuju tempat tinggi atau lantai atas bangunan tinggi yang kokoh.</li>
              <li>Jangan menunggu sirene atau peringatan resmi, dan jangan kembali ke pantai sebelum dinyatakan aman.</li>
            </ul></div></div>
        </div>
      </div>

      <!-- SESUDAH -->
      <div class="tab-pane fade" id="aksi-sesudah" role="tabpanel">
        <ul>
          <li>Periksa kondisi diri dan orang di sekitar; beri pertolongan pertama bila perlu.</li>
          <li>Waspadai <strong>gempa susulan</strong>; siap berlindung kembali.</li>
          <li>Keluar dengan tenang lewat jalur evakuasi, pakai sepatu, dan hindari bangunan yang rusak.</li>
          <li>Bila tercium bau gas: jangan menyalakan api atau saklar, buka jendela, dan segera tinggalkan bangunan.</li>
          <li>Matikan listrik dan gas jika aman dilakukan.</li>
          <li>Gunakan pesan teks daripada telepon agar jaringan tetap tersedia untuk keadaan darurat.</li>
          <li>Ikuti informasi resmi dari BMKG, BNPB, dan BPBD. Jangan menyebarkan kabar yang belum jelas sumbernya.</li>
          <li>Kembali ke bangunan hanya setelah dinyatakan aman oleh petugas.</li>
          <li>Butuh bantuan? Hubungi <a href="tel:117">BNPB 117</a>, <a href="tel:115">Basarnas 115</a>, atau <a href="tel:119">119</a>. Mencari anggota keluarga? Gunakan <a href="orang-hilang/cari.php">fitur Cari Orang Hilang</a>.</li>
        </ul>
      </div>
    </div>
  </section>

  <!-- ================= MITIGASI ================= -->
  <section id="mitigasi" class="kartu p-3 p-md-4 mb-3">
    <h2 class="h5 fw-bold">5. Mitigasi Bencana</h2>
    <p>Mitigasi adalah upaya mengurangi risiko dan kerugian sebelum bencana terjadi. Gempa tidak dapat dicegah, tetapi korban dan kerusakan dapat dikurangi.</p>
    <div class="row g-3">
      <div class="col-md-6"><div class="border rounded p-3 h-100">
        <div class="fw-bold"><i class="bi bi-building"></i> Mitigasi struktural (fisik)</div>
        <ul class="small mb-0">
          <li>Membangun dan memperkuat bangunan sesuai standar ketahanan gempa (SNI).</li>
          <li>Memperkuat (<em>retrofit</em>) bangunan lama, sekolah, dan fasilitas umum.</li>
          <li>Menyediakan jalur dan rambu evakuasi serta tempat berkumpul yang aman.</li>
          <li>Membangun tanggul, tempat evakuasi vertikal, dan infrastruktur penahan di daerah rawan tsunami.</li>
        </ul></div></div>
      <div class="col-md-6"><div class="border rounded p-3 h-100">
        <div class="fw-bold"><i class="bi bi-mortarboard"></i> Mitigasi non-struktural</div>
        <ul class="small mb-0">
          <li>Tata ruang berbasis peta rawan bencana: tidak membangun di atas sesar aktif, lereng curam, atau tanah yang mudah mencair.</li>
          <li>Edukasi, simulasi, dan latihan evakuasi di sekolah dan masyarakat.</li>
          <li>Sistem peringatan dini dan penyebaran informasi yang cepat dan akurat.</li>
          <li>Penguatan komunitas, relawan, dan desa/kelurahan tangguh bencana.</li>
          <li>Perencanaan kontinjensi dan perlindungan seperti asuransi.</li>
        </ul></div></div>
    </div>
  </section>

  <!-- ================= SKALA ================= -->
  <section id="skala" class="kartu p-3 p-md-4 mb-3">
    <h2 class="h5 fw-bold">6. Memahami Skala Gempa</h2>

    <h3 class="h6 fw-bold mt-3">Magnitudo: kekuatan gempa di sumbernya</h3>
    <div class="table-responsive">
      <table class="table table-sm table-bordered align-middle">
        <thead class="table-light"><tr><th style="width:140px">Magnitudo</th><th>Perkiraan dampak</th></tr></thead>
        <tbody>
        <?php foreach ($magnitudo as [$m, $d]): ?>
          <tr><td class="fw-bold"><?= e($m) ?></td><td><?= e($d) ?></td></tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div class="alert alert-secondary small">
      <strong>M 5,0 vs M 7,0:</strong> skala magnitudo bersifat logaritmik. Setiap kenaikan 1 angka berarti amplitudo gelombang sekitar 10 kali lebih besar dan energi sekitar 32 kali lebih besar. Jadi gempa M 7,0 melepaskan energi sekitar <strong>1.000 kali</strong> gempa M 5,0.
      Kedalaman juga berpengaruh: gempa <strong>dangkal</strong> (&lt; 60 km) umumnya lebih terasa dan lebih merusak daripada gempa dalam pada magnitudo yang sama.
    </div>

    <h3 class="h6 fw-bold mt-4">Intensitas: dampak yang dirasakan di suatu lokasi (skala MMI)</h3>
    <p class="small text-muted">Satu gempa punya satu magnitudo, tetapi intensitasnya berbeda-beda di tiap tempat tergantung jarak dan kondisi tanah. Data "gempa dirasakan" BMKG di beranda memakai skala ini. BMKG juga menyederhanakannya menjadi skala SIG-BMKG (I-V, berwarna).</p>
    <div class="table-responsive">
      <table class="table table-sm table-bordered align-middle mb-0">
        <thead class="table-light"><tr><th style="width:80px">MMI</th><th>Yang terjadi</th><th style="width:130px">SIG-BMKG</th></tr></thead>
        <tbody>
        <?php foreach ($mmi as [$k, $d, $sig]): ?>
          <tr><td class="fw-bold"><?= e($k) ?></td><td class="small"><?= e($d) ?></td><td class="small"><?= e($sig) ?></td></tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div class="small text-muted mt-2">Ringkasan dengan kata-kata sendiri; rujukan resmi: situs BMKG.</div>
  </section>

  <!-- ================= MITOS ================= -->
  <section id="mitos" class="kartu p-3 p-md-4 mb-3">
    <h2 class="h5 fw-bold">7. Mitos vs Fakta</h2>
    <div class="accordion" id="accMitos">
      <?php foreach ($mitos as $i => [$m, $f]): ?>
        <div class="accordion-item">
          <h3 class="accordion-header">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#mitos<?= $i ?>" aria-expanded="false">
              <span class="badge bg-danger me-2">MITOS</span> <?= e($m) ?>
            </button>
          </h3>
          <div id="mitos<?= $i ?>" class="accordion-collapse collapse" data-bs-parent="#accMitos">
            <div class="accordion-body small"><span class="badge bg-success me-1">FAKTA</span> <?= e($f) ?></div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </section>

  <!-- ================= TAS SIAGA ================= -->
  <section id="tas-siaga" class="kartu p-3 p-md-4 mb-3">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
      <h2 class="h5 fw-bold mb-0">8. Checklist Tas Siaga Bencana</h2>
      <div class="no-print d-flex gap-2">
        <button class="btn btn-sm btn-outline-dark" type="button" id="btnCetak"><i class="bi bi-printer"></i> Cetak</button>
        <button class="btn btn-sm btn-outline-secondary" type="button" id="btnReset">Reset</button>
      </div>
    </div>
    <p class="small text-muted">Centang yang sudah Anda siapkan. Centang tersimpan di browser ini. Periksa isi tas secara berkala (misalnya tiap 6 bulan) agar makanan dan obat tidak kedaluwarsa.</p>

    <div class="mb-3">
      <div class="d-flex justify-content-between small fw-bold"><span id="tasStatus">Tingkat kesiapan</span><span id="tasPersen">0%</span></div>
      <div class="progress" role="progressbar" aria-label="Kesiapan tas siaga" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0" style="height:16px;">
        <div class="progress-bar bg-danger" id="tasBar" style="width:0%"></div>
      </div>
    </div>

    <div class="row g-3">
      <?php $n = 0; foreach ($tas as $kategori => $butir): ?>
        <div class="col-md-6">
          <div class="border rounded p-3 h-100">
            <div class="fw-bold mb-2"><?= e($kategori) ?></div>
            <?php foreach ($butir as $b): $n++; ?>
              <div class="form-check mb-1">
                <input class="form-check-input tas-cek" type="checkbox" id="tas<?= $n ?>" data-key="tas<?= $n ?>">
                <label class="form-check-label small" for="tas<?= $n ?>"><?= e($b) ?></label>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </section>

  <div class="small text-muted">
    Rujukan informasi resmi: <a href="https://www.bmkg.go.id" target="_blank" rel="noopener">BMKG</a> dan
    <a href="https://bnpb.go.id" target="_blank" rel="noopener">BNPB</a>. Materi ini ringkasan edukasi umum;
    ikuti arahan petugas setempat saat terjadi bencana.
  </div>
</main>

<?php require __DIR__ . '/includes/footer.php'; ?>
