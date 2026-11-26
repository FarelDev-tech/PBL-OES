<?php

class JobdeskModel
{
    public const STATUS = ['To Do', 'In Progress', 'Done', 'Closed'];

    private PDO $conn;

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    // Daftar jobdesk proker + penerima (JSON).
    // $idUser > 0 => hanya tugas milik user; $idDivisi > 0 => hanya satu divisi;
    // $urut 'terbaru' => terakhir diedit di atas (tampilan Umum), selain itu urut tenggat.
    public function getByProker(int $idProker, int $idUser = 0, int $idDivisi = 0, string $urut = 'terbaru'): array
    {
        $sql = "SELECT j.*, d.nama_divisi,
                    COALESCE(json_agg(json_build_object('id_user', u.id_user, 'nama', u.nama) ORDER BY u.nama)
                             FILTER (WHERE u.id_user IS NOT NULL), '[]') AS penerima
                FROM jobdesk j
                LEFT JOIN divisi d ON j.id_divisi = d.id_divisi
                LEFT JOIN jobdesk_anggota ja ON ja.id_jobdesk = j.id_jobdesk
                LEFT JOIN users u ON u.id_user = ja.id_user
                WHERE j.id_proker = :p";
        $params = ['p' => $idProker];
        if ($idUser > 0) {
            $sql .= " AND EXISTS (SELECT 1 FROM jobdesk_anggota x WHERE x.id_jobdesk = j.id_jobdesk AND x.id_user = :u)";
            $params['u'] = $idUser;
        }
        if ($idDivisi > 0) {
            $sql .= " AND j.id_divisi = :d";
            $params['d'] = $idDivisi;
        }
        $sql .= " GROUP BY j.id_jobdesk, d.nama_divisi";
        $sql .= ($urut === 'terbaru')
            ? " ORDER BY j.updated_at DESC, j.id_jobdesk DESC"
            : " ORDER BY (j.status_kanban = 'Closed'), j.deadline ASC NULLS LAST, j.id_jobdesk ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);

        $rows = $stmt->fetchAll();
        foreach ($rows as &$r) {
            $r['penerima'] = json_decode($r['penerima'], true) ?: [];
        }
        return $rows;
    }

    // Divisi yang sudah punya jobdesk (untuk chip filter) beserta jumlahnya
    public function getDivisiBerjobdesk(int $idProker): array
    {
        $stmt = $this->conn->prepare(
            "SELECT d.id_divisi, d.nama_divisi, COUNT(j.id_jobdesk) AS total
             FROM jobdesk j JOIN divisi d ON j.id_divisi = d.id_divisi
             WHERE j.id_proker = :p
             GROUP BY d.id_divisi, d.nama_divisi
             ORDER BY d.id_divisi ASC"
        );
        $stmt->execute(['p' => $idProker]);
        return $stmt->fetchAll();
    }

    public function countByProker(int $idProker): int
    {
        $stmt = $this->conn->prepare("SELECT COUNT(*) FROM jobdesk WHERE id_proker = :p");
        $stmt->execute(['p' => $idProker]);
        return (int) $stmt->fetchColumn();
    }

    // Calon penerima: koordinator + anggota divisi, bukan akun Admin/Super Admin
    public function getCalonPenerima(int $idProker, int $idDivisi): array
    {
        $stmt = $this->conn->prepare(
            "SELECT u.id_user, u.nama, pp.jabatan
             FROM panitia_proker pp JOIN users u ON u.id_user = pp.id_user
             WHERE pp.id_proker = :p AND pp.id_divisi = :d
               AND u.role NOT IN ('admin', 'super_admin')
             ORDER BY (pp.jabatan = 'Koordinator') DESC, u.nama ASC"
        );
        $stmt->execute(['p' => $idProker, 'd' => $idDivisi]);
        return $stmt->fetchAll();
    }

