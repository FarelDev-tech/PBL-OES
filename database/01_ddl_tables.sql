-- DDL Tabel & Indeks OES
DROP TABLE IF EXISTS riwayat_poin CASCADE;
DROP TABLE IF EXISTS presensi CASCADE;
DROP TABLE IF EXISTS sesi_presensi CASCADE;
DROP TABLE IF EXISTS panitia_proker CASCADE;
DROP TABLE IF EXISTS divisi CASCADE;
DROP TABLE IF EXISTS proker CASCADE;
DROP TABLE IF EXISTS users CASCADE;

-- Tabel Pengguna & Kredensial
CREATE TABLE users (
    id_user SERIAL,
    nim VARCHAR(20) NOT NULL,
    nama VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    no_hp VARCHAR(20),
    password VARCHAR(255) NOT NULL,
    role VARCHAR(20) NOT NULL DEFAULT 'anggota',
    status_akun VARCHAR(20) NOT NULL DEFAULT 'Aktif',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT pk_users PRIMARY KEY (id_user),
    CONSTRAINT uq_users_nim UNIQUE (nim),
    CONSTRAINT uq_users_email UNIQUE (email),
    CONSTRAINT chk_users_role CHECK (role IN ('super_admin', 'admin', 'koordinator', 'anggota')),
    CONSTRAINT chk_users_status CHECK (status_akun IN ('Aktif', 'Nonaktif'))
);

-- Tabel Program Kerja
CREATE TABLE proker (
    id_proker SERIAL,
    nama_proker VARCHAR(150) NOT NULL,
    periode VARCHAR(20) NOT NULL DEFAULT '2026/2027',
    deskripsi TEXT,
    tanggal_mulai DATE NOT NULL,
    tanggal_selesai DATE NOT NULL,
    status_proker VARCHAR(20) NOT NULL DEFAULT 'Berjalan',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT pk_proker PRIMARY KEY (id_proker),
    CONSTRAINT chk_proker_status CHECK (status_proker IN ('Perencanaan', 'Berjalan', 'Selesai'))
);

-- Tabel Divisi
CREATE TABLE divisi (
    id_divisi SERIAL,
    id_proker INT NOT NULL,
    nama_divisi VARCHAR(100) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT pk_divisi PRIMARY KEY (id_divisi),
    CONSTRAINT fk_divisi_proker FOREIGN KEY (id_proker) REFERENCES proker(id_proker) ON DELETE CASCADE,
    CONSTRAINT uq_divisi_proker_nama UNIQUE (id_proker, nama_divisi)
);

-- Tabel Kepanitiaan Proker
CREATE TABLE panitia_proker (
    id_panitia SERIAL,
    id_proker INT NOT NULL,
    id_user INT NOT NULL,
    id_divisi INT,
    jabatan VARCHAR(20) NOT NULL DEFAULT 'Anggota',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT pk_panitia_proker PRIMARY KEY (id_panitia),
    CONSTRAINT fk_panitia_proker FOREIGN KEY (id_proker) REFERENCES proker(id_proker) ON DELETE CASCADE,
    CONSTRAINT fk_panitia_user FOREIGN KEY (id_user) REFERENCES users(id_user) ON DELETE CASCADE,
    CONSTRAINT fk_panitia_divisi FOREIGN KEY (id_divisi) REFERENCES divisi(id_divisi) ON DELETE SET NULL,
    CONSTRAINT chk_panitia_jabatan CHECK (jabatan IN ('Koordinator', 'Anggota')),
    CONSTRAINT uq_panitia_proker_user UNIQUE (id_proker, id_user)
);

