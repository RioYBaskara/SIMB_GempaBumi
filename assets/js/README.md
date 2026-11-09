# Sistem Informasi Manajemen Bencana (SIMB) - Edukasi & Pemantauan Gempa Bumi

Aplikasi web berbasis **PHP Native** dan **Bootstrap 5** yang berfungsi sebagai platform edukasi, pemantauan gempa bumi terkini (via API BMKG), visualisasi statistik, serta layanan pelaporan dan pencarian orang hilang akibat bencana.

**Prinsip desain:** fungsi di atas estetika. Informasi paling mendesak (gempa terbaru, peta, kontak darurat) tampil lebih dulu, baru edukasi. Pengguna umum **tidak perlu login** karena sifatnya darurat; hanya admin yang login.

---

## 📌 Fitur

### 1. Pemantauan Gempa Real-time (API BMKG)
* **Alert bar** gempa terbaru di bagian paling atas. Warna otomatis: **merah** (M ≥ 6 atau berpotensi tsunami), **kuning** (M 5-5,9), **abu** (lebih kecil).
* **Peta interaktif (Leaflet + OpenStreetMap)**: sebaran gempa 30 hari terakhir; ukuran dan warna marker mengikuti magnitudo.
* **Gempa dirasakan**: daftar wilayah yang merasakan guncangan beserta skala MMI.
* **Daftar gempa M 5,0+** terkini, klik baris untuk melihat lokasinya di peta.
* **Jarak dari lokasimu** (rumus Haversine, dihitung di browser; lokasi tidak dikirim ke server).
* **Auto-refresh** tiap 60 detik, **bunyi/notifikasi** saat ada gempa baru, dan **data tersimpan** tetap tampil saat offline.
* **Perekaman riwayat** gempa ke MySQL (anti-duplikat) dengan throttle agar server BMKG tidak dipanggil berlebihan.

### 2. Statistik & Visualisasi (Chart.js)
* Filter rentang tanggal (7 hari, 30 hari, 90 hari, 1 tahun, atau kustom).
* Ringkasan: total gempa, rata-rata magnitudo, jumlah M ≥ 5, jumlah gempa dirasakan, dan gempa terkuat.
* Grafik frekuensi + magnitudo maksimum, distribusi magnitudo, distribusi kedalaman, dan 5 lokasi teratas.

### 3. Orang Hilang (tanpa login)
* **Lapor**: data diri, usia, jenis kelamin, ciri-ciri, foto, lokasi terakhir (opsional ditandai di peta), domisili, dan kontak pelapor.
* **Verifikasi admin**: laporan baru berstatus *menunggu* dan baru tampil publik setelah disetujui.
* **Cari**: filter nama/lokasi, status, jenis kelamin, dan rentang usia, dengan paginasi.
* **Status**: Masih dicari / Ditemukan / Dalam perawatan. Pelapor mengubahnya lewat **link rahasia (token)** tanpa akun.
* **Detail** dengan peta, tombol telepon/WhatsApp pelapor, dan **Bagikan ke WhatsApp**.

### 4. Kontak Darurat
Tombol telepon satu sentuhan (BNPB 117, Basarnas 115, ambulans 119, dll.). Daftar dikelola admin dari database.

### 5. Edukasi Gempa Bumi
Definisi, penyebab, dampak (manusia, ekonomi, bahaya ikutan, sisi pemulihan), penanggulangan (sebelum/saat/sesudah), mitigasi, panduan skala magnitudo dan MMI, **mitos vs fakta**, serta **checklist tas siaga** interaktif yang bisa dicetak.

### 6. Panel Admin
Login aman, dashboard ringkasan, verifikasi laporan (setujui/tolak dengan alasan/hapus), deteksi duplikat dan spam, kelola kontak darurat, dan sinkron manual data BMKG.

---

## 🛠️ Teknologi

