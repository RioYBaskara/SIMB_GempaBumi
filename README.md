# Sistem Informasi Manajemen Bencana (SIMB) - Edukasi & Pemantauan Gempa Bumi

Aplikasi web berbasis **PHP Native** dan **Bootstrap** yang dirancang sebagai platform edukasi, pemantauan data gempa bumi secara *real-time* (via API), visualisasi grafik statistik gempa, serta layanan pencarian orang hilang akibat bencana.

---

## 📌 Fitur Utama

### 1. Informasi & Edukasi Gempa Bumi
Modul edukasi interaktif untuk meningkatkan kesadaran dan kesiapsiagaan masyarakat terhadap bencana gempa bumi:
* **Definisi Bencana:** Penjelasan mendasar mengenai apa itu gempa bumi.
* **Penyebab:** Faktor-faktor pemicu terjadinya gempa bumi (tektonik, vulkanik, dll.).
* **Dampak terhadap Manusia & Ekonomi:** Analisis dampak negatif serta sudut pandang pemulihan/dampak positif (rekontruksi, kesadaran struktural).
* **Cara Penanggulangan:** Panduan langkah-langkah sebelum terjadi gempa, saat kejadian, dan pasca-bencana.
* **Mitigasi Bencana:** Strategi dan upaya meminimalisir risiko serta kerugian jika bencana terjadi.

### 2. Monitoring & Visualisasi Data Gempa (API)
* **Integrasi API:** Mengambil data parameter gempa bumi terkini secara langsung melalui API publik (misal: API BMKG).
* **Perekaman Data:** Menyimpan riwayat data gempa ke dalam database MySQL.
* **Grafik Visualisasi:** Menyajikan statistik frekuensi dan magnitudo gempa dalam bentuk grafik interaktif (menggunakan Chart.js / Google Charts).

### 3. Fitur Cari Orang Hilang
Fasilitas pelaporan dan pencarian anggota keluarga atau kerabat yang hilang saat bencana terjadi, mencakup data:
* Data Diri (Nama Lengkap, Usia, Jenis Kelamin, Foto/Ciri-ciri).
* Lokasi Terakhir Terlihat.
* Domisili / Alamat Asal.
* Kontak Informasi Nama Keluarga / Pelapor.

---

## 🛠️ Teknologi yang Digunakan

* **Language:** PHP (Native)
* **Database:** MySQL
* **Frontend:** Bootstrap (Responsive Web Design), HTML5, CSS3, JavaScript
* **Visualization:** Chart.js (atau Library Grafik JS lainnya)
* **API Integration:** API Data Gempa Bumi

---

## 🚀 Panduan Instalasi & Penggunaan

### 1. Prasyarat Sistem
* Web Server (XAMPP / WAMP / Laragon)
* PHP versi 7.4 ke atas
* MySQL Database

### 2. Langkah-Langkah Instalasi
1. **Clone Repositori ini:**
   ```bash
   git clone https://github.com/username/nama-repo-simb.git
   cd nama-repo-simb
   ```

2. **Pindahkan Folder Project:**
   Pindahkan folder project ke direktori web server Anda (misal: `htdocs` di XAMPP atau `www` di Laragon).

3. **Import Database:**
   * Buka **phpMyAdmin** (`http://localhost/phpmyadmin`).
   * Buat database baru dengan nama `db_simb` (atau sesuaikan).
   * Import file database yang berada di direktori project: `database/db_simb.sql`.

4. **Konfigurasi Koneksi Database:**
   Buka file koneksi (misal `config/koneksi.php`) dan sesuaikan kredensial database Anda:
   ```php
   $host = "localhost";
   $user = "root";
   $pass = "";
   $db   = "db_simb";

   $koneksi = mysqli_connect($host, $user, $pass, $db);
   ```

5. **Jalankan Aplikasi:**
   Buka browser dan akses URL:
   ```text
   http://localhost/SIMB_GempaBumi
   ```