-- Tabel Sesi Presensi
CREATE TABLE sesi_presensi (
    id_kegiatan SERIAL,
    id_proker INT NOT NULL,
    id_divisi INT,
    nama_kegiatan VARCHAR(150) NOT NULL,
    jenis_kegiatan VARCHAR(50) NOT NULL,
    bobot_poin INT NOT NULL DEFAULT 10,
    tanggal DATE,
    jam_mulai TIME,
    jam_selesai TIME,
    pelaksanaan VARCHAR(20),
    tempat_atau_link VARCHAR(255),
    status_detail VARCHAR(20) NOT NULL DEFAULT 'Belum Dilengkapi',
    status_sesi VARCHAR(20) NOT NULL DEFAULT 'Dibuka',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT pk_sesi_presensi PRIMARY KEY (id_kegiatan),
    CONSTRAINT fk_sesi_proker FOREIGN KEY (id_proker) REFERENCES proker(id_proker) ON DELETE CASCADE,
    CONSTRAINT fk_sesi_divisi FOREIGN KEY (id_divisi) REFERENCES divisi(id_divisi) ON DELETE SET NULL,
    CONSTRAINT chk_sesi_jenis CHECK (jenis_kegiatan IN ('Rapat', 'Hari-H', 'Lainnya')),
    CONSTRAINT chk_sesi_pelaksanaan CHECK (pelaksanaan IN ('Offline', 'Online')),
    CONSTRAINT chk_sesi_status_detail CHECK (status_detail IN ('Belum Dilengkapi', 'Lengkap')),
    CONSTRAINT chk_sesi_status_sesi CHECK (status_sesi IN ('Dibuka', 'Ditutup'))
);

-- Tabel Presensi
CREATE TABLE presensi (
    id_presensi SERIAL,
    id_kegiatan INT NOT NULL,
    id_user INT NOT NULL,
    foto_selfie VARCHAR(255) NOT NULL,
    waktu_presensi TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    status_validasi VARCHAR(20) NOT NULL DEFAULT 'Menunggu',
    catatan_penolakan TEXT,
    divalidasi_oleh INT,
    waktu_validasi TIMESTAMP,
    metadata JSONB DEFAULT '{}'::jsonb,

    CONSTRAINT pk_presensi PRIMARY KEY (id_presensi),
    CONSTRAINT fk_presensi_sesi FOREIGN KEY (id_kegiatan) REFERENCES sesi_presensi(id_kegiatan) ON DELETE CASCADE,
    CONSTRAINT fk_presensi_user FOREIGN KEY (id_user) REFERENCES users(id_user) ON DELETE CASCADE,
    CONSTRAINT fk_presensi_validator FOREIGN KEY (divalidasi_oleh) REFERENCES users(id_user) ON DELETE SET NULL,
    CONSTRAINT chk_presensi_status CHECK (status_validasi IN ('Menunggu', 'Disetujui', 'Ditolak')),
    CONSTRAINT uq_presensi_kegiatan_user UNIQUE (id_kegiatan, id_user)
);

-- Tabel Riwayat Poin
CREATE TABLE riwayat_poin (
    id_riwayat SERIAL,
    id_user INT NOT NULL,
    id_proker INT,
    id_presensi INT,
    sumber VARCHAR(30) NOT NULL,
    kegiatan_atau_task VARCHAR(150) NOT NULL,
    keterangan TEXT,
    poin INT NOT NULL,
    tanggal DATE NOT NULL DEFAULT CURRENT_DATE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT pk_riwayat_poin PRIMARY KEY (id_riwayat),
    CONSTRAINT fk_riwayat_user FOREIGN KEY (id_user) REFERENCES users(id_user) ON DELETE CASCADE,
    CONSTRAINT fk_riwayat_proker FOREIGN KEY (id_proker) REFERENCES proker(id_proker) ON DELETE SET NULL,
    CONSTRAINT fk_riwayat_presensi FOREIGN KEY (id_presensi) REFERENCES presensi(id_presensi) ON DELETE CASCADE,
    CONSTRAINT chk_riwayat_sumber CHECK (sumber IN ('Presensi', 'Koreksi Admin', 'Penghargaan'))
);

-- Indeks 
CREATE INDEX idx_users_role_status ON users(role, status_akun);
CREATE INDEX idx_sesi_presensi_proker ON sesi_presensi(id_proker);
CREATE INDEX idx_presensi_kegiatan ON presensi(id_kegiatan, status_validasi);
CREATE INDEX idx_riwayat_poin_user ON riwayat_poin(id_user, id_proker);
CREATE INDEX idx_users_fts ON users USING gin(to_tsvector('indonesian', nama || ' ' || nim));
