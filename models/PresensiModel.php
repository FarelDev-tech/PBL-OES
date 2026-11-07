<?php

class PresensiModel
{
    private PDO $conn;
    private string $table_name = 'presensi';

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    public function getKegiatanUser(int $userId, int $prokerId = 0, string $jenisFilter = '', string $statusFilter = ''): array
    {
        $sql = "SELECT k.*, p.nama_proker, pp.jabatan AS jabatan_di_proker,
                    d_user.nama_divisi AS nama_divisi_user,
                    d_keg.nama_divisi AS nama_divisi_kegiatan,
                    pr.id_presensi, pr.waktu_presensi, pr.foto_selfie,
                    pr.status_validasi, pr.catatan_penolakan
                FROM sesi_presensi k
                JOIN proker p ON k.id_proker = p.id_proker
                JOIN panitia_proker pp ON pp.id_proker = p.id_proker AND pp.id_user = :id_user1
                LEFT JOIN divisi d_user ON pp.id_divisi = d_user.id_divisi
                LEFT JOIN divisi d_keg ON k.id_divisi = d_keg.id_divisi
                LEFT JOIN presensi pr ON pr.id_kegiatan = k.id_kegiatan AND pr.id_user = :id_user2
                WHERE (k.id_divisi IS NULL OR k.id_divisi = pp.id_divisi)";

        $params = [
            'id_user1' => $userId,
            'id_user2' => $userId,
        ];

        if ($prokerId > 0) {
            $sql .= " AND k.id_proker = :id_proker";
            $params['id_proker'] = $prokerId;
        }

        if (in_array($jenisFilter, ['Rapat', 'Hari-H'], true)) {
            $sql .= " AND k.jenis_kegiatan = :jenis";
            $params['jenis'] = $jenisFilter;
        }

        $sql .= " ORDER BY CASE WHEN k.status_sesi = 'Dibuka' THEN 0 ELSE 1 END, k.tanggal DESC NULLS LAST, k.id_kegiatan ASC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        $semuaSesi = $stmt->fetchAll();

        $hasil = [];
        foreach ($semuaSesi as $item) {
            $statusKartu = 'Belum Presensi';
            if (!empty($item['status_validasi'])) {
                $statusKartu = $item['status_validasi'];
            } elseif ($item['status_sesi'] === 'Ditutup') {
                $statusKartu = 'Ditutup';
            }
            $item['status_kartu'] = $statusKartu;

            if ($statusFilter !== '' && $statusFilter !== $statusKartu) {
                continue;
            }
            $hasil[] = $item;
        }

        return $hasil;
    }

