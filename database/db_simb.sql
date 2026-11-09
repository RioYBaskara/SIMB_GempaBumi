-- ============================================================
-- SIMB GempaBumi - Database Schema
-- db_simb.sql
-- ============================================================

CREATE DATABASE IF NOT EXISTS db_simb CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE db_simb;

-- Tabel data gempa bumi dari BMKG
CREATE TABLE IF NOT EXISTS gempa (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    datetime    DATETIME NOT NULL,
    tanggal     VARCHAR(20),
    jam         VARCHAR(20),
    lintang     DECIMAL(8,4) NOT NULL,
    bujur       DECIMAL(8,4) NOT NULL,
    magnitudo   DECIMAL(3,1) NOT NULL,
    kedalaman_km INT,
    wilayah     VARCHAR(255),
    potensi     VARCHAR(255),
    dirasakan   TEXT,
    shakemap    VARCHAR(255),
    sumber      ENUM('autogempa','terkini','dirasakan') DEFAULT 'terkini',
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_gempa (datetime, lintang, bujur)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel pelaporan orang hilang
CREATE TABLE IF NOT EXISTS orang_hilang (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    nama            VARCHAR(150) NOT NULL,
    usia            INT,
    jenis_kelamin   ENUM('Laki-laki','Perempuan') NOT NULL,
    foto            VARCHAR(255),
    ciri_ciri       TEXT,
    lokasi_terakhir VARCHAR(255),
    tanggal_hilang  DATE,
    domisili        VARCHAR(255),
    nama_pelapor    VARCHAR(150),
    hubungan        VARCHAR(100),
    kontak_pelapor  VARCHAR(20),
    status          ENUM('dicari','ditemukan') DEFAULT 'dicari',
    kode_verifikasi VARCHAR(10),
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel cache gambar edukasi (dari Google Custom Search)
CREATE TABLE IF NOT EXISTS gambar_edukasi (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    bagian      ENUM('definisi','penyebab','dampak','penanggulangan','mitigasi') NOT NULL,
    kata_kunci  VARCHAR(255),
    url_gambar  TEXT NOT NULL,
    thumbnail   TEXT,
    judul       VARCHAR(500),
    sumber      VARCHAR(255),
    link_sumber TEXT,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel cache API BMKG (minimal 60 detik)
CREATE TABLE IF NOT EXISTS api_cache (
    cache_key   VARCHAR(100) PRIMARY KEY,
    data        LONGTEXT,
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel admin (opsional)
CREATE TABLE IF NOT EXISTS admin (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    username        VARCHAR(100) UNIQUE NOT NULL,
    password_hash   VARCHAR(255) NOT NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- DATA DUMMY GEMPA
-- ============================================================
INSERT IGNORE INTO gempa (datetime, tanggal, jam, lintang, bujur, magnitudo, kedalaman_km, wilayah, potensi, dirasakan, shakemap, sumber) VALUES
('2024-11-07 14:32:00', '07-Nov-24', '14:32:00 WIB', -8.5234, 115.3456, 6.2, 15, '27 km BaratLaut GIANYAR-BALI', 'Gempa ini berpotensi tsunami', 'Dirasakan (IV MMI) di Gianyar, Denpasar (III MMI) Kuta', '20241107143200.mmi.jpg', 'terkini'),
('2024-11-07 09:15:00', '07-Nov-24', '09:15:00 WIB', -2.1234, 139.5678, 5.8, 45, '89 km Timur Laut JAYAPURA-PAPUA', 'Tidak berpotensi tsunami', 'Dirasakan (III MMI) di Jayapura', '20241107091500.mmi.jpg', 'terkini'),
('2024-11-06 22:45:00', '06-Nov-24', '22:45:00 WIB', -8.4521, 115.2987, 4.5, 10, '15 km Barat DENPASAR-BALI', 'Tidak berpotensi tsunami', 'Dirasakan (II MMI) di Denpasar', NULL, 'dirasakan'),
('2024-11-06 18:20:00', '06-Nov-24', '18:20:00 WIB', -6.8765, 107.6543, 5.1, 85, '45 km Selatan BANDUNG-JABAR', 'Tidak berpotensi tsunami', 'Dirasakan (II-III MMI) di Bandung, Garut', '20241106182000.mmi.jpg', 'terkini'),
('2024-11-06 11:05:00', '06-Nov-24', '11:05:00 WIB', -3.5432, 128.7654, 6.5, 30, '72 km Barat Daya AMBON-MALUKU', 'Gempa ini berpotensi tsunami', 'Dirasakan (V MMI) di Ambon, Masohi (IV MMI)', '20241106110500.mmi.jpg', 'terkini'),
('2024-11-05 16:33:00', '05-Nov-24', '16:33:00 WIB', -9.1234, 118.5432, 4.8, 20, '35 km Selatan SUMBAWA-NTB', 'Tidak berpotensi tsunami', NULL, NULL, 'terkini'),
('2024-11-05 08:12:00', '05-Nov-24', '08:12:00 WIB', 1.2345, 124.5678, 5.3, 65, '40 km Utara MANADO-SULUT', 'Tidak berpotensi tsunami', 'Dirasakan (II MMI) di Manado', NULL, 'terkini'),
('2024-11-04 20:55:00', '04-Nov-24', '20:55:00 WIB', -7.5123, 110.3456, 4.2, 12, '18 km Timur YOGYAKARTA-DIY', 'Tidak berpotensi tsunami', 'Dirasakan (II MMI) di Yogyakarta', NULL, 'dirasakan'),
('2024-11-04 13:28:00', '04-Nov-24', '13:28:00 WIB', -0.8765, 131.2345, 5.6, 55, '55 km Barat Daya SORONG-PAPUA BARAT', 'Tidak berpotensi tsunami', 'Dirasakan (III MMI) di Sorong', NULL, 'terkini'),
('2024-11-03 07:44:00', '03-Nov-24', '07:44:00 WIB', -10.2345, 120.1234, 5.0, 40, '60 km Barat Laut ENDE-NTT', 'Tidak berpotensi tsunami', NULL, NULL, 'terkini');

-- ============================================================
-- DATA DUMMY ORANG HILANG
-- ============================================================
INSERT IGNORE INTO orang_hilang (nama, usia, jenis_kelamin, foto, ciri_ciri, lokasi_terakhir, tanggal_hilang, domisili, nama_pelapor, hubungan, kontak_pelapor, status, kode_verifikasi) VALUES
('Budi Santoso', 35, 'Laki-laki', NULL, 'Tinggi 170 cm, kulit sawo matang, rambut pendek hitam, ada tato burung di lengan kanan, terakhir memakai kaos merah dan celana jeans biru', 'Kecamatan Gianyar, dekat jalan raya Gianyar-Klungkung', '2024-11-07', 'Jl. Melati No. 5, Gianyar, Bali', 'Sri Wahyuni', 'Istri', '08123456789', 'dicari', 'BUD001'),
('Dewi Rahayu', 28, 'Perempuan', NULL, 'Tinggi 158 cm, kulit kuning langsat, rambut panjang sebahu, memakai kerudung hijau, baju batik coklat, dan tas ransel hitam', 'Jalan dekat Pantai Lebak, Lebak Selatan', '2024-11-06', 'Jl. Anggrek No. 12, Lebak, Banten', 'Ahmad Fauzi', 'Suami', '08234567890', 'dicari', 'DEW002'),
('Slamet Riyadi', 62, 'Laki-laki', NULL, 'Tinggi 165 cm, rambut putih beruban, memakai kacamata, jaket abu-abu, terakhir membawa tongkat kayu', 'Dekat Pasar Ambon, Maluku', '2024-11-06', 'Jl. Pattimura No. 8, Ambon, Maluku', 'Ratna Dewi', 'Anak', '08345678901', 'ditemukan', 'SLA003'),
('Anita Kusuma', 19, 'Perempuan', NULL, 'Tinggi 162 cm, rambut hitam panjang diikat, memakai seragam putih abu-abu (pelajar), membawa tas sekolah hitam', 'Terminal Bus Sorong, Papua Barat', '2024-11-04', 'Jl. Cendrawasih No. 21, Sorong, Papua Barat', 'Hendra Kusuma', 'Ayah', '08456789012', 'dicari', 'ANI004'),
('Rahmat Hidayat', 45, 'Laki-laki', NULL, 'Tinggi 172 cm, berbadan gemuk, berkumis tebal, terakhir memakai kemeja kotak-kotak biru, celana hitam, dan sandal jepit', 'Kawasan industri Bandung Selatan, dekat pabrik tekstil', '2024-11-05', 'Komp. Griya Indah Blok B No. 3, Bandung, Jawa Barat', 'Tuti Alawiyah', 'Istri', '08567890123', 'dicari', 'RAH005');

-- ============================================================
-- DATA DUMMY GAMBAR EDUKASI (dari Wikimedia Commons)
-- ============================================================
INSERT IGNORE INTO gambar_edukasi (bagian, kata_kunci, url_gambar, thumbnail, judul, sumber, link_sumber) VALUES
('definisi', 'gempa bumi ilustrasi', 'https://upload.wikimedia.org/wikipedia/commons/thumb/f/f3/Usgs_earthquake.jpg/640px-Usgs_earthquake.jpg', 'https://upload.wikimedia.org/wikipedia/commons/thumb/f/f3/Usgs_earthquake.jpg/320px-Usgs_earthquake.jpg', 'Ilustrasi Gempa Bumi', 'Wikimedia Commons / USGS', 'https://commons.wikimedia.org/wiki/File:Usgs_earthquake.jpg'),
('penyebab', 'lempeng tektonik subduction zone', 'https://upload.wikimedia.org/wikipedia/commons/thumb/4/4d/Tectonic_plates.png/640px-Tectonic_plates.png', 'https://upload.wikimedia.org/wikipedia/commons/thumb/4/4d/Tectonic_plates.png/320px-Tectonic_plates.png', 'Peta Lempeng Tektonik Dunia', 'Wikimedia Commons', 'https://commons.wikimedia.org/wiki/File:Tectonic_plates.png'),
('penyebab', 'cincin api pasifik ring of fire', 'https://upload.wikimedia.org/wikipedia/commons/thumb/7/7d/Pacific_Ring_of_Fire.png/640px-Pacific_Ring_of_Fire.png', 'https://upload.wikimedia.org/wikipedia/commons/thumb/7/7d/Pacific_Ring_of_Fire.png/320px-Pacific_Ring_of_Fire.png', 'Cincin Api Pasifik (Ring of Fire)', 'Wikimedia Commons', 'https://commons.wikimedia.org/wiki/File:Pacific_Ring_of_Fire.png'),
('dampak', 'kerusakan gempa bumi bangunan', 'https://upload.wikimedia.org/wikipedia/commons/thumb/3/3b/2009_Sumatra_earthquakes_%28building_damage%29.jpg/640px-2009_Sumatra_earthquakes_%28building_damage%29.jpg', 'https://upload.wikimedia.org/wikipedia/commons/thumb/3/3b/2009_Sumatra_earthquakes_%28building_damage%29.jpg/320px-2009_Sumatra_earthquakes_%28building_damage%29.jpg', 'Kerusakan Bangunan Akibat Gempa Sumatera 2009', 'Wikimedia Commons', 'https://commons.wikimedia.org/wiki/File:2009_Sumatra_earthquakes_(building_damage).jpg'),
('penanggulangan', 'drop cover hold on gempa', 'https://upload.wikimedia.org/wikipedia/commons/thumb/8/8b/Drop_cover_hold_on.png/640px-Drop_cover_hold_on.png', 'https://upload.wikimedia.org/wikipedia/commons/thumb/8/8b/Drop_cover_hold_on.png/320px-Drop_cover_hold_on.png', 'Prosedur DROP - COVER - HOLD ON', 'Wikimedia Commons', 'https://commons.wikimedia.org/wiki/File:Drop_cover_hold_on.png'),
('mitigasi', 'bangunan tahan gempa struktur', 'https://upload.wikimedia.org/wikipedia/commons/thumb/6/6d/Earthquake-resistant_construction.jpg/640px-Earthquake-resistant_construction.jpg', 'https://upload.wikimedia.org/wikipedia/commons/thumb/6/6d/Earthquake-resistant_construction.jpg/320px-Earthquake-resistant_construction.jpg', 'Konstruksi Bangunan Tahan Gempa', 'Wikimedia Commons', 'https://commons.wikimedia.org/wiki/File:Earthquake-resistant_construction.jpg');

-- Admin default: admin / admin123
INSERT IGNORE INTO admin (username, password_hash) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');
