<?php
/**
 * edukasi.php — Halaman Edukasi Gempa Bumi
 * 5 bagian: Definisi, Penyebab, Dampak, Penanggulangan, Mitigasi
 * Dilengkapi gambar dari Google Custom Search (cache 7 hari di DB)
 */

require_once __DIR__ . '/config/koneksi.php';
require_once __DIR__ . '/includes/fungsi.php';

// ============================================================
// Ambil gambar dari database (cache)
// Jika cache kosong untuk suatu bagian, akan ditampilkan placeholder
// ============================================================
function ambilGambarEdukasi($koneksi, $bagian, $limit = 3) {
    $stmt = $koneksi->prepare(
        "SELECT * FROM gambar_edukasi WHERE bagian = ? ORDER BY id ASC LIMIT ?"
    );
    $stmt->bind_param('si', $bagian, $limit);
    $stmt->execute();
    $hasil = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $hasil;
}

// Konfigurasi pencarian gambar per bagian
$config_gambar = [
    'definisi'        => ['q' => 'gempa bumi ilustrasi earthquake definition',        'jumlah' => 2],
    'penyebab'        => ['q' => 'lempeng tektonik Indonesia cincin api ring of fire', 'jumlah' => 3],
    'dampak'          => ['q' => 'kerusakan akibat gempa bumi Indonesia pengungsi',    'jumlah' => 2],
    'penanggulangan'  => ['q' => 'drop cover hold on gempa tas siaga bencana',         'jumlah' => 2],
    'mitigasi'        => ['q' => 'bangunan tahan gempa jalur evakuasi rambu',          'jumlah' => 2],
];

$judul_halaman = 'Edukasi Gempa Bumi';
$deskripsi     = 'Pelajari definisi, penyebab, dampak, penanggulangan, dan mitigasi gempa bumi di Indonesia';
require_once __DIR__ . '/includes/header.php';
?>

<script>var BASE_URL = '<?= BASE_URL ?>';</script>

