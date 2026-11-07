-- Standard Views & Materialized Views OES
DROP MATERIALIZED VIEW IF EXISTS mv_dashboard_summary CASCADE;
DROP VIEW IF EXISTS v_kegiatan_lengkap CASCADE;
DROP VIEW IF EXISTS v_leaderboard CASCADE;

-- View Leaderboard Poin Keaktifan
CREATE VIEW v_leaderboard AS
SELECT
    u.id_user,
    u.nim,
    u.nama,
    u.role,
    u.status_akun,
    SUM(rp.poin) AS total_poin,
    COUNT(CASE WHEN rp.sumber = 'Presensi' THEN 1 END) AS kali_presensi
FROM users u
LEFT JOIN riwayat_poin rp ON u.id_user = rp.id_user
WHERE u.role IN ('anggota', 'koordinator') AND u.status_akun = 'Aktif'
GROUP BY u.id_user, u.nim, u.nama, u.role, u.status_akun;

-- View Sesi Kegiatan Lengkap
CREATE VIEW v_kegiatan_lengkap AS
SELECT
    s.id_kegiatan,
    s.id_proker,
    p.nama_proker,
    s.id_divisi,
    d.nama_divisi,
    s.nama_kegiatan,
    s.jenis_kegiatan,
    s.bobot_poin,
    s.tanggal,
    s.jam_mulai,
    s.jam_selesai,
    s.pelaksanaan,
    s.tempat_atau_link,
    s.status_detail,
    s.status_sesi,
    s.created_at
FROM sesi_presensi s
JOIN proker p ON s.id_proker = p.id_proker
LEFT JOIN divisi d ON s.id_divisi = d.id_divisi;

-- Materialized View Ringkasan Dashboard
CREATE MATERIALIZED VIEW mv_dashboard_summary AS
SELECT
    (SELECT COUNT(*) FROM proker WHERE status_proker = 'Berjalan') AS total_proker_aktif,
    (SELECT COUNT(*) FROM users WHERE role = 'anggota' AND status_akun = 'Aktif') AS total_anggota_aktif,
    (SELECT COUNT(*) FROM presensi WHERE status_validasi = 'Disetujui') AS total_presensi_disetujui,
    (SELECT COALESCE(SUM(poin), 0) FROM riwayat_poin) AS total_poin_terdistribusi;

CREATE UNIQUE INDEX idx_mv_dashboard_summary ON mv_dashboard_summary(total_proker_aktif, total_anggota_aktif, total_presensi_disetujui, total_poin_terdistribusi);
