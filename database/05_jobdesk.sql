CREATE TABLE IF NOT EXISTS jobdesk (
    id_jobdesk SERIAL,
    id_proker INT NOT NULL,
    id_divisi INT,
    judul VARCHAR(150) NOT NULL,
    deskripsi TEXT,
    deadline DATE,
    bobot_poin INT NOT NULL DEFAULT 10,
    status_kanban VARCHAR(20) NOT NULL DEFAULT 'To Do',
    dibuat_oleh INT,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT pk_jobdesk PRIMARY KEY (id_jobdesk),
    CONSTRAINT fk_jobdesk_proker FOREIGN KEY (id_proker) REFERENCES proker(id_proker) ON DELETE CASCADE,
    CONSTRAINT fk_jobdesk_divisi FOREIGN KEY (id_divisi) REFERENCES divisi(id_divisi) ON DELETE SET NULL,
    CONSTRAINT fk_jobdesk_pembuat FOREIGN KEY (dibuat_oleh) REFERENCES users(id_user) ON DELETE SET NULL,
    CONSTRAINT chk_jobdesk_status CHECK (status_kanban IN ('To Do', 'In Progress', 'Done', 'Closed')),
    CONSTRAINT chk_jobdesk_bobot CHECK (bobot_poin >= 0)
);

-- Penerima tugas (relasi many-to-many jobdesk <-> users)
CREATE TABLE IF NOT EXISTS jobdesk_anggota (
    id_jobdesk INT NOT NULL,
    id_user INT NOT NULL,

    CONSTRAINT pk_jobdesk_anggota PRIMARY KEY (id_jobdesk, id_user),
    CONSTRAINT fk_ja_jobdesk FOREIGN KEY (id_jobdesk) REFERENCES jobdesk(id_jobdesk) ON DELETE CASCADE,
    CONSTRAINT fk_ja_user FOREIGN KEY (id_user) REFERENCES users(id_user) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_jobdesk_proker ON jobdesk(id_proker, status_kanban);
CREATE INDEX IF NOT EXISTS idx_ja_user ON jobdesk_anggota(id_user);

-- Poin dari jobdesk: riwayat_poin menerima sumber 'Jobdesk' + kolom id_jobdesk
ALTER TABLE riwayat_poin ADD COLUMN IF NOT EXISTS id_jobdesk INT;
ALTER TABLE riwayat_poin DROP CONSTRAINT IF EXISTS fk_riwayat_jobdesk;
ALTER TABLE riwayat_poin ADD CONSTRAINT fk_riwayat_jobdesk
    FOREIGN KEY (id_jobdesk) REFERENCES jobdesk(id_jobdesk) ON DELETE CASCADE;
ALTER TABLE riwayat_poin DROP CONSTRAINT IF EXISTS chk_riwayat_sumber;
ALTER TABLE riwayat_poin ADD CONSTRAINT chk_riwayat_sumber
    CHECK (sumber IN ('Presensi', 'Koreksi Admin', 'Penghargaan', 'Jobdesk'));