<div class="container py-4">
    <!-- Judul halaman -->
    <div class="mb-4">
        <h1 class="h3 fw-bold text-primary-dark mb-1">📚 Edukasi Gempa Bumi</h1>
        <p class="text-muted">Memahami gempa bumi: dari definisi hingga cara menyelamatkan diri</p>
    </div>

    <!-- ============================================================ -->
    <!-- ACCORDION MATERI EDUKASI                                      -->
    <!-- ============================================================ -->
    <div class="accordion" id="akordionEdukasi">

        <!-- ===== BAGIAN 1: DEFINISI ===== -->
        <div class="accordion-item border mb-3 rounded-card shadow-sm">
            <h2 class="accordion-header">
                <button class="accordion-button rounded-card" type="button"
                        data-bs-toggle="collapse" data-bs-target="#bagian1">
                    🔍 1. Definisi Bencana dan Gempa Bumi
                </button>
            </h2>
            <div id="bagian1" class="accordion-collapse collapse show" data-bs-parent="#akordionEdukasi">
                <div class="accordion-body">
                    <div class="row g-4">
                        <div class="col-md-7">
                            <h5 class="fw-bold text-primary-dark">📖 Definisi Bencana</h5>
                            <p>Berdasarkan <strong>Undang-Undang No. 24 Tahun 2007 tentang Penanggulangan Bencana</strong>, bencana didefinisikan sebagai:</p>
                            <blockquote class="blockquote border-start border-primary border-3 ps-3 fst-italic small text-muted">
                                "Peristiwa atau rangkaian peristiwa yang mengancam dan mengganggu kehidupan dan penghidupan masyarakat yang disebabkan, baik oleh faktor alam dan/atau faktor nonalam maupun faktor manusia sehingga mengakibatkan timbulnya korban jiwa manusia, kerusakan lingkungan, kerugian harta benda, dan dampak psikologis."
                            </blockquote>
                            <p>Bencana dibagi tiga jenis: <strong>Bencana Alam</strong> (gempa, tsunami, banjir, dll.), <strong>Bencana Nonalam</strong> (wabah, kegagalan teknologi), dan <strong>Bencana Sosial</strong> (konflik, teror).</p>

                            <h5 class="fw-bold text-primary-dark mt-4">🌍 Definisi Gempa Bumi</h5>
                            <p>Gempa bumi adalah <strong>getaran atau guncangan yang terjadi di permukaan bumi</strong> akibat pelepasan energi dari dalam bumi secara tiba-tiba, yang menciptakan gelombang seismik. Energi ini dipancarkan dari <em>hiposenter</em> (titik sumber gempa di dalam bumi) dan dirasakan di <em>episenter</em> (titik di permukaan bumi tepat di atas hiposenter).</p>
                            <ul>
                                <li>⚡ Energi gempa diukur dalam <strong>Skala Richter (SR)</strong> atau <strong>Skala Momen (Mw)</strong></li>
                                <li>📏 Kekuatan gempa &lt;3 SR: tidak terasa; 3–4 SR: lemah; 5–6 SR: kuat; &gt;7 SR: sangat kuat</li>
                                <li>🌊 Kedalaman gempa: Dangkal (&lt;60 km), Menengah (60–300 km), Dalam (&gt;300 km)</li>
                                <li>🇮🇩 Indonesia adalah salah satu negara paling rawan gempa di dunia</li>
                            </ul>
                        </div>
                        <div class="col-md-5">
                            <!-- Grid gambar bagian definisi -->
                            <?php $gambar = ambilGambarEdukasi($koneksi, 'definisi', 2); ?>
                            <?php include __DIR__ . '/includes/_gambar_edukasi.php'; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ===== BAGIAN 2: PENYEBAB ===== -->
        <div class="accordion-item border mb-3 rounded-card shadow-sm">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed rounded-card" type="button"
                        data-bs-toggle="collapse" data-bs-target="#bagian2">
                    ⚙️ 2. Penyebab Gempa Bumi
                </button>
            </h2>
            <div id="bagian2" class="accordion-collapse collapse">
                <div class="accordion-body">
                    <div class="row g-4">
                        <div class="col-md-7">
                            <h5 class="fw-bold text-primary-dark">Jenis-Jenis Penyebab Gempa</h5>
                            <div class="row g-3 mb-3">
                                <div class="col-sm-6">
                                    <div class="p-3 rounded border" style="border-left:4px solid #dc2626!important">
                                        <strong>🏔️ Gempa Tektonik</strong>
                                        <p class="small mt-1 mb-0">Disebabkan pergerakan lempeng tektonik bumi. Paling umum dan berpotensi merusak. Terjadi di zona subduksi, sesar, dan mid-ocean ridge.</p>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="p-3 rounded border" style="border-left:4px solid #d97706!important">
                                        <strong>🌋 Gempa Vulkanik</strong>
                                        <p class="small mt-1 mb-0">Akibat aktivitas magma di dalam gunung berapi. Biasanya lebih kecil dan terlokalisir di sekitar gunung berapi aktif.</p>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="p-3 rounded border" style="border-left:4px solid #0891b2!important">
                                        <strong>⛰️ Gempa Runtuhan</strong>
                                        <p class="small mt-1 mb-0">Disebabkan runtuhnya gua bawah tanah atau lereng. Skala kecil, berdampak lokal.</p>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="p-3 rounded border" style="border-left:4px solid #64748b!important">
                                        <strong>🏗️ Gempa Buatan Manusia</strong>
                                        <p class="small mt-1 mb-0">Dipicu aktivitas seperti penambangan, ledakan nuklir bawah tanah, atau pengisian waduk besar.</p>
                                    </div>
                                </div>
                            </div>

                            <h5 class="fw-bold text-primary-dark mt-3">🇮🇩 Indonesia di Cincin Api Pasifik</h5>
                            <p>Indonesia berada di <strong>Cincin Api Pasifik (Ring of Fire)</strong> — zona sepanjang ~40.000 km berbentuk tapal kuda yang mengelilingi Samudra Pasifik, tempat 90% gempa bumi di dunia terjadi.</p>
                            <p>Indonesia menjadi sangat rentan karena dikelilingi <strong>tiga lempeng tektonik besar</strong>:</p>
                            <ul>
                                <li>🔴 <strong>Lempeng Indo-Australia</strong> — bergerak ke utara ~7 cm/tahun, menunjam di bawah Lempeng Eurasia di sepanjang selatan Jawa–Sumatera</li>
                                <li>🔵 <strong>Lempeng Eurasia</strong> — relatif stabil, membentuk sebagian besar daratan Asia</li>
                                <li>🟡 <strong>Lempeng Pasifik</strong> — bergerak ke barat, bertumbukan di wilayah Papua dan Maluku Utara</li>
                            </ul>
                            <p>Zona tumbukan (subduksi) ini menghasilkan tekanan dahsyat yang terlepas sebagai gempa bumi dan juga memunculkan gunung berapi aktif.</p>
                        </div>
                        <div class="col-md-5">
                            <?php $gambar = ambilGambarEdukasi($koneksi, 'penyebab', 3); ?>
                            <?php include __DIR__ . '/includes/_gambar_edukasi.php'; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ===== BAGIAN 3: DAMPAK ===== -->
        <div class="accordion-item border mb-3 rounded-card shadow-sm">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed rounded-card" type="button"
                        data-bs-toggle="collapse" data-bs-target="#bagian3">
                    💥 3. Dampak terhadap Manusia dan Ekonomi
                </button>
            </h2>
            <div id="bagian3" class="accordion-collapse collapse">
                <div class="accordion-body">
                    <div class="row g-4">
                        <div class="col-md-7">
                            <div class="row g-3">
                                <div class="col-md-12">
                                    <h5 class="fw-bold" style="color:#dc2626">⛔ Dampak Negatif</h5>
                                    <ul>
                                        <li>💀 <strong>Korban jiwa dan luka-luka</strong> — akibat reruntuhan bangunan, kebakaran, atau tsunami susulan</li>
                                        <li>🏚️ <strong>Kerusakan rumah dan infrastruktur</strong> — jembatan, jalan, jaringan listrik, air bersih, dan telekomunikasi terganggu</li>
                                        <li>⛺ <strong>Pengungsian massal</strong> — warga harus meninggalkan rumah, rentan cuaca buruk dan penyakit</li>
                                        <li>🧠 <strong>Trauma psikologis</strong> — terutama anak-anak dan lansia, dapat berupa PTSD, depresi, dan kecemasan berkepanjangan</li>
                                        <li>💰 <strong>Kerugian ekonomi besar</strong> — biaya pemulihan, kehilangan aset, dan penurunan investasi</li>
                                        <li>🏫 <strong>Terhentinya pendidikan</strong> — sekolah rusak, anak-anak kehilangan akses belajar</li>
                                        <li>🏭 <strong>Terhentinya usaha</strong> — UMKM, pabrik, dan sektor jasa terganggu berbulan-bulan</li>
                                        <li>🌊 <strong>Potensi tsunami</strong> — gempa tektonik di bawah laut dapat memicu gelombang tsunami mematikan</li>
                                        <li>💧 <strong>Pencemaran lingkungan</strong> — tanah longsor, kebocoran bahan berbahaya dari industri</li>
                                    </ul>
                                </div>
                                <div class="col-md-12">
                                    <h5 class="fw-bold" style="color:#16a34a">✅ Dampak Positif</h5>
                                    <ul>
                                        <li>🧠 <strong>Meningkatnya kesadaran dan budaya siaga</strong> — masyarakat lebih peduli terhadap kesiapsiagaan bencana</li>
                                        <li>🏗️ <strong>Perbaikan standar bangunan</strong> — mendorong penerapan SNI dan bangunan tahan gempa</li>
                                        <li>🔬 <strong>Kemajuan ilmu pengetahuan</strong> — mendorong penelitian seismologi, sistem peringatan dini, dan teknologi konstruksi</li>
                                        <li>🤝 <strong>Solidaritas sosial</strong> — mempererat hubungan antar-warga dan komunitas dalam membantu korban</li>
                                        <li>🏘️ <strong>Pembangunan kembali yang lebih baik</strong> — infrastruktur dibangun dengan standar lebih modern dan aman</li>
                                        <li>💼 <strong>Peluang kerja rekonstruksi</strong> — sektor konstruksi, logistik, dan jasa sosial berkembang pascabencana</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-5">
                            <?php $gambar = ambilGambarEdukasi($koneksi, 'dampak', 2); ?>
                            <?php include __DIR__ . '/includes/_gambar_edukasi.php'; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ===== BAGIAN 4: PENANGGULANGAN ===== -->
        <div class="accordion-item border mb-3 rounded-card shadow-sm">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed rounded-card" type="button"
                        data-bs-toggle="collapse" data-bs-target="#bagian4">
                    🛡️ 4. Cara Penanggulangan Gempa Bumi
                </button>
            </h2>
            <div id="bagian4" class="accordion-collapse collapse">
                <div class="accordion-body">
                    <div class="row g-4">
                        <div class="col-md-7">
                            <div class="mb-4">
                                <h5 class="fw-bold" style="color:#0891b2">⏮️ SEBELUM Gempa</h5>
                                <ul>
                                    <li>📋 Pelajari rencana evakuasi keluarga dan lokasi titik kumpul</li>
                                    <li>🎒 Siapkan <strong>Tas Siaga Bencana</strong>: air minum 3 hari, makanan kaleng, P3K, senter, radio, dokumen penting (fotokopi KTP, KK, akta kelahiran), obat-obatan rutin, pakaian ganti, uang tunai</li>
                                    <li>🏠 Pasang lemari dan furnitur berat ke dinding; hindari menaruh barang berat di atas kepala</li>
                                    <li>📞 Catat dan hafal nomor darurat: 112, BNPB (021-500-113), BMKG (021-650-2468)</li>
                                    <li>🔧 Pelajari cara mematikan gas, listrik, dan air</li>
                                    <li>💪 Ikuti latihan simulasi gempa (earthquake drill) di sekolah atau kantor</li>
                                </ul>
                            </div>
                            <div class="mb-4">
                                <h5 class="fw-bold" style="color:#dc2626">⚡ SAAT Gempa Terjadi</h5>
                                <div class="alert alert-danger py-2">
                                    <strong>Ingat: DROP → COVER → HOLD ON</strong>
                                </div>
                                <ul>
                                    <li>🫳 <strong>DROP</strong>: Jatuhkan diri ke lantai, lindungi kepala dan leher dengan tangan</li>
                                    <li>🛡️ <strong>COVER</strong>: Berlindung di bawah meja kokoh atau di samping tempat tidur. Jika tidak ada, berlindung di sudut dalam bangunan</li>
                                    <li>✋ <strong>HOLD ON</strong>: Pegang erat perlindungan hingga guncangan berhenti</li>
                                    <li>🚫 JANGAN berlari ke luar saat guncangan masih berlangsung</li>
                                    <li>🚫 JANGAN menggunakan lift</li>
                                    <li>🚫 JANGAN berdiri di dekat jendela, kaca, atau rak buku</li>
                                    <li>🌊 Jika dekat pantai, segera menjauh ke tempat tinggi setelah guncangan berhenti (antisipasi tsunami)</li>
                                </ul>
                            </div>
                            <div>
                                <h5 class="fw-bold" style="color:#16a34a">⏭️ PASCA Gempa</h5>
                                <ul>
                                    <li>🔍 Periksa kondisi diri dan orang sekitar; berikan pertolongan pertama jika mampu</li>
                                    <li>📻 Nyalakan radio baterai untuk informasi resmi dari BMKG/BNPB</li>
                                    <li>🏠 Periksa kebocoran gas (cium bau gas), kerusakan listrik, dan retakan struktural sebelum masuk kembali</li>
                                    <li>⚠️ Waspadai gempa susulan — bisa datang dalam menit hingga hari berikutnya</li>
                                    <li>📷 Dokumentasikan kerusakan untuk keperluan klaim asuransi atau bantuan pemerintah</li>
                                    <li>🤝 Ikuti arahan petugas, jangan mudik atau kembali ke zona merah tanpa izin</li>
                                </ul>
                            </div>
                        </div>
                        <div class="col-md-5">
                            <?php $gambar = ambilGambarEdukasi($koneksi, 'penanggulangan', 2); ?>
                            <?php include __DIR__ . '/includes/_gambar_edukasi.php'; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ===== BAGIAN 5: MITIGASI ===== -->
        <div class="accordion-item border mb-3 rounded-card shadow-sm">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed rounded-card" type="button"
                        data-bs-toggle="collapse" data-bs-target="#bagian5">
                    🏗️ 5. Mitigasi Gempa Bumi
                </button>
            </h2>
            <div id="bagian5" class="accordion-collapse collapse">
                <div class="accordion-body">
                    <div class="row g-4">
                        <div class="col-md-7">
                            <h5 class="fw-bold text-primary-dark">Mitigasi Struktural</h5>
                            <p>Upaya fisik untuk mengurangi risiko kerusakan akibat gempa:</p>
                            <ul>
                                <li>🏗️ <strong>Bangunan Tahan Gempa (BTG)</strong> — desain mengikuti SNI 1726 (Standar Nasional Indonesia untuk bangunan tahan gempa), menggunakan material fleksibel, pondasi dalam, dan sistem peredam kejut (base isolator)</li>
                                <li>🔧 <strong>Retrofitting</strong> — penguatan struktural bangunan lama agar tahan gempa: penambahan kolom, penggunaan baja tulangan lebih kuat, dan perbaikan sambungan</li>
                                <li>🛣️ <strong>Infrastruktur tahan gempa</strong> — jembatan, jalan, bendungan, dan jaringan utilitas dirancang dengan standar khusus zona gempa</li>
                                <li>🏥 <strong>Fasilitas kritis</strong> — rumah sakit, kantor pemerintah, dan sekolah wajib memenuhi standar konstruksi tertinggi</li>
                            </ul>

                            <h5 class="fw-bold text-primary-dark mt-3">📋 Mitigasi Non-Struktural</h5>
                            <ul>
                                <li>🗺️ <strong>Tata Ruang Berbasis Risiko</strong> — kawasan berpotensi gempa tinggi dibatasi peruntukannya untuk permukiman padat</li>
                                <li>📚 <strong>Edukasi dan Pelatihan</strong> — kurikulum kebencanaan di sekolah, pelatihan pertolongan pertama, dan simulasi evakuasi rutin</li>
                                <li>🔔 <strong>Sistem Peringatan Dini InaTEWS</strong> — Indonesia Tsunami Early Warning System; mendeteksi gempa dan memberikan peringatan dini tsunami dalam hitungan menit</li>
                                <li>🎒 <strong>Tas Siaga Bencana</strong> — disiapkan setiap keluarga, berisi kebutuhan darurat 72 jam pertama</li>
                                <li>🚦 <strong>Jalur Evakuasi</strong> — rambu evakuasi tsunami/gempa yang terpasang jelas di kawasan rawan, menuju titik kumpul (muster point) yang aman</li>
                                <li>🏛️ <strong>Regulasi dan Penegakan Hukum</strong> — pemerintah mewajibkan IMB (Izin Mendirikan Bangunan) yang mensyaratkan kepatuhan standar tahan gempa</li>
                                <li>📱 <strong>Aplikasi BMKG</strong> — unduh aplikasi BMKG untuk notifikasi gempa real-time di smartphone</li>
                            </ul>
                        </div>
                        <div class="col-md-5">
                            <?php $gambar = ambilGambarEdukasi($koneksi, 'mitigasi', 2); ?>
                            <?php include __DIR__ . '/includes/_gambar_edukasi.php'; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div><!-- /accordion -->

    <!-- Catatan kaki gambar -->
    <div class="alert alert-light border mt-3 small text-muted">
        📸 <strong>Catatan:</strong> Gambar ilustrasi diambil dari berbagai sumber melalui pencarian Google Images;
        hak cipta tetap milik pemilik masing-masing. Gambar dari Wikimedia Commons tersedia di bawah lisensi Creative Commons.
        <br>Jika gambar tidak muncul, <a href="README.md">konfigurasikan Google API Key</a> atau
        <a href="<?= BASE_URL ?>/admin/gambar.php">tambahkan gambar manual melalui panel admin</a>.
    </div>

</div><!-- /container -->

<?php
$skrip_tambahan = '<script>
// Muat gambar via AJAX jika cache DB kosong untuk bagian tertentu
// (opsional: cukup reload halaman setelah API key dikonfigurasikan)
</script>';
require_once __DIR__ . '/includes/footer.php';
?>
