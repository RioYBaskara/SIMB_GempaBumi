/* Halaman edukasi: checklist tas siaga + pengaktifan tab "Saat gempa" lewat link #saat */
(() => {
  'use strict';

  const KUNCI = 'simb_tas_siaga';
  const cek = Array.from(document.querySelectorAll('.tas-cek'));
  const bar = document.getElementById('tasBar');
  const persen = document.getElementById('tasPersen');
  const status = document.getElementById('tasStatus');
  const progress = document.querySelector('#tas-siaga .progress');

  // ---------- muat & simpan (aman jika penyimpanan browser diblokir) ----------
  function muat() {
    try { return JSON.parse(localStorage.getItem(KUNCI)) || {}; } catch (e) { return {}; }
  }
  function simpan() {
    const data = {};
    cek.forEach((c) => { if (c.checked) data[c.dataset.key] = 1; });
    try { localStorage.setItem(KUNCI, JSON.stringify(data)); } catch (e) { /* abaikan */ }
  }

  function hitung() {
    const total = cek.length;
    const done = cek.filter((c) => c.checked).length;
    const p = total ? Math.round((done / total) * 100) : 0;
    bar.style.width = p + '%';
    bar.className = 'progress-bar ' + (p >= 80 ? 'bg-success' : p >= 40 ? 'bg-warning' : 'bg-danger');
    persen.textContent = `${p}% (${done}/${total})`;
    progress.setAttribute('aria-valuenow', String(p));
    status.textContent = p >= 80 ? 'Siap' : p >= 40 ? 'Cukup, perlu dilengkapi' : 'Perlu ditingkatkan';
  }

  const tersimpan = muat();
  cek.forEach((c) => {
    c.checked = !!tersimpan[c.dataset.key];
    c.addEventListener('change', () => { simpan(); hitung(); });
  });
  hitung();

  document.getElementById('btnReset')?.addEventListener('click', () => {
    if (!confirm('Hapus semua centang?')) return;
    cek.forEach((c) => { c.checked = false; });
    simpan(); hitung();
  });
  document.getElementById('btnCetak')?.addEventListener('click', () => window.print());

  // ---------- link #saat harus membuka tab "SAAT gempa" ----------
  function bukaTabSaat() {
    if (location.hash !== '#saat' || typeof bootstrap === 'undefined') return;
    const tombol = document.getElementById('saat');
    if (tombol) bootstrap.Tab.getOrCreateInstance(tombol).show();
  }
  window.addEventListener('hashchange', bukaTabSaat);
  bukaTabSaat();
})();
