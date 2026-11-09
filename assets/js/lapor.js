/* Peta pemilih lokasi pada form lapor orang hilang */
(() => {
  'use strict';
  const el = document.getElementById('petaLapor');
  if (!el) return;

  const inLat = document.getElementById('lintang');
  const inLng = document.getElementById('bujur');
  const map = L.map(el).setView([-2.5, 118], 5);
  L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 18, attribution: '&copy; OpenStreetMap contributors',
  }).addTo(map);

  let marker = null;

  function setTitik(la, lo, zoom) {
    inLat.value = la.toFixed(5);
    inLng.value = lo.toFixed(5);
    if (marker) marker.setLatLng([la, lo]);
    else marker = L.marker([la, lo]).addTo(map);
    if (zoom) map.setView([la, lo], zoom);
  }

  if (inLat.value && inLng.value) setTitik(parseFloat(inLat.value), parseFloat(inLng.value), 12);

  map.on('click', (e) => setTitik(e.latlng.lat, e.latlng.lng));

  document.getElementById('btnHapusTitik').addEventListener('click', () => {
    inLat.value = ''; inLng.value = '';
    if (marker) { marker.remove(); marker = null; }
  });

  document.getElementById('btnGps').addEventListener('click', (ev) => {
    const btn = ev.currentTarget;
    if (!navigator.geolocation) { alert('Browser tidak mendukung geolokasi.'); return; }
    btn.disabled = true;
    navigator.geolocation.getCurrentPosition(
      (p) => { setTitik(p.coords.latitude, p.coords.longitude, 14); btn.disabled = false; },
      () => { alert('Lokasi tidak dapat diakses. Klik peta untuk menandai manual.'); btn.disabled = false; },
      { timeout: 15000 }
    );
  });
})();