| Bagian | Teknologi |
|---|---|
| Bahasa | PHP Native (7.4+) |
| Database | MySQL / MariaDB (`mysqli`, prepared statements) |
| Frontend | Bootstrap 5, Bootstrap Icons, HTML5, CSS3, JavaScript |
| Peta | Leaflet + OpenStreetMap |
| Grafik | Chart.js |
| Sumber data | [Data Gempa Bumi Terbuka BMKG](https://data.bmkg.go.id/gempabumi) |

---

## 📁 Struktur Folder

```text
SIMB_GempaBumi/
├── index.php                 # Halaman utama (alert, peta, kontak darurat, statistik, edukasi ringkas)
├── edukasi.php               # Halaman edukasi lengkap + checklist tas siaga
├── README.md
│
├── config/
│   └── koneksi.php           # Koneksi database & SINKRON_KEY
│
├── includes/                 # Kode bersama (bukan halaman)
│   ├── header.php            # <head>, navbar, alert bar
│   ├── footer.php            # Footer & pemuatan script
│   ├── fungsi.php            # Sesi, CSRF, rate limit, upload foto, helper tampilan
│   ├── bmkg.php              # Integrasi API BMKG: ambil, normalisasi, simpan, throttle
│   ├── statistik.php         # Perhitungan statistik gempa
│   └── admin.php             # Autentikasi & layout panel admin
│
├── api/                      # Endpoint (JSON)
│   ├── gempa_json.php        # Data untuk frontend: terbaru, daftar, dirasakan, peta, statistik, status
│   └── sinkron_gempa.php     # Pemicu sinkron BMKG -> MySQL (cron / Task Scheduler)
│
├── orang-hilang/             # Modul orang hilang (publik, tanpa login)
│   ├── lapor.php             # Form laporan
│   ├── sukses.php            # Kode laporan + link rahasia pengelolaan
│   ├── cari.php              # Pencarian publik + filter
│   ├── detail.php            # Detail laporan terverifikasi
│   └── kelola.php            # Ubah status lewat token pelapor
│
├── admin/                    # Panel admin (wajib login, kecuali setup & login)
│   ├── setup.php             # Pembuatan admin pertama (hapus setelah dipakai)
│   ├── login.php
│   ├── logout.php
│   ├── index.php             # Dashboard
│   ├── laporan.php           # Daftar laporan per status
│   ├── tinjau.php            # Tinjau, setujui, tolak, hapus
│   └── kontak.php            # Kelola kontak darurat
│
├── assets/
│   ├── css/
│   │   └── style.css
│   └── js/
│       ├── app.js            # Alert bar, peta, gempa dirasakan, jarak, auto-refresh
│       ├── statistik.js      # Grafik Chart.js + filter tanggal
│       ├── lapor.js          # Pemilih lokasi di form lapor
│       ├── detail.js         # Peta di halaman detail
│       └── edukasi.js        # Checklist tas siaga
│
├── uploads/                  # Foto orang hilang (harus bisa ditulis server)
│   ├── .htaccess             # Menolak eksekusi script & daftar isi folder
│   └── index.html
│
└── database/
    └── db_simb.sql           # Skema + data awal
```

### Skema database (`db_simb`)

| Tabel | Fungsi |
|---|---|
| `gempa` | Riwayat gempa dari BMKG (`kode_unik` mencegah duplikat) |
| `orang_hilang` | Laporan orang hilang, status verifikasi, status orang, token pelapor |
| `kontak_darurat` | Daftar nomor darurat yang dikelola admin |
| `admin` | Akun admin (password di-hash) |
| `log_sinkron_api` | Catatan sinkron BMKG, dasar throttle |
| `login_gagal` | Pembatas percobaan login admin |

---

## 🚀 Instalasi

### 1. Prasyarat
* Web server (XAMPP / WAMP / Laragon)
* **PHP 7.4+** dengan ekstensi: `mysqli`, `curl`, `mbstring`, `json`. Disarankan `gd` (olah foto) dan `exif` (koreksi rotasi foto HP).
* MySQL / MariaDB
* Koneksi internet (data BMKG, peta, dan library Bootstrap/Leaflet/Chart.js dimuat dari CDN)

### 2. Langkah-langkah
1. **Clone repositori:**
   ```bash
   git clone https://github.com/username/nama-repo-simb.git
   ```
2. **Pindahkan folder** ke `htdocs` (XAMPP) atau `www` (Laragon).
3. **Import database:** buka phpMyAdmin, buat database `db_simb`, lalu import `database/db_simb.sql`.
4. **Atur koneksi** di `config/koneksi.php`:
   ```php
   $host = "localhost";
   $user = "root";
   $pass = "";
   $db   = "db_simb";
   ```
   Ganti juga `SINKRON_KEY` dengan string acak milikmu.
5. **Buat akun admin:** buka `http://localhost/SIMB_GempaBumi/admin/setup.php` (kunci setup = `SINKRON_KEY`), lalu **hapus file `admin/setup.php`**.
6. **Pastikan `uploads/` dapat ditulis** oleh web server (di Linux: `chmod 775 uploads`).
7. **Jalankan aplikasi:** `http://localhost/SIMB_GempaBumi`

### 3. Sinkronisasi data BMKG (disarankan)
Selama ada pengunjung, data diperbarui otomatis. Agar riwayat dan statistik terus terkumpul, jadwalkan sinkron berkala (tiap 1-5 menit):

```bash
# Linux (cron)
*/2 * * * * php /path/ke/SIMB_GempaBumi/api/sinkron_gempa.php

# Windows: Task Scheduler -> jalankan php.exe dengan argumen path file di atas
```

> **Catatan XAMPP Windows:** bila muncul error *SSL certificate problem*, arahkan `curl.cainfo` di `php.ini` ke file CA bundle (`cacert.pem`).

---

## 🔌 Endpoint API

Semua lewat `api/gempa_json.php?aksi=...`

| Aksi | Parameter | Keterangan |
|---|---|---|
| `terbaru` | - | Gempa paling baru |
| `daftar` | `min_mag`, `limit` | Daftar gempa (maks 200) |
| `dirasakan` | `limit` | Gempa yang dirasakan warga |
| `peta` | `hari` | Titik gempa untuk peta (maks 365 hari) |
| `statistik` | `dari`, `sampai` (Y-m-d) | Statistik untuk grafik (maks 1 tahun) |
| `status` | - | Jumlah data dan hasil sinkron |

Sinkron manual/cron: `api/sinkron_gempa.php?key=SINKRON_KEY` (tambah `&paksa=1` untuk melewati throttle).

---

## 🔒 Keamanan

* Prepared statements di seluruh query dan `htmlspecialchars` di seluruh output.
* Token **CSRF** pada semua form POST (termasuk logout).
* Password admin memakai `password_hash()`; pembatasan **5 kali gagal login per 15 menit per IP**; session diganti saat login dan berakhir setelah 30 menit tidak aktif.
* Form lapor: **honeypot**, **batas 5 laporan per IP per jam**, validasi sisi server, dan **verifikasi admin** sebelum tampil publik.
* Foto: validasi tipe asli gambar, dibuat ulang menjadi JPG (menghapus data GPS/EXIF), nama file diacak, dan folder `uploads/` menolak eksekusi script.
* Pengelolaan laporan tanpa akun memakai token acak 128-bit (`random_bytes`) yang dibandingkan dengan `hash_equals`.

---

## ⚠️ Catatan & Batasan

* **Statistik bukan katalog lengkap.** BMKG hanya menyediakan daftar terbatas (gempa M5,0+, gempa dirasakan, dan gempa terbaru). Gempa kecil tidak tercakup, dan grafik baru bermakna setelah sinkron berjalan beberapa hari.
* Pengelompokan **lokasi teratas** bersifat perkiraan karena format teks wilayah BMKG bervariasi.
* Aplikasi ini ditujukan sebagai proyek edukasi. Untuk keputusan keselamatan, **selalu rujuk informasi resmi BMKG dan BNPB**.
* Gunakan **HTTPS** saat di-deploy ke server sungguhan.
* Aplikasi dibuat tanpa framework dan belum dilengkapi pengujian otomatis; lakukan uji manual pada tiap fitur sebelum dipakai.

---

## 🧭 Rencana Pengembangan

* PWA (dapat dipasang di HP dan sebagian halaman tersedia offline)
* Notifikasi Telegram/email saat gempa M ≥ 5
* Peta posko pengungsian dan titik kumpul, serta laporan "Saya aman"
* Ekspor data gempa ke CSV/PDF
* Mode kontras tinggi dan pengaturan ukuran huruf

---

## 📚 Sumber Data & Kredit

* Data gempa bumi: BMKG (Badan Meteorologi, Klimatologi, dan Geofisika), https://data.bmkg.go.id/gempabumi
* Peta: © kontributor OpenStreetMap
* Library: Bootstrap, Bootstrap Icons, Leaflet, Chart.js

admin
admin123