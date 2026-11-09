-- ============================================
-- SIMB - Sistem Informasi Manajemen Bencana
-- Skema database (MySQL / MariaDB)
-- ============================================

CREATE DATABASE IF NOT EXISTS db_simb
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
USE db_simb;

-- --------------------------------------------
-- 1. ADMIN (hanya admin yang login)
-- --------------------------------------------
CREATE TABLE admin (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  username      VARCHAR(50)  NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,          -- gunakan password_hash() di PHP
  nama_lengkap  VARCHAR(100) NOT NULL,
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- --------------------------------------------
-- 2. GEMPA (riwayat dari API BMKG)
--    jenis: 'terkini' (M5+), 'dirasakan', 'terbaru' (autogempa)
-- --------------------------------------------
CREATE TABLE gempa (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  kode_unik   VARCHAR(64) NOT NULL UNIQUE,      -- hash(waktu+lat+lon) agar tidak duplikat
  waktu       DATETIME      NOT NULL,           -- waktu kejadian (WIB)
  lintang     DECIMAL(8,4)  NOT NULL,
  bujur       DECIMAL(8,4)  NOT NULL,
  magnitudo   DECIMAL(3,1)  NOT NULL,
  kedalaman   SMALLINT      NOT NULL,           -- dalam km
  wilayah     VARCHAR(255)  NOT NULL,
  potensi     VARCHAR(255)  NULL,               -- ex: "Tidak berpotensi tsunami"
  dirasakan   VARCHAR(500)  NULL,               -- ex: "III Bantul, II Yogyakarta"
  jenis       ENUM('terkini','dirasakan','terbaru') NOT NULL DEFAULT 'terkini',
  shakemap    VARCHAR(100)  NULL,               -- nama file shakemap BMKG
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_waktu (waktu),
  INDEX idx_magnitudo (magnitudo),
  INDEX idx_jenis (jenis)
) ENGINE=InnoDB;

-- --------------------------------------------
-- 3. ORANG HILANG
--    status_laporan : alur verifikasi admin
--    status_orang   : kondisi orang yang dicari
-- --------------------------------------------
CREATE TABLE orang_hilang (
  id               INT AUTO_INCREMENT PRIMARY KEY,
  kode_laporan     VARCHAR(12)  NOT NULL UNIQUE,   -- ex: OH-7K2M9Q (dibagikan ke publik)
  token_kelola     CHAR(32)     NOT NULL,          -- rahasia pelapor, untuk ubah status tanpa login

  -- data orang yang dicari
  nama_lengkap     VARCHAR(100) NOT NULL,
  usia             TINYINT UNSIGNED NULL,
  jenis_kelamin    ENUM('L','P') NOT NULL,
  ciri_ciri        TEXT         NULL,              -- pakaian, tinggi, tanda khusus
  foto             VARCHAR(255) NULL,              -- nama file di /uploads

  -- lokasi
  lokasi_terakhir  VARCHAR(255) NOT NULL,
  lintang_terakhir DECIMAL(8,5) NULL,              -- opsional, untuk ditampilkan di peta
  bujur_terakhir   DECIMAL(8,5) NULL,
  waktu_terakhir   DATETIME     NULL,              -- kapan terakhir terlihat
  domisili         VARCHAR(255) NOT NULL,

  -- pelapor
  nama_pelapor     VARCHAR(100) NOT NULL,
  hubungan         VARCHAR(50)  NULL,              -- ayah, anak, teman, dll
  kontak_pelapor   VARCHAR(30)  NOT NULL,          -- no. WA/telepon

  -- status
  status_laporan   ENUM('menunggu','terverifikasi','ditolak') NOT NULL DEFAULT 'menunggu',
  alasan_penolakan VARCHAR(255) NULL,
  status_orang     ENUM('hilang','ditemukan','dalam_perawatan') NOT NULL DEFAULT 'hilang',
  diverifikasi_oleh INT NULL,
  diverifikasi_pada DATETIME NULL,

  -- anti spam
  ip_pelapor       VARCHAR(45)  NOT NULL,
  created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  FOREIGN KEY (diverifikasi_oleh) REFERENCES admin(id) ON DELETE SET NULL,
  INDEX idx_status_laporan (status_laporan),
  INDEX idx_status_orang (status_orang),
  INDEX idx_nama (nama_lengkap),
  INDEX idx_ip_waktu (ip_pelapor, created_at)
) ENGINE=InnoDB;

-- --------------------------------------------
-- 4. KONTAK DARURAT (bisa dikelola admin)
-- --------------------------------------------
CREATE TABLE kontak_darurat (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  instansi   VARCHAR(100) NOT NULL,
  nomor      VARCHAR(20)  NOT NULL,
  keterangan VARCHAR(150) NULL,
  urutan     TINYINT NOT NULL DEFAULT 0,
  aktif      TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

-- --------------------------------------------
-- 5. LOG REFRESH API (mencegah panggilan API berlebihan)
-- --------------------------------------------
CREATE TABLE log_sinkron_api (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  endpoint    VARCHAR(100) NOT NULL,
  status      ENUM('sukses','gagal') NOT NULL,
  jumlah_baru INT NOT NULL DEFAULT 0,
  pesan       VARCHAR(255) NULL,
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_endpoint_waktu (endpoint, created_at)
) ENGINE=InnoDB;

-- --------------------------------------------
-- 6. LOGIN GAGAL (pembatas percobaan login admin)
-- --------------------------------------------
CREATE TABLE login_gagal (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  ip         VARCHAR(45) NOT NULL,
  username   VARCHAR(50) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_ip_waktu (ip, created_at)
) ENGINE=InnoDB;

-- ============================================
-- DATA AWAL
-- ============================================

INSERT INTO kontak_darurat (instansi, nomor, keterangan, urutan) VALUES
('BNPB',                '117', 'Badan Nasional Penanggulangan Bencana', 1),
('Basarnas (SAR)',      '115', 'Pencarian dan pertolongan',             2),
('Ambulans / Gawat Darurat', '119', 'Layanan medis darurat',            3),
('Polisi',              '110', 'Kepolisian RI',                         4),
('Pemadam Kebakaran',   '113', 'Kebakaran dan penyelamatan',            5),
('PLN',                 '123', 'Gangguan listrik',                      6);

-- Admin awal berisi PLACEHOLDER (tidak bisa dipakai login).
-- Buka http://localhost/SIMB_GempaBumi/admin/setup.php untuk membuat akun admin Anda.
INSERT INTO admin (username, password_hash, nama_lengkap) VALUES
('admin', '$2y$10$REPLACE_THIS_WITH_YOUR_OWN_HASH', 'Administrator');
