<?php

class AnggotaModel {
    private PDO $conn;
    private string $table_name = 'users';

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    public function getAll(string $keyword = '', string $roleFilter = '', string $statusFilter = ''): array
    {
        $sql = "SELECT u.*,
                    COALESCE((SELECT SUM(rp.poin) FROM riwayat_poin rp WHERE rp.id_user = u.id_user), 0) AS total_poin
                FROM {$this->table_name} u
                WHERE 1=1";

        $params = [];

        if (!empty($keyword)) {
            $sql .= " AND (to_tsvector('indonesian', u.nama || ' ' || u.nim) @@ plainto_tsquery('indonesian', :kw_fts) OR u.nama ILIKE :kw_like OR u.nim ILIKE :kw_like OR u.email ILIKE :kw_like)";
            $params['kw_fts']  = $keyword;
            $params['kw_like'] = '%' . $keyword . '%';
        }

        if (in_array($roleFilter, ['anggota', 'admin', 'super_admin'], true)) {
            $sql .= " AND u.role = :role";
            $params['role'] = $roleFilter;
        }

        if (in_array($statusFilter, ['Aktif', 'Nonaktif'], true)) {
            $sql .= " AND u.status_akun = :status";
            $params['status'] = $statusFilter;
        }

        $sql .= " ORDER BY CASE WHEN u.status_akun = 'Aktif' THEN 0 ELSE 1 END,
                        CASE WHEN u.role = 'super_admin' THEN 0 WHEN u.role = 'admin' THEN 1 ELSE 2 END,
                        u.nama ASC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getById(int $userId): ?array
    {
        $stmt = $this->conn->prepare("SELECT * FROM {$this->table_name} WHERE id_user = :id LIMIT 1");
        $stmt->execute(['id' => $userId]);
        $res = $stmt->fetch();
        return $res ?: null;
    }

    public function checkDuplicate(string $nim, string $email): bool
    {
        $stmt = $this->conn->prepare("SELECT id_user FROM {$this->table_name} WHERE nim = :nim OR email = :email LIMIT 1");
        $stmt->execute(['nim' => $nim, 'email' => $email]);
        return (bool) $stmt->fetch();
    }

    public function create(array $data): bool
    {
        $sql = "INSERT INTO {$this->table_name} (nim, nama, email, password, role, status_akun)
                VALUES (:nim, :nama, :email, :password, :role, 'Aktif')";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            'nim'      => $data['nim'],
            'nama'     => $data['nama'],
            'email'    => $data['email'],
            'password' => password_hash($data['password'], PASSWORD_BCRYPT),
            'role'     => in_array($data['role'], ['admin', 'super_admin'], true) ? 'admin' : 'anggota',
        ]);
    }

    public function updateRole(int $userId, string $roleBaru): bool
    {
        $roleValid = ($roleBaru === 'admin') ? 'admin' : 'anggota';
        $stmt = $this->conn->prepare("UPDATE {$this->table_name} SET role = :r WHERE id_user = :id");
        return $stmt->execute(['r' => $roleValid, 'id' => $userId]);
    }

    public function toggleStatus(int $userId, string $statusBaru): bool
    {
        if (!in_array($statusBaru, ['Aktif', 'Nonaktif'], true)) {
            return false;
        }
        $stmt = $this->conn->prepare("UPDATE {$this->table_name} SET status_akun = :s WHERE id_user = :id");
        return $stmt->execute(['s' => $statusBaru, 'id' => $userId]);
    }

    public function resetPassword(int $userId, string $passwordBaru): bool
    {
        $stmt = $this->conn->prepare("UPDATE {$this->table_name} SET password = :p WHERE id_user = :id");
        return $stmt->execute([
            'p'  => password_hash($passwordBaru, PASSWORD_BCRYPT),
            'id' => $userId,
        ]);
    }

    public function deleteAdmin(int $userId, int $currentUserId): bool
    {
        if ($userId === $currentUserId) {
            return false;
        }
        $stmt = $this->conn->prepare("DELETE FROM {$this->table_name} WHERE id_user = :id AND role = 'admin'");
        return $stmt->execute(['id' => $userId]);
    }

    public function deleteAnggota(int $userId): bool
    {
        $stmt = $this->conn->prepare("DELETE FROM {$this->table_name} WHERE id_user = :id AND role = 'anggota'");
        return $stmt->execute(['id' => $userId]);
    }

    public function koreksiPoin(int $userId, int $idProker, int $poinDelta, string $keterangan): bool
    {
        $sql = "INSERT INTO riwayat_poin (id_user, id_proker, sumber, kegiatan_atau_task, keterangan, poin, tanggal)
                VALUES (:u, :p, 'Koreksi Admin', 'Koreksi Manual Poin', :ket, :poin, CURRENT_DATE)";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            'u'    => $userId,
            'p'    => $idProker > 0 ? $idProker : null,
            'ket'  => $keterangan,
            'poin' => $poinDelta,
        ]);
    }
}

