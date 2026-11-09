/**
 * SIMB - grafik statistik gempa (Chart.js)
 * Sumber: api/gempa_json.php?aksi=statistik&dari=...&sampai=...
 */
(() => {
  'use strict';
  if (typeof Chart === 'undefined' || !document.getElementById('chartWaktu')) return;

  const $ = (id) => document.getElementById(id);
  const esc = (s) => String(s ?? '').replace(/[&<>"']/g,
    (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

  const WARNA = { bahaya: '#c62828', jingga: '#ef6c00', kuning: '#f9a825', abu: '#607d8b', abuMuda: '#b0bec5', gelap: '#263238' };
  const charts = {};
  Chart.defaults.font.family = 'system-ui, -apple-system, "Segoe UI", Roboto, Arial, sans-serif';

  // ---------- tanggal (WIB) ----------
  const hariIniWib = () => new Date().toLocaleDateString('en-CA', { timeZone: 'Asia/Jakarta' }); // yyyy-mm-dd
  function geser(ymd, hari) {
    const d = new Date(ymd + 'T00:00:00Z');
    d.setUTCDate(d.getUTCDate() + hari);
    return d.toISOString().slice(0, 10);
  }
  const BULAN = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
  function labelWaktu(k, per) {
    const p = k.split('-');
    return per === 'bulan' ? `${BULAN[+p[1] - 1]} ${p[0]}` : `${+p[2]} ${BULAN[+p[1] - 1]}`;
  }
  const fmtTgl = (ymd) => { const p = ymd.split('-'); return `${+p[2]} ${BULAN[+p[1] - 1]} ${p[0]}`; };

  // ---------- render grafik ----------
  function gambar(id, config) {
    if (charts[id]) charts[id].destroy();
    charts[id] = new Chart($(id), config);
  }

  const sumbuInt = { beginAtZero: true, ticks: { precision: 0 } };

  function render(d) {
    const per = d.rentang.per;

    // Ringkasan
    const r = d.ringkasan;
    $('statRingkasan').innerHTML = r.total === 0 ? '' : `
      <div class="col-6 col-md-3"><div class="kartu p-2 text-center"><div class="fs-3 fw-bold">${r.total}</div><div class="small">Total gempa</div></div></div>
      <div class="col-6 col-md-3"><div class="kartu p-2 text-center"><div class="fs-3 fw-bold">${r.rata_mag.toFixed(1)}</div><div class="small">Rata-rata magnitudo</div></div></div>
      <div class="col-6 col-md-3"><div class="kartu p-2 text-center"><div class="fs-3 fw-bold text-danger">${r.jumlah_m5}</div><div class="small">Gempa M &ge; 5,0</div></div></div>
      <div class="col-6 col-md-3"><div class="kartu p-2 text-center"><div class="fs-3 fw-bold">${r.dirasakan}</div><div class="small">Dirasakan warga</div></div></div>
      <div class="col-12"><div class="kartu p-2 small"><strong>Terkuat:</strong> M ${r.terkuat.magnitudo.toFixed(1)} - ${esc(r.terkuat.wilayah)} (${esc(r.terkuat.waktu.slice(0, 10))})</div></div>`;

    // Catatan cakupan data
    $('statCatatan').textContent = d.data_sejak
      ? `Dihitung dari data yang tersimpan sistem sejak ${fmtTgl(d.data_sejak.slice(0, 10))}. Sumber BMKG hanya menyediakan daftar gempa M5,0+, gempa dirasakan, dan gempa terbaru, sehingga gempa kecil tidak tercakup dan ini bukan katalog lengkap.`
      : 'Belum ada data tersimpan.';

    const kosong = r.total === 0;
    $('statKosong').classList.toggle('d-none', !kosong);
    $('statGrafik').classList.toggle('d-none', kosong);
    if (kosong) return;

    // 1. Frekuensi + magnitudo maksimum per periode
    gambar('chartWaktu', {
      data: {
        labels: d.waktu.label.map((k) => labelWaktu(k, per)),
        datasets: [
          { type: 'bar', label: 'Jumlah gempa', data: d.waktu.jumlah, backgroundColor: WARNA.abu, yAxisID: 'y', order: 2 },
          { type: 'line', label: 'Magnitudo maksimum', data: d.waktu.mag_maks, borderColor: WARNA.bahaya,
            backgroundColor: WARNA.bahaya, yAxisID: 'y1', spanGaps: true, tension: 0.2, pointRadius: 3, order: 1 },
        ],
      },
      options: {
        responsive: true, maintainAspectRatio: false,
        interaction: { mode: 'index', intersect: false },
        scales: {
          y:  { ...sumbuInt, title: { display: true, text: 'Jumlah gempa' } },
          y1: { position: 'right', min: 0, max: 9, grid: { drawOnChartArea: false }, title: { display: true, text: 'Magnitudo' } },
          x:  { ticks: { maxRotation: 60, autoSkip: true, maxTicksLimit: 15 } },
        },
        plugins: { legend: { position: 'bottom' } },
      },
    });

    // 2. Distribusi magnitudo (doughnut)
    gambar('chartMag', {
      type: 'doughnut',
      data: {
        labels: d.magnitudo.label,
        datasets: [{ data: d.magnitudo.jumlah, backgroundColor: [WARNA.abuMuda, WARNA.abu, WARNA.kuning, WARNA.jingga, WARNA.bahaya] }],
      },
      options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } },
    });

    // 3. Distribusi kedalaman (bar)
    gambar('chartDalam', {
      type: 'bar',
      data: {
        labels: d.kedalaman.label,
        datasets: [{ label: 'Jumlah gempa', data: d.kedalaman.jumlah, backgroundColor: [WARNA.bahaya, WARNA.jingga, WARNA.abu] }],
      },
      options: {
        responsive: true, maintainAspectRatio: false,
        scales: { y: sumbuInt, x: { ticks: { autoSkip: false } } },
        plugins: { legend: { display: false } },
      },
    });

    // 4. Top 5 lokasi (horizontal bar)
    gambar('chartLokasi', {
      type: 'bar',
      data: {
        labels: d.top_lokasi.map((x) => x.lokasi),
        datasets: [{ label: 'Jumlah gempa', data: d.top_lokasi.map((x) => x.jumlah), backgroundColor: WARNA.gelap }],
      },
      options: {
        indexAxis: 'y', responsive: true, maintainAspectRatio: false,
        scales: { x: sumbuInt },
        plugins: { legend: { display: false } },
      },
    });
  }

  // ---------- muat data ----------
  async function muat(dari, sampai) {
    $('statStatus').textContent = 'Memuat…';
    try {
      const res = await fetch(`api/gempa_json.php?aksi=statistik&dari=${dari}&sampai=${sampai}`);
      if (!res.ok) throw new Error('HTTP ' + res.status);
      const json = await res.json();
      if (!json.ok) throw new Error(json.pesan || 'Respon tidak valid');
      // sinkronkan input dengan rentang yang benar-benar dipakai server
      $('statDari').value = json.data.rentang.dari;
      $('statSampai').value = json.data.rentang.sampai;
      render(json.data);
      $('statStatus').textContent = `${fmtTgl(json.data.rentang.dari)} - ${fmtTgl(json.data.rentang.sampai)}`
        + ` (per ${json.data.rentang.per})`;
    } catch (err) {
      $('statStatus').innerHTML = '<span class="text-danger">Statistik tidak dapat dimuat. Periksa koneksi.</span>';
    }
  }

  function tandaiPreset(hari) {
    document.querySelectorAll('.btn-preset').forEach((b) => {
      b.classList.toggle('btn-dark', b.dataset.hari === String(hari));
      b.classList.toggle('btn-outline-dark', b.dataset.hari !== String(hari));
    });
  }

  document.querySelectorAll('.btn-preset').forEach((b) => b.addEventListener('click', () => {
    const hari = parseInt(b.dataset.hari, 10);
    const sampai = hariIniWib();
    tandaiPreset(hari);
    muat(geser(sampai, -(hari - 1)), sampai);
  }));

  $('formStat').addEventListener('submit', (e) => {
    e.preventDefault();
    const dari = $('statDari').value, sampai = $('statSampai').value;
    if (!dari || !sampai) return;
    tandaiPreset('x');
    muat(dari, sampai);
  });

  // awal: 30 hari terakhir, dimuat ulang tiap 5 menit dengan rentang yang sedang dipilih
  tandaiPreset(30);
  const awal = hariIniWib();
  muat(geser(awal, -29), awal);
  setInterval(() => muat($('statDari').value, $('statSampai').value), 300000);
})();
