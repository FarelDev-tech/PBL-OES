<?php

class JobdeskModel
{
    public const STATUS = ['To Do', 'In Progress', 'Done', 'Closed'];

    private PDO $conn;

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    // Daftar jobdesk proker + penerima (JSON). $idUser > 0 => hanya tugas milik user tsb.
    public function getByProker(int $idProker, int $idUser = 0): array
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
        $sql .= " GROUP BY j.id_jobdesk, d.nama_divisi
                  ORDER BY (j.status_kanban = 'Closed'), j.deadline ASC NULLS LAST, j.id_jobdesk DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);

        $rows = $stmt->fetchAll();
        foreach ($rows as &$r) {
            $r['penerima'] = json_decode($r['penerima'], true) ?: [];
        }
        return $rows;
    }

    public function getById(int $idJobdesk): ?array
    {
        $stmt = $this->conn->prepare("SELECT * FROM jobdesk WHERE id_jobdesk = :id LIMIT 1");
        $stmt->execute(['id' => $idJobdesk]);
        return $stmt->fetch() ?: null;
    }

    // Hanya user yang memang panitia proker ini (dan, bila $idDivisi diberikan, divisinya cocok) yang lolos.
    public function filterPenerima(int $idProker, array $userIds, ?int $idDivisi = null): array
    {
        $userIds = array_values(array_unique(array_filter(array_map('intval', $userIds))));
        if (!$userIds) {
            return [];
        }
        $in  = implode(',', array_fill(0, count($userIds), '?'));
        $sql = "SELECT id_user FROM panitia_proker WHERE id_proker = ? AND id_user IN ($in)";
        $params = array_merge([$idProker], $userIds);
        if ($idDivisi) {
            $sql .= " AND id_divisi = ?";
            $params[] = $idDivisi;
        }
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    public function create(array $data, array $userIds): int
    {
        $this->conn->beginTransaction();
        try {
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
            $id = (int) $stmt->fetchColumn();
            $this->setPenerima($id, $userIds);
            $this->conn->commit();
            return $id;
        } catch (Throwable $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            throw $e;
        }
    }

    public function update(int $idJobdesk, array $data, array $userIds): void
    {
        $this->conn->beginTransaction();
        try {
            $stmt = $this->conn->prepare(
                "UPDATE jobdesk
                 SET id_divisi = :div, judul = :judul, deskripsi = :desk, deadline = :dl, bobot_poin = :poin
                 WHERE id_jobdesk = :id"
            );
            $stmt->execute([
                'div'   => $data['id_divisi'],
                'judul' => $data['judul'],
                'desk'  => $data['deskripsi'],
                'dl'    => $data['deadline'],
                'poin'  => $data['bobot_poin'],
                'id'    => $idJobdesk,
            ]);
            $this->setPenerima($idJobdesk, $userIds);
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

    // Ganti seluruh penerima tugas (dipanggil di dalam transaksi create/update)
    private function setPenerima(int $idJobdesk, array $userIds): void
    {
        $this->conn->prepare("DELETE FROM jobdesk_anggota WHERE id_jobdesk = :id")->execute(['id' => $idJobdesk]);
        $ins = $this->conn->prepare("INSERT INTO jobdesk_anggota (id_jobdesk, id_user) VALUES (:j, :u)");
        foreach ($userIds as $uid) {
            $ins->execute(['j' => $idJobdesk, 'u' => (int) $uid]);
        }
    }
}
