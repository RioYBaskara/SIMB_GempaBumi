/**
 * assets/js/app.js
 * JavaScript kustom SIMB GempaBumi
 */

/* ============================================================
   AUTO-REFRESH GEMPA TERBARU (index.php)
   Mengambil data dari api/gempa_terbaru.php setiap 60 detik
   ============================================================ */
function inisialisasiAutoRefresh() {
    const elWaktu = document.getElementById('waktu-update');
    const elKonten = document.getElementById('konten-gempa-terbaru');
    if (!elKonten) return; // Hanya jalankan di halaman yang punya elemen ini

    function perbaruiGempa() {
        fetch(BASE_URL + '/api/gempa_terbaru.php')
            .then(r => r.json())
            .then(data => {
                if (!data.gempa) return;
                const g = data.gempa;

                // Perbarui magnitudo
                const elMag = document.getElementById('nilai-magnitudo');
                if (elMag) elMag.textContent = parseFloat(g.Magnitude).toFixed(1);

                // Perbarui wilayah
                const elWil = document.getElementById('nilai-wilayah');
                if (elWil) elWil.textContent = g.Wilayah || '-';

                // Perbarui waktu relatif
                const elRel = document.getElementById('waktu-relatif');
                if (elRel && g.DateTime) elRel.textContent = hitungWaktuRelatif(g.DateTime);

                // Perbarui label status banner
                perbaruiStatusBanner(g);

                // Update timestamp
                if (elWaktu) {
                    const skrg = new Date();
                    elWaktu.textContent = 'Terakhir diperbarui: ' +
                        skrg.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' }) + ' WIB';
                }
            })
            .catch(err => {
                console.warn('Gagal memperbarui data gempa:', err);
                if (elWaktu) elWaktu.textContent = 'Pembaruan gagal — mencoba ulang...';
            });
    }

    // Jalankan pertama kali lalu ulangi setiap 60 detik
    perbaruiGempa();
    setInterval(perbaruiGempa, 60000);
}

/* ============================================================
   HITUNG WAKTU RELATIF (di browser, tanpa PHP)
   ============================================================ */
function hitungWaktuRelatif(datetimeStr) {
    const waktu = new Date(datetimeStr);
    const sekarang = new Date();
    const selisih = Math.floor((sekarang - waktu) / 1000); // detik

    if (selisih < 60)       return 'Baru saja';
    if (selisih < 3600)     return Math.floor(selisih / 60) + ' menit yang lalu';
    if (selisih < 86400)    return Math.floor(selisih / 3600) + ' jam yang lalu';
    return Math.floor(selisih / 86400) + ' hari yang lalu';
}

/* ============================================================
   UPDATE BANNER STATUS
   ============================================================ */
function perbaruiStatusBanner(gempa) {
    const banner = document.getElementById('banner-status');
    if (!banner) return;
    const mag = parseFloat(gempa.Magnitude);
    const potensi = (gempa.Potensi || '').toLowerCase();

    banner.className = 'banner-status alert mb-0';
    if (mag >= 6.0 || potensi.includes('tsunami') && !potensi.includes('tidak berpotensi')) {
        banner.className += ' banner-merah';
        banner.querySelector('.banner-teks').textContent =
            '⚠️ PERINGATAN: Gempa signifikan M' + mag.toFixed(1) + ' terdeteksi. Waspada kemungkinan tsunami!';
    } else if (mag >= 5.0) {
        banner.className += ' banner-kuning';
        banner.querySelector('.banner-teks').textContent =
            '⚡ PERHATIAN: Gempa M' + mag.toFixed(1) + ' terdeteksi. Pantau perkembangan.';
    } else {
        banner.className += ' banner-hijau';
        banner.querySelector('.banner-teks').textContent =
            '✅ Status Normal: Tidak ada gempa signifikan dalam 24 jam terakhir.';
    }
}

/* ============================================================
   INISIALISASI PETA LEAFLET
   Dipanggil dari halaman yang butuh peta
   ============================================================ */
function inisialisasiPeta(idElemen, gempaList, latCenter, lonCenter, zoomAwal) {
    if (!document.getElementById(idElemen)) return;
    if (typeof L === 'undefined') {
        console.error('Leaflet.js belum dimuat');
        return;
    }

    latCenter = latCenter || -2.5;
    lonCenter = lonCenter || 118;
    zoomAwal  = zoomAwal  || 5;

    const peta = L.map(idElemen, { scrollWheelZoom: false }).setView([latCenter, lonCenter], zoomAwal);

    // Tile layer OpenStreetMap
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 18,
        attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
    }).addTo(peta);

    // Tambahkan marker untuk setiap gempa
    gempaList.forEach(function(g) {
        const lat = parseFloat(g.lintang || g.Lintang);
        const lon = parseFloat(g.bujur || g.Bujur);
        const mag = parseFloat(g.magnitudo || g.Magnitude);
        if (isNaN(lat) || isNaN(lon)) return;

        // Ukuran dan warna marker berdasarkan magnitudo
        const radius = Math.max(5, mag * 3);
        const warna  = mag >= 7 ? '#dc2626' : mag >= 6 ? '#ea580c' : mag >= 5 ? '#d97706' : '#2563a8';

        const marker = L.circleMarker([lat, lon], {
            radius: radius,
            fillColor: warna,
            color: '#fff',
            weight: 1.5,
            opacity: 1,
            fillOpacity: 0.8
        }).addTo(peta);

        // Popup klik
        marker.bindPopup(
            '<strong>M ' + mag.toFixed(1) + '</strong><br>' +
            (g.wilayah || g.Wilayah || '') + '<br>' +
            '<small>' + (g.datetime || g.DateTime || '') + '</small><br>' +
            'Kedalaman: ' + (g.kedalaman_km || g.Kedalaman || '?') + ' km'
        );
    });

    return peta;
}

/* ============================================================
   TOMBOL "COBA ENDPOINT" DI api-info.php
   ============================================================ */
function cobaEndpoint(url, idOutput) {
    const el = document.getElementById(idOutput);
    if (!el) return;
    el.textContent = 'Memuat...';
    fetch(BASE_URL + url)
        .then(r => r.json())
        .then(data => {
            el.textContent = JSON.stringify(data, null, 2);
        })
        .catch(err => {
            el.textContent = 'Gagal: ' + err.message;
        });
}

/* ============================================================
   KONFIRMASI HAPUS / AKSI BERBAHAYA
   ============================================================ */
document.addEventListener('DOMContentLoaded', function() {
    // Semua tombol dengan class btn-konfirmasi memerlukan konfirmasi
    document.querySelectorAll('.btn-konfirmasi').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            const pesan = this.dataset.pesan || 'Apakah Anda yakin?';
            if (!confirm(pesan)) e.preventDefault();
        });
    });

    // Inisialisasi auto-refresh jika elemen ada
    inisialisasiAutoRefresh();
});

/* ============================================================
   BASE_URL — diisi inline di halaman (PHP)
   ============================================================ */
// Pastikan BASE_URL selalu tersedia (nilai default jika tidak di-set)
if (typeof BASE_URL === 'undefined') {
    var BASE_URL = '/SIMB_GempaBumi';
}
