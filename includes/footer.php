
<?php /* includes/footer.php */ ?>
    <!-- ============================================================ -->
    <!-- includes/footer.php - Footer bawah halaman                   -->
    <!-- ============================================================ -->
    <footer class="footer bg-primary-dark text-light py-4 mt-5">
        <div class="container">
            <div class="row gy-3">
                <!-- Tentang aplikasi -->
                <div class="col-md-4">
                    <h6 class="fw-bold text-warning mb-2">SIMB GempaBumi</h6>
                    <p class="small text-light-50 mb-0">
                        Sistem Informasi Manajemen Bencana untuk edukasi dan pemantauan gempa bumi di Indonesia.
                        Dibuat sebagai proyek akademik.
                    </p>
                </div>

                <!-- Tautan cepat -->
                <div class="col-md-4">
                    <h6 class="fw-bold text-warning mb-2">Tautan Cepat</h6>
                    <ul class="list-unstyled small">
                        <li><a href="<?= BASE_URL ?>/" class="text-light text-decoration-none">🏠 Beranda</a></li>
                        <li><a href="<?= BASE_URL ?>/edukasi.php" class="text-light text-decoration-none">📚 Edukasi Gempa</a></li>
                        <li><a href="<?= BASE_URL ?>/monitoring.php" class="text-light text-decoration-none">📊 Monitoring Gempa</a></li>
                        <li><a href="<?= BASE_URL ?>/orang-hilang.php" class="text-light text-decoration-none">🔍 Orang Hilang</a></li>
                        <li><a href="<?= BASE_URL ?>/lapor-orang-hilang.php" class="text-light text-decoration-none">📋 Lapor Orang Hilang</a></li>
                    </ul>
                </div>

                <!-- Sumber data -->
                <div class="col-md-4">
                    <h6 class="fw-bold text-warning mb-2">Sumber Data</h6>
                    <ul class="list-unstyled small">
                        <li>
                            <a href="https://www.bmkg.go.id" target="_blank" rel="noopener noreferrer"
                               class="text-light text-decoration-none">
                                🌐 BMKG – Badan Meteorologi, Klimatologi, dan Geofisika
                            </a>
                        </li>
                        <li>
                            <a href="https://data.bmkg.go.id/gempabumi/" target="_blank" rel="noopener noreferrer"
                               class="text-light text-decoration-none">
                                📡 Open Data BMKG Gempabumi
                            </a>
                        </li>
                    </ul>
                    <!-- Nomor darurat -->
                    <div class="mt-2 small">
                        <strong class="text-warning">Darurat:</strong>
                        <span class="text-light">112 (Nasional) | 021-500-113 (BNPB)</span>
                    </div>
                </div>
            </div>

            <hr class="border-secondary my-3">
            <div class="row align-items-center">
                <div class="col-md-8 small text-light-50">
                    Data gempa diambil dari API BMKG dan diperbarui secara berkala.
                    Informasi ini bersifat informatif dan bukan peringatan resmi pemerintah.
                </div>
                <div class="col-md-4 text-md-end small text-light-50">
                    &copy; <?= date('Y') ?> SIMB GempaBumi — Proyek Akademik
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap 5 JS Bundle (sudah termasuk Popper) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"
            integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL"
            crossorigin="anonymous"></script>

    <!-- Leaflet JS untuk peta -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
            integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV/XN/WLEg="
            crossorigin=""></script>

    <!-- Chart.js untuk grafik -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

    <!-- JS kustom aplikasi -->
    <script src="<?= BASE_URL ?>/assets/js/app.js"></script>

    <!-- Slot script tambahan per halaman -->
    <?php if (isset($skrip_tambahan)) echo $skrip_tambahan; ?>

</body>
</html>
