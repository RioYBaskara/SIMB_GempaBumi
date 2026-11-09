/* Peta lokasi terakhir terlihat (halaman detail orang hilang) */
(() => {
  'use strict';
  const el = document.getElementById('petaDetail');
  if (!el) return;

  const lat = parseFloat(el.dataset.lat);
  const lng = parseFloat(el.dataset.lng);
  if (Number.isNaN(lat) || Number.isNaN(lng)) return;

  const map = L.map(el).setView([lat, lng], 13);
  L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 18, attribution: '&copy; OpenStreetMap contributors',
  }).addTo(map);

  // textContent (bukan innerHTML) agar aman dari XSS
  const isi = document.createElement('div');
  isi.textContent = el.dataset.label || 'Lokasi terakhir terlihat';
  L.marker([lat, lng]).addTo(map).bindPopup(isi).openPopup();
})();
