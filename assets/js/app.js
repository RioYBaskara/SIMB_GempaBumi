/**
 * SIMB - logika halaman utama
 * Alert bar, peta Leaflet, gempa dirasakan, daftar M5+, jarak dari lokasi pengguna,
 * auto-refresh 60 detik, bunyi/notifikasi gempa baru.
 */
(() => {
  'use strict';

  const API = 'api/gempa_json.php';
  const REFRESH_MS = 60000;
  const $ = (id) => document.getElementById(id);

  // ---------- util ----------
  const esc = (s) => String(s ?? '').replace(/[&<>"']/g,
    (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

  const fmtWaktu = (iso) => new Date(iso).toLocaleString('id-ID', {
    timeZone: 'Asia/Jakarta', day: 'numeric', month: 'short', year: 'numeric',
    hour: '2-digit', minute: '2-digit',
  }).replace(/\./g, ':') + ' WIB';

  function fmtRelatif(iso) {
    const mnt = Math.floor((Date.now() - new Date(iso).getTime()) / 60000);
    if (mnt < 1) return 'baru saja';
    if (mnt < 60) return `${mnt} menit lalu`;
    const jam = Math.floor(mnt / 60);
    if (jam < 24) return `${jam} jam lalu`;
    return `${Math.floor(jam / 24)} hari lalu`;
  }

  // Rumus Haversine (km)
  function jarakKm(lat1, lon1, lat2, lon2) {
    const R = 6371, rad = (d) => d * Math.PI / 180;
    const dLat = rad(lat2 - lat1), dLon = rad(lon2 - lon1);
    const a = Math.sin(dLat / 2) ** 2 +
              Math.cos(rad(lat1)) * Math.cos(rad(lat2)) * Math.sin(dLon / 2) ** 2;
    return 2 * R * Math.asin(Math.sqrt(a));
  }

  const kelasMag = (m) => (m >= 6 ? 'bahaya' : m >= 5 ? 'waspada' : 'info');
  const warnaMarker = (m) => (m >= 6 ? '#c62828' : m >= 5 ? '#ef6c00' : m >= 4 ? '#f9a825' : '#607d8b');
  const badgeMag = (m) => `<span class="mag mag-${kelasMag(m)}">${m.toFixed(1)}</span>`;

  async function ambil(aksi, params = '') {
    const ctl = new AbortController();
    const t = setTimeout(() => ctl.abort(), 15000);
    const kunci = `simb_cache_${aksi}${params}`;
    try {
      const res = await fetch(`${API}?aksi=${aksi}${params}`, { signal: ctl.signal });
      if (!res.ok) throw new Error('HTTP ' + res.status);
      const json = await res.json();
      if (!json.ok) throw new Error(json.pesan || 'Respon tidak valid');
      try { localStorage.setItem(kunci, JSON.stringify(json)); } catch (e) { /* abaikan */ }
      json.dariCache = false;
      return json;
    } catch (err) {
      // Koneksi putus? Pakai data tersimpan terakhir (penting saat bencana)
      try {
        const c = JSON.parse(localStorage.getItem(kunci));
        if (c) { c.dariCache = true; return c; }
      } catch (e) { /* abaikan */ }
      throw err;
    } finally {
      clearTimeout(t);
    }
  }

  // ---------- state ----------
  let posisi = null;          // {lat, lon} pengguna
  let terbaru = null;
  let dataPeta = [];
  let dataDirasakan = [];
  let dataM5 = [];

  // ---------- peta ----------
  const peta = L.map('petaGempa', { zoomControl: true }).setView([-2.5, 118], 5);
  L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 18, attribution: '&copy; OpenStreetMap contributors',
  }).addTo(peta);
  const lapisan = L.layerGroup().addTo(peta);
  let markerUser = null;
  const markerById = {};

  function popupHtml(g) {
    let h = `<strong>M ${g.magnitudo.toFixed(1)}</strong> - ${esc(g.wilayah)}<br>
      ${esc(fmtWaktu(g.waktu_iso))}<br>Kedalaman: ${g.kedalaman} km`;
    if (g.potensi) h += `<br>${esc(g.potensi)}`;
    if (g.dirasakan) h += `<br><em>Dirasakan:</em> ${esc(g.dirasakan)}`;
    if (posisi) h += `<br>Jarak dari Anda: <strong>${Math.round(jarakKm(posisi.lat, posisi.lon, g.lintang, g.bujur))} km</strong>`;
    if (g.shakemap_url) h += `<br><a href="${esc(g.shakemap_url)}" target="_blank" rel="noopener">Lihat shakemap</a>`;
    return h;
  }

  function gambarPeta() {
    lapisan.clearLayers();
    Object.keys(markerById).forEach((k) => delete markerById[k]);
    // gambar yang kecil dulu supaya yang besar berada di atas
    [...dataPeta].sort((a, b) => a.magnitudo - b.magnitudo).forEach((g) => {
      const adalahTerbaru = terbaru && g.id === terbaru.id;
      const m = L.circleMarker([g.lintang, g.bujur], {
        radius: 4 + g.magnitudo * 2.2,
        color: adalahTerbaru ? '#000' : '#333',
        weight: adalahTerbaru ? 3 : 1,
        fillColor: warnaMarker(g.magnitudo),
        fillOpacity: 0.75,
      }).bindPopup(popupHtml(g));
      m.addTo(lapisan);
      markerById[g.id] = m;
    });
  }

  function fokusKe(id) {
    const m = markerById[id];
    if (!m) return;
    $('peta').scrollIntoView({ behavior: 'smooth' });
    peta.flyTo(m.getLatLng(), 8);
    m.openPopup();
  }

  // ---------- alert bar ----------
  function renderAlert() {
    const bar = $('alertBar'), teks = $('alertTeks');
    if (!bar || !terbaru) return;
    const g = terbaru;
    bar.className = `alert-bar alert-bar-${g.level}`;
    const tsunami = g.potensi && /tsunami/i.test(g.potensi) && !/tidak/i.test(g.potensi);
    teks.innerHTML = `
      <i class="bi bi-exclamation-triangle-fill me-1"></i>
      <span class="badge-mag">${g.magnitudo.toFixed(1)}</span>
      <strong>Gempa terbaru:</strong> ${esc(g.wilayah)} &middot; ${esc(fmtRelatif(g.waktu_iso))}
      (${esc(fmtWaktu(g.waktu_iso))}) &middot; Kedalaman ${g.kedalaman} km
      ${g.potensi ? ` &middot; <strong>${tsunami ? 'BERPOTENSI TSUNAMI' : esc(g.potensi)}</strong>` : ''}
      &middot; <a href="#peta" id="alertLihat">Lihat di peta</a>`;
    const lihat = $('alertLihat');
    if (lihat) lihat.addEventListener('click', (e) => { e.preventDefault(); fokusKe(g.id); });
  }

  // ---------- kartu detail gempa terbaru ----------
  function renderDetailTerbaru() {
    const el = $('detailTerbaru');
    if (!el || !terbaru) return;
    const g = terbaru;
    el.innerHTML = `
      <div class="d-flex align-items-center gap-3 mb-2">
        ${badgeMag(g.magnitudo).replace('mag ', 'mag fs-3 ')}
        <div><strong>${esc(g.wilayah)}</strong><br>
        <span class="text-muted small">${esc(fmtWaktu(g.waktu_iso))} (${esc(fmtRelatif(g.waktu_iso))})</span></div>
      </div>
      <ul class="list-unstyled mb-0 small">
        <li><i class="bi bi-arrow-down-circle"></i> Kedalaman: <strong>${g.kedalaman} km</strong></li>
        <li><i class="bi bi-geo-alt"></i> Koordinat: ${g.lintang.toFixed(2)}, ${g.bujur.toFixed(2)}</li>
        ${g.potensi ? `<li><i class="bi bi-water"></i> ${esc(g.potensi)}</li>` : ''}
        ${g.dirasakan ? `<li><i class="bi bi-people"></i> Dirasakan: ${esc(g.dirasakan)}</li>` : ''}
      </ul>
      ${g.shakemap_url ? `<a class="btn btn-sm btn-outline-dark mt-2" href="${esc(g.shakemap_url)}" target="_blank" rel="noopener">Lihat shakemap BMKG</a>` : ''}`;
  }

  // ---------- gempa dirasakan ----------
  function renderDirasakan() {
    const el = $('listDirasakan');
    if (!dataDirasakan.length) {
      el.innerHTML = '<div class="text-muted">Belum ada data gempa dirasakan.</div>';
      return;
    }
    el.innerHTML = dataDirasakan.map((g) => `
      <div class="col-md-6">
        <div class="kartu item-gempa level-${g.level} p-3 h-100">
          <div class="d-flex gap-3">
            <div>${badgeMag(g.magnitudo)}</div>
            <div class="flex-grow-1">
              <strong>${esc(g.wilayah)}</strong>
              <div class="small text-muted">${esc(fmtWaktu(g.waktu_iso))} &middot; ${g.kedalaman} km</div>
              <div class="small mt-1"><i class="bi bi-people"></i> <strong>Dirasakan:</strong> ${esc(g.dirasakan)}</div>
              ${posisi ? `<div class="small text-muted">${Math.round(jarakKm(posisi.lat, posisi.lon, g.lintang, g.bujur))} km dari lokasi Anda</div>` : ''}
              <button class="btn btn-link btn-sm p-0" data-fokus="${g.id}">Lihat di peta</button>
            </div>
          </div>
        </div>
      </div>`).join('');
  }

  // ---------- daftar M5+ ----------
  function renderTabel() {
    const body = $('tabelGempa');
    if (!dataM5.length) {
      body.innerHTML = '<tr><td colspan="5" class="text-center text-muted">Belum ada data.</td></tr>';
      return;
    }
    body.innerHTML = dataM5.map((g) => `
      <tr data-fokus="${g.id}">
        <td>${esc(fmtWaktu(g.waktu_iso))}</td>
        <td>${badgeMag(g.magnitudo)}</td>
        <td>${g.kedalaman} km</td>
        <td>${esc(g.wilayah)}</td>
        <td class="text-nowrap">${posisi ? Math.round(jarakKm(posisi.lat, posisi.lon, g.lintang, g.bujur)) + ' km' : '-'}</td>
      </tr>`).join('');
  }

  // ---------- jarak dari lokasi pengguna ----------
  function renderJarak() {
    const el = $('hasilJarak');
    if (!posisi || !terbaru) return;
    const dTerbaru = jarakKm(posisi.lat, posisi.lon, terbaru.lintang, terbaru.bujur);
    let terdekat = null, dMin = Infinity;
    dataPeta.forEach((g) => {
      const d = jarakKm(posisi.lat, posisi.lon, g.lintang, g.bujur);
      if (d < dMin) { dMin = d; terdekat = g; }
    });
    let h = `<div class="mb-2">Gempa terbaru berjarak <strong class="fs-5">${Math.round(dTerbaru)} km</strong> dari lokasi Anda.</div>`;
    if (terdekat) {
      h += `<div class="small">Gempa terdekat (30 hari terakhir): M ${terdekat.magnitudo.toFixed(1)}
        - ${esc(terdekat.wilayah)}, <strong>${Math.round(dMin)} km</strong> dari Anda.</div>`;
    }
    if (dTerbaru < 150 && terbaru.magnitudo >= 5) {
      h += `<div class="alert alert-warning py-2 mt-2 mb-0 small">
        Lokasi Anda relatif dekat dengan pusat gempa. Periksa kondisi sekitar dan waspadai gempa susulan.</div>`;
    }
    el.innerHTML = h;
  }

  function mintaLokasi() {
    const btn = $('btnLokasi'), el = $('hasilJarak');
    if (!navigator.geolocation) {
      el.innerHTML = '<span class="text-danger">Browser tidak mendukung geolokasi.</span>';
      return;
    }
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Mencari lokasi…';
    navigator.geolocation.getCurrentPosition((p) => {
      posisi = { lat: p.coords.latitude, lon: p.coords.longitude };
      btn.disabled = false;
      btn.innerHTML = '<i class="bi bi-crosshair"></i> Perbarui lokasi saya';
      if (markerUser) markerUser.remove();
      markerUser = L.marker([posisi.lat, posisi.lon]).addTo(peta).bindPopup('Lokasi Anda');
      renderJarak(); renderTabel(); renderDirasakan(); gambarPeta();
    }, (err) => {
      btn.disabled = false;
      btn.innerHTML = '<i class="bi bi-crosshair"></i> Coba lagi';
      el.innerHTML = '<span class="text-danger">Lokasi tidak dapat diakses' +
        (err.code === 1 ? ' (izin ditolak). Aktifkan izin lokasi di browser.' : '.') + '</span>';
    }, { enableHighAccuracy: false, timeout: 15000, maximumAge: 60000 });
  }

  // ---------- notifikasi gempa baru ----------
  function bunyi() {
    try {
      const ctx = new (window.AudioContext || window.webkitAudioContext)();
      [0, 0.25].forEach((t) => {
        const o = ctx.createOscillator(), g = ctx.createGain();
        o.frequency.value = 880; o.connect(g); g.connect(ctx.destination);
        g.gain.setValueAtTime(0.2, ctx.currentTime + t);
        o.start(ctx.currentTime + t); o.stop(ctx.currentTime + t + 0.18);
      });
    } catch (e) { /* abaikan */ }
  }

  const notifAktif = () => localStorage.getItem('simb_notif') === '1';

  function cekGempaBaru() {
    if (!terbaru) return;
    const idLama = localStorage.getItem('simb_last_id');
    const adaBaru = idLama !== null && String(terbaru.id) !== idLama;
    try { localStorage.setItem('simb_last_id', String(terbaru.id)); } catch (e) { /* abaikan */ }
    if (!adaBaru || !notifAktif()) return;
    bunyi();
    if ('Notification' in window && Notification.permission === 'granted') {
      new Notification(`Gempa M ${terbaru.magnitudo.toFixed(1)}`, { body: terbaru.wilayah });
    }
  }

  function initToggleNotif() {
    const cb = $('toggleNotif');
    if (!cb) return;
    cb.checked = notifAktif();
    cb.addEventListener('change', async () => {
      localStorage.setItem('simb_notif', cb.checked ? '1' : '0');
      if (cb.checked) {
        bunyi();   // sekaligus membuka izin audio dari interaksi pengguna
        if ('Notification' in window && Notification.permission === 'default') {
          await Notification.requestPermission();
        }
      }
    });
  }

  // ---------- muat semua data ----------
  function setStatus(diperbarui, dariCache) {
    const el = $('statusData');
    if (!el) return;
    el.innerHTML = dariCache
      ? '<span class="text-danger fw-bold"><i class="bi bi-wifi-off"></i> Offline - menampilkan data tersimpan terakhir (mungkin tidak terbaru)</span>'
      : `<i class="bi bi-arrow-repeat"></i> Diperbarui otomatis tiap 60 detik &middot; sinkron BMKG terakhir: ${esc(diperbarui || '-')}`;
  }

  async function muatSemua() {
    try {
      const [a, b, c, d] = await Promise.all([
        ambil('terbaru'),
        ambil('peta', '&hari=30'),
        ambil('dirasakan', '&limit=10'),
        ambil('daftar', '&min_mag=5&limit=15'),
      ]);
      terbaru = a.data; dataPeta = b.data; dataDirasakan = c.data; dataM5 = d.data;
      setStatus(a.diperbarui, a.dariCache);
      renderAlert(); renderDetailTerbaru(); gambarPeta();
      renderDirasakan(); renderTabel(); renderJarak();
      if (!a.dariCache) cekGempaBaru();
    } catch (err) {
      const t = $('alertTeks');
      if (t && !terbaru) t.innerHTML = '<i class="bi bi-wifi-off"></i> Data gempa belum dapat dimuat. Periksa koneksi, lalu muat ulang halaman.';
      setStatus(null, true);
    }
  }

  // ---------- event ----------
  document.addEventListener('click', (e) => {
    const t = e.target.closest('[data-fokus]');
    if (t) fokusKe(Number(t.dataset.fokus));
  });
  $('btnLokasi')?.addEventListener('click', mintaLokasi);
  initToggleNotif();

  muatSemua();
  setInterval(muatSemua, REFRESH_MS);
})();
