<?php

class AuthModel {
    private PDO $conn;
    private string $table_name = 'users';

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    public function findByNim(string $nim): ?array
    {
        $stmt = $this->conn->prepare("SELECT * FROM {$this->table_name} WHERE nim = :nim AND status_akun = 'Aktif' LIMIT 1");
        $stmt->execute(['nim' => $nim]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public function findById(int $userId): ?array
    {
        $stmt = $this->conn->prepare("SELECT * FROM {$this->table_name} WHERE id_user = :id_user LIMIT 1");
        $stmt->execute(['id_user' => $userId]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public function updatePassword(int $userId, string $newPasswordHash): bool
    {
        $stmt = $this->conn->prepare("UPDATE {$this->table_name} SET password = :password WHERE id_user = :id_user");
        return $stmt->execute([
            'password' => $newPasswordHash,
            'id_user'  => $userId,
        ]);
    }
}