    public function getById(int $idJobdesk): ?array
    {
        $stmt = $this->conn->prepare("SELECT * FROM jobdesk WHERE id_jobdesk = :id LIMIT 1");
        $stmt->execute(['id' => $idJobdesk]);
        return $stmt->fetch() ?: null;
    }

    // Hanya panitia divisi tsb yang bukan Admin/Super Admin yang lolos.
    public function filterPenerima(int $idProker, int $idDivisi, array $userIds): array
    {
        $userIds = array_values(array_unique(array_filter(array_map('intval', $userIds))));
        if (!$userIds || $idDivisi <= 0) {
            return [];
        }
        $in  = implode(',', array_fill(0, count($userIds), '?'));
        $stmt = $this->conn->prepare(
            "SELECT pp.id_user FROM panitia_proker pp JOIN users u ON u.id_user = pp.id_user
             WHERE pp.id_proker = ? AND pp.id_divisi = ? AND u.role NOT IN ('admin', 'super_admin')
               AND pp.id_user IN ($in)"
        );
        $stmt->execute(array_merge([$idProker, $idDivisi], $userIds));
        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    // Dibuat Admin: tanpa penerima (penerima diatur Koordinator)
    public function create(array $data): int
    {
        $stmt = $this->conn->prepare(
            "INSERT INTO jobdesk (id_proker, id_divisi, judul, deskripsi, deadline, bobot_poin, dibuat_oleh)
             VALUES (:p, :div, :judul, :desk, :dl, :poin, :by)
             RETURNING id_jobdesk"
        );
        $stmt->execute([
            'p'     => $data['id_proker'],
            'div'   => $data['id_divisi'],
            'judul' => $data['judul'],
            'desk'  => $data['deskripsi'],
            'dl'    => $data['deadline'],
            'poin'  => $data['bobot_poin'],
            'by'    => $data['dibuat_oleh'],
        ]);
        return (int) $stmt->fetchColumn();
    }

    // Edit jobdesk. $userIds null (Admin) => penerima tetap, hanya yang di luar divisi baru dilepas;
    // array (Koordinator) => penerima diganti sesuai pilihan.
    public function update(int $idJobdesk, array $data, ?array $userIds = null): void
    {
        $this->conn->beginTransaction();
        try {
            $this->conn->prepare(
                "UPDATE jobdesk
                 SET id_divisi = :div, judul = :judul, deskripsi = :desk, deadline = :dl,
                     bobot_poin = :poin, updated_at = CURRENT_TIMESTAMP
                 WHERE id_jobdesk = :id"
            )->execute([
                'div'   => $data['id_divisi'],
                'judul' => $data['judul'],
                'desk'  => $data['deskripsi'],
                'dl'    => $data['deadline'],
                'poin'  => $data['bobot_poin'],
                'id'    => $idJobdesk,
            ]);
            $this->conn->prepare(
                "DELETE FROM jobdesk_anggota ja WHERE ja.id_jobdesk = :id
                 AND NOT EXISTS (SELECT 1 FROM panitia_proker pp
                                 WHERE pp.id_user = ja.id_user AND pp.id_divisi = :div)"
            )->execute(['id' => $idJobdesk, 'div' => $data['id_divisi']]);
            if ($userIds !== null) {
                $this->conn->prepare("DELETE FROM jobdesk_anggota WHERE id_jobdesk = :id")->execute(['id' => $idJobdesk]);
                $ins = $this->conn->prepare("INSERT INTO jobdesk_anggota (id_jobdesk, id_user) VALUES (:j, :u)");
                foreach ($userIds as $uid) {
                    $ins->execute(['j' => $idJobdesk, 'u' => (int) $uid]);
                }
            }
            $this->conn->commit();
        } catch (Throwable $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            throw $e;
        }
    }

    public function delete(int $idJobdesk): bool
    {
        $stmt = $this->conn->prepare("DELETE FROM jobdesk WHERE id_jobdesk = :id");
        return $stmt->execute(['id' => $idJobdesk]);
    }
}