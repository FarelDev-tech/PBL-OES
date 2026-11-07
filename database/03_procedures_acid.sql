-- Stored Procedure Transaksi Validasi Presensi (ACID)
DROP PROCEDURE IF EXISTS sp_validasi_presensi(INT, VARCHAR, TEXT, INT);
DROP PROCEDURE IF EXISTS sp_validasi_presensi(INT, VARCHAR, TEXT, INT, BOOLEAN);

CREATE OR REPLACE PROCEDURE sp_validasi_presensi(
    p_id_presensi INT,
    p_status VARCHAR,
    p_catatan TEXT,
    p_validator INT
)
LANGUAGE plpgsql
AS $$
DECLARE
    v_id_kegiatan INT;
    v_id_user INT;
    v_id_proker INT;
    v_nama_kegiatan VARCHAR;
    v_bobot_poin INT;
BEGIN
    -- Kunci baris presensi (concurrency lock)
    SELECT id_kegiatan, id_user INTO v_id_kegiatan, v_id_user
    FROM presensi WHERE id_presensi = p_id_presensi FOR UPDATE;

    IF NOT FOUND THEN
        RAISE EXCEPTION 'Data presensi tidak ditemukan';
    END IF;

    -- Update status validasi
    UPDATE presensi
    SET status_validasi = p_status,
        catatan_penolakan = p_catatan,
        divalidasi_oleh = p_validator,
        waktu_validasi = CURRENT_TIMESTAMP
    WHERE id_presensi = p_id_presensi;

    -- Kelola poin keaktifan
    IF p_status = 'Disetujui' THEN
        SELECT s.id_proker, s.nama_kegiatan, s.bobot_poin
        INTO v_id_proker, v_nama_kegiatan, v_bobot_poin
        FROM sesi_presensi s WHERE s.id_kegiatan = v_id_kegiatan;

        DELETE FROM riwayat_poin WHERE id_presensi = p_id_presensi;

        INSERT INTO riwayat_poin (id_user, id_proker, id_presensi, sumber, kegiatan_atau_task, keterangan, poin, tanggal)
        VALUES (v_id_user, v_id_proker, p_id_presensi, 'Presensi', v_nama_kegiatan, 'Kehadiran pada ' || v_nama_kegiatan, v_bobot_poin, CURRENT_DATE);
    ELSE
        DELETE FROM riwayat_poin WHERE id_presensi = p_id_presensi;
    END IF;

    -- Refresh Materialized View
    REFRESH MATERIALIZED VIEW mv_dashboard_summary;
END;
$$;
