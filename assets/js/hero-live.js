/* SIMB V3 live hero: read-only observer; core app.js remains untouched. */
(() => {
  'use strict';
  const $ = (id) => document.getElementById(id);
  const detail = $('detailTerbaru');
  if (!detail) return;
  const set = (id, value) => { const el = $(id); if (el) el.textContent = value == null || value === '' ? '—' : String(value); };
  const relative = (iso) => {
    const date = new Date(iso); if (Number.isNaN(date.getTime())) return 'Waktu tidak tersedia';
    const mins = Math.max(0, Math.floor((Date.now() - date.getTime()) / 60000));
    if (mins < 1) return 'Baru saja'; if (mins < 60) return `${mins} menit lalu`;
    const hrs = Math.floor(mins / 60); if (hrs < 24) return `${hrs} jam lalu`; return `${Math.floor(hrs / 24)} hari lalu`;
  };
  const clock = (iso) => { const d = new Date(iso); return Number.isNaN(d.getTime()) ? '—' : d.toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' }); };
  function show(g, offline = false) {
    if (!g || typeof g !== 'object' || !Number.isFinite(Number(g.magnitudo))) { error(); return; }
    const mag = Number(g.magnitudo), tsunami = g.potensi && /tsunami/i.test(g.potensi) && !/tidak/i.test(g.potensi);
    const hero = document.querySelector('.rd-hero-live');
    hero.classList.toggle('hx-danger', mag >= 6 || !!tsunami); hero.classList.toggle('hx-warning', mag >= 5 && mag < 6 && !tsunami); hero.classList.toggle('hx-info', mag < 5 && !tsunami);
    $('hx-tsunami').hidden = !tsunami;
    set('hx-status', offline ? 'OFFLINE · data tersimpan terakhir' : 'LIVE · Diperbarui otomatis tiap 60 detik');
    set('hx-mag', `M ${mag.toFixed(1).replace('.', ',')}`); set('hx-region', g.wilayah || 'Wilayah tidak tersedia');
    set('hx-relative', `${relative(g.waktu_iso)} · Data gempa terbaru`); set('hx-time', clock(g.waktu_iso));
    set('hx-depth', `${g.kedalaman ?? '—'} km`); set('hx-coord', `${Number(g.lintang).toFixed(2)}, ${Number(g.bujur).toFixed(2)}`);
    set('hx-impact', [g.potensi, g.dirasakan ? `Dirasakan: ${g.dirasakan}` : ''].filter(Boolean).join(' · ') || 'Informasi belum tersedia');
    const map = $('hx-map'); if (map) map.setAttribute('data-fokus', g.id == null ? '' : String(g.id));
    $('hx-error').hidden = true; $('hx-data').hidden = false;
    document.querySelector('.hx-waves').classList.toggle('hx-still', offline);
  }
  function error() { $('hx-data').hidden = true; $('hx-error').hidden = false; set('hx-region', 'Data gempa belum tersedia'); }
  function readCache() {
    try { const raw = localStorage.getItem('simb_cache_terbaru'); if (!raw) return null; const parsed = JSON.parse(raw); return parsed && parsed.data ? parsed.data : parsed; } catch (_) { return null; }
  }
  function update() { const g = readCache(); if (g) show(g, navigator.onLine === false); }
  const observer = new MutationObserver(update); observer.observe(detail, { childList: true, subtree: true, characterData: true });
  window.addEventListener('online', update); window.addEventListener('offline', update);
  $('hx-reload')?.addEventListener('click', () => window.location.reload());
  // Safe fallback only when no core cache exists. No data is inserted as HTML.
  if (!readCache()) fetch('api/gempa_json.php?aksi=terbaru', { cache: 'no-store' }).then(r => r.json()).then(json => show(json && json.data ? json.data : json, false)).catch(error);
  else update();
})();
