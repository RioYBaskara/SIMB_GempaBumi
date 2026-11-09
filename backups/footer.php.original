<?php $base = $base ?? ''; ?>
<footer class="bg-dark text-light mt-5 py-4">
  <div class="container-xl small">
    <div class="row g-3">
      <div class="col-md-6">
        <strong>SIMB - Sistem Informasi Manajemen Bencana</strong><br>
        Platform edukasi, pemantauan gempa bumi, dan pencarian orang hilang.
      </div>
      <div class="col-md-6">
        <strong>Sumber data</strong><br>
        Data gempa bumi: <a class="link-light" href="https://data.bmkg.go.id/gempabumi" target="_blank" rel="noopener">BMKG (Badan Meteorologi, Klimatologi, dan Geofisika)</a>.<br>
        Peta: &copy; <a class="link-light" href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap</a> contributors.<br>
        Untuk keputusan keselamatan, selalu rujuk informasi resmi BMKG dan BNPB.
      </div>
    </div>
  </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<?php if (!empty($extraJs)) foreach ($extraJs as $js): ?>
<script src="<?= $base . htmlspecialchars($js) ?>"></script>
<?php endforeach; ?>
</body>
</html>
