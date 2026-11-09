# SIMB GempaBumi — Sistem Informasi Manajemen Bencana

Aplikasi web edukasi dan pemantauan gempa bumi berbasis PHP Native + MySQL + Bootstrap 5.

## 📋 Persyaratan

- XAMPP / Laragon (PHP 7.4+, MySQL 5.7+)
- Koneksi internet (untuk mengambil data BMKG dan CDN Bootstrap/Leaflet/Chart.js)

---

## 🚀 Cara Instalasi

### 1. Salin folder ke htdocs

```
C:\xampp\htdocs\SIMB_GempaBumi\
```

atau untuk Laragon:

```
C:\laragon\www\SIMB_GempaBumi\
```

### 2. Import database

- Buka **phpMyAdmin** → http://localhost/phpmyadmin
- Klik **Import** → pilih file `database/db_simb.sql`
- Klik **Go**

Database `db_simb` akan otomatis dibuat beserta tabel dan data dummy.

### 3. Sesuaikan konfigurasi database

Buka `config/koneksi.php` dan sesuaikan jika perlu:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');       // Kosong untuk XAMPP default
define('DB_NAME', 'db_simb');
```

### 4. Buka di browser

```
http://localhost/SIMB_GempaBumi/
```

---

## 🖼️ Konfigurasi API Key Gambar (Opsional)

Gambar ilustrasi di halaman Edukasi diambil dari Google Images via Google Custom Search API.
Tanpa API key, gambar dummy dari Wikimedia Commons tetap ditampilkan (sudah termasuk di database SQL).

### Langkah 1 — Buat Search Engine

1. Buka [Google Programmable Search Engine](https://programmablesearchengine.google.com/)
2. Klik **Add** → isi nama dan **tambahkan "www.google.com"** sebagai situs pencarian
3. Aktifkan **"Image search"** dan **"Search the entire web"**
4. Salin **Search Engine ID (cx)** yang tampil

### Langkah 2 — Aktifkan Custom Search API

1. Buka [Google Cloud Console](https://console.cloud.google.com/)
2. Buat project baru (atau pilih yang ada)
3. **APIs & Services** → **Library** → cari **"Custom Search API"** → Enable
4. **APIs & Services** → **Credentials** → **Create Credentials** → **API key**
5. Salin API key

### Langkah 3 — Isi di config/api_key.php

```php
define('GOOGLE_API_KEY', 'AIza...KUNCI_ANDA');
define('GOOGLE_CX',      '12345...ID_SEARCH_ENGINE');
```

> ⚠️ Kuota gratis Google Custom Search: **100 query/hari**. Hasil pencarian di-cache 7 hari di database untuk menghemat kuota.

---

## 👤 Login Admin

- URL: http://localhost/SIMB_GempaBumi/admin/login.php
- Username default: `admin`
- Password default: `admin123`

> ⚠️ Segera ganti password di database setelah instalasi!

Untuk mengganti password:
```sql
UPDATE admin SET password_hash = PASSWORD_HASH('password_baru', PASSWORD_DEFAULT) WHERE username = 'admin';
```

Atau via PHP:
```php
echo password_hash('password_baru_anda', PASSWORD_DEFAULT);
```
Lalu update kolom `password_hash` di tabel `admin`.

---

## 📁 Struktur Folder

```
SIMB_GempaBumi/
├── index.php                 Beranda & informasi gempa terbaru
├── edukasi.php               Materi edukasi gempa (5 bagian)
├── monitoring.php            Riwayat & grafik data gempa
├── orang-hilang.php          Daftar orang hilang (kartu)
├── lapor-orang-hilang.php    Form pelaporan orang hilang
├── detail-orang-hilang.php   Detail laporan + tandai ditemukan
├── api-info.php              Dokumentasi API data gempa
├── config/
│   ├── koneksi.php           Konfigurasi database
│   ├── api_key.php           API Key Google (JANGAN di-commit!)
│   └── api_key.example.php   Contoh file api_key.php
├── includes/
│   ├── header.php            Template HTML head
│   ├── footer.php            Template footer + script CDN
│   ├── navbar.php            Navigasi utama
│   ├── fungsi.php            Fungsi-fungsi pembantu
│   └── _gambar_edukasi.php   Komponen grid gambar edukasi
├── api/
│   ├── gempa_terbaru.php     JSON: 1 gempa terbaru BMKG
│   ├── gempa_terkini.php     JSON: 15 gempa M5.0+ terkini
│   ├── gempa_dirasakan.php   JSON: 15 gempa dirasakan
│   ├── statistik.php         JSON: data untuk Chart.js
│   └── cari_gambar.php       JSON: cari gambar Google
├── admin/
│   ├── login.php             Login admin
│   └── gambar.php            Kelola gambar & laporan
├── assets/
│   ├── css/style.css         CSS kustom
│   ├── js/app.js             JavaScript kustom
│   └── img/                  Logo, placeholder SVG
├── uploads/
│   └── orang_hilang/         Foto upload orang hilang
└── database/
    └── db_simb.sql           Schema + data dummy
```

---

## 🔌 Sumber Data

- **BMKG Open Data**: https://data.bmkg.go.id/gempabumi/
- **OpenStreetMap** (via Leaflet.js): peta interaktif
- **Google Custom Search API**: gambar ilustrasi edukasi

---

## 🔧 Hal yang Bisa Dikembangkan

1. **Notifikasi Push** — kirim notifikasi browser saat ada gempa M≥5.0 baru
2. **Export PDF/Excel** — ekspor riwayat gempa untuk pelaporan
3. **Peta Risiko** — overlay zona rawan gempa dari peta BNPB
4. **Multi-bahasa** — tambahkan dukungan bahasa Inggris
5. **Autentikasi pelapor** — OTP via WhatsApp untuk laporan orang hilang
6. **Scraping data historis BMKG** — isi database dengan data gempa beberapa tahun terakhir
7. **Grafik scatter** — plot koordinat gempa di peta heatmap
8. **Integrasi USGS** — fallback ke API USGS jika BMKG tidak tersedia
9. **PWA (Progressive Web App)** — tambahkan manifest + service worker agar bisa diinstal di HP
10. **Dark mode** — toggle tema gelap/terang

---

## ⚠️ Catatan Keamanan

- Jangan publikasikan `config/api_key.php` ke repositori publik (sudah ada di .gitignore)
- Ganti password admin default secepatnya
- Folder `uploads/` sudah dibuat, pastikan permission-nya 755 di Linux
- Aplikasi ini untuk tujuan akademik — untuk produksi, tambahkan HTTPS dan hardening tambahan