    public function simpanSelfie(int $idKegiatan, int $userId, string $namaFileFoto, array $metadata = []): bool
    {
        $metaJson = json_encode(array_merge([
            'ip'         => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Web Browser',
            'timestamp'  => date('Y-m-d H:i:s'),
        ], $metadata));

        $stmtCek = $this->conn->prepare("SELECT id_presensi, status_validasi FROM {$this->table_name} WHERE id_kegiatan = :k AND id_user = :u LIMIT 1");
        $stmtCek->execute(['k' => $idKegiatan, 'u' => $userId]);
        $existing = $stmtCek->fetch();

        if ($existing) {
            if ($existing['status_validasi'] === 'Ditolak') {
                $stmtUpd = $this->conn->prepare(
                    "UPDATE {$this->table_name}
                    SET foto_selfie = :foto,
                        metadata = :meta::jsonb,
                        waktu_presensi = CURRENT_TIMESTAMP,
                        status_validasi = 'Menunggu',
                        catatan_penolakan = NULL
                    WHERE id_presensi = :id"
                );
                return $stmtUpd->execute([
                    'foto' => $namaFileFoto,
                    'meta' => $metaJson,
                    'id'   => (int) $existing['id_presensi'],
                ]);
            }
            return false;
        }

        $sql = "INSERT INTO {$this->table_name} (id_kegiatan, id_user, foto_selfie, metadata, status_validasi)
                VALUES (:k, :u, :foto, :meta::jsonb, 'Menunggu')";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            'k'    => $idKegiatan,
            'u'    => $userId,
            'foto' => $namaFileFoto,
            'meta' => $metaJson,
        ]);
    }

    public function getAntreanKoordinator(int $idProker, int $idDivisi): array
    {
        $sql = "SELECT pr.*, u.nama AS nama_anggota, u.nim,
                    k.nama_kegiatan, k.bobot_poin, k.tanggal, k.jam_mulai, k.jam_selesai,
                    d.nama_divisi
                FROM {$this->table_name} pr
                JOIN users u ON pr.id_user = u.id_user
                JOIN sesi_presensi k ON pr.id_kegiatan = k.id_kegiatan
                JOIN panitia_proker pp ON pp.id_proker = k.id_proker AND pp.id_user = u.id_user
                LEFT JOIN divisi d ON pp.id_divisi = d.id_divisi
                WHERE k.id_proker = :id_proker
                    AND pp.id_divisi = :id_divisi
                    AND pp.jabatan = 'Anggota'
                ORDER BY CASE WHEN pr.status_validasi = 'Menunggu' THEN 0 WHEN pr.status_validasi IN ('Disetujui', 'Sah') THEN 1 ELSE 2 END,
                         pr.waktu_presensi DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute(['id_proker' => $idProker, 'id_divisi' => $idDivisi]);
        return $stmt->fetchAll();
    }

    public function getAntreanAdmin(int $idProker = 0): array
    {
        $sql = "SELECT pr.*, u.nama AS nama_koordinator, u.nim,
                    k.nama_kegiatan, k.bobot_poin, k.tanggal, k.jam_mulai, k.jam_selesai,
                    d.nama_divisi,
                    p.nama_proker
                FROM {$this->table_name} pr
                JOIN users u ON pr.id_user = u.id_user
                JOIN sesi_presensi k ON pr.id_kegiatan = k.id_kegiatan
                JOIN proker p ON k.id_proker = p.id_proker
                JOIN panitia_proker pp ON pp.id_proker = k.id_proker AND pp.id_user = u.id_user
                LEFT JOIN divisi d ON pp.id_divisi = d.id_divisi
                WHERE pp.jabatan = 'Koordinator'";

        $params = [];
        if ($idProker > 0) {
            $sql .= " AND k.id_proker = :id_proker";
            $params['id_proker'] = $idProker;
        }

        $sql .= " ORDER BY CASE WHEN pr.status_validasi = 'Menunggu' THEN 0 WHEN pr.status_validasi IN ('Disetujui', 'Sah') THEN 1 ELSE 2 END,
                        pr.waktu_presensi DESC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getDetailPresensi(int $idPresensi): ?array
    {
        $sql = "SELECT pr.*, u.nama, u.nim, k.nama_kegiatan, k.bobot_poin, k.tanggal, k.jam_mulai, k.jam_selesai,
                    k.id_proker, p.nama_proker, d.nama_divisi
                FROM {$this->table_name} pr
                JOIN users u ON pr.id_user = u.id_user
                JOIN sesi_presensi k ON pr.id_kegiatan = k.id_kegiatan
                JOIN proker p ON k.id_proker = p.id_proker
                LEFT JOIN panitia_proker pp ON pp.id_proker = k.id_proker AND pp.id_user = u.id_user
                LEFT JOIN divisi d ON pp.id_divisi = d.id_divisi
                WHERE pr.id_presensi = :id LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute(['id' => $idPresensi]);
        $res = $stmt->fetch();
        return $res ?: null;
    }

    public function validasiPresensi(int $idPresensi, string $statusValidasi, ?string $catatan, int $idValidator): bool
    {
        try {
            $stmt = $this->conn->prepare("CALL sp_validasi_presensi(:id, :status, :catatan, :validator)");
            return $stmt->execute([
                'id'        => $idPresensi,
                'status'    => $statusValidasi,
                'catatan'   => $catatan,
                'validator' => $idValidator,
            ]);
        } catch (Throwable $e) {
            return false;
        }
    }

    public function validasiPresensiACID(int $idPresensi, string $statusValidasi, ?string $catatan, int $idValidator): bool
    {
        return $this->validasiPresensi($idPresensi, $statusValidasi, $catatan, $idValidator);
    }

    public function getDashboardSummary(): array
    {
        $stmt = $this->conn->query("SELECT * FROM mv_dashboard_summary");
        return $stmt->fetch() ?: [];
    }

    public function refreshDashboardSummary(): bool
    {
        return (bool) $this->conn->exec("REFRESH MATERIALIZED VIEW mv_dashboard_summary");
    }

    public function getLeaderboard(int $limit = 10): array
    {
        $stmt = $this->conn->prepare("SELECT * FROM v_leaderboard ORDER BY total_poin DESC NULLS LAST, nama ASC LIMIT :limit");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll();

        $rank = 1;
        foreach ($rows as &$r) {
            $r['ranking']    = $rank++;
            $r['total_poin'] = (int) ($r['total_poin'] ?? 0);
        }
        unset($r);

        return $rows;
    }

    public function getStatistikUser(int $userId): array
    {
        $stmt = $this->conn->prepare(
            "SELECT 
                COALESCE((SELECT SUM(poin) FROM riwayat_poin WHERE id_user = :u1), 0) AS total_poin,
                COUNT(CASE WHEN status_validasi = 'Disetujui' THEN 1 END) AS total_hadir,
                COUNT(CASE WHEN status_validasi = 'Menunggu' THEN 1 END) AS total_menunggu
            FROM {$this->table_name} WHERE id_user = :u2"
        );
        $stmt->execute(['u1' => $userId, 'u2' => $userId]);
        $row = $stmt->fetch();
        return [
            'total_poin'     => (int) ($row['total_poin'] ?? 0),
            'total_hadir'    => (int) ($row['total_hadir'] ?? 0),
            'total_menunggu' => (int) ($row['total_menunggu'] ?? 0),
        ];
    }

    public function getAntreanCount(string $role, int $userId): int
    {
        if ($role === 'koordinator') {
            $stmt = $this->conn->prepare(
                "SELECT COUNT(*) FROM {$this->table_name} pr
                JOIN sesi_presensi k ON pr.id_kegiatan = k.id_kegiatan
                JOIN panitia_proker pp_koor ON pp_koor.id_proker = k.id_proker AND pp_koor.id_user = :u AND pp_koor.jabatan = 'Koordinator'
                JOIN panitia_proker pp_ang ON pp_ang.id_proker = k.id_proker AND pp_ang.id_user = pr.id_user AND pp_ang.id_divisi = pp_koor.id_divisi
                WHERE pr.status_validasi = 'Menunggu' AND pp_ang.jabatan = 'Anggota'"
            );
            $stmt->execute(['u' => $userId]);
            return (int) $stmt->fetchColumn();
        } elseif (in_array($role, ['admin', 'super_admin'], true)) {
            $stmt = $this->conn->prepare(
                "SELECT COUNT(*) FROM {$this->table_name} pr
                JOIN sesi_presensi k ON pr.id_kegiatan = k.id_kegiatan
                JOIN panitia_proker pp ON pp.id_proker = k.id_proker AND pp.id_user = pr.id_user
                WHERE pr.status_validasi = 'Menunggu' AND pp.jabatan = 'Koordinator'"
            );
            $stmt->execute();
            return (int) $stmt->fetchColumn();
        }
        return 0;
    }
}

