<?php
/**
 * Mencari user berdasarkan NIM yang berstatus Aktif
 */
function cariUserByNim(PDO $pdo, string $nim): ?array
{
    $stmt = $pdo->prepare("SELECT * FROM users WHERE nim = :nim AND status_akun = 'Aktif' LIMIT 1");
    $stmt->execute(['nim' => $nim]);
    $user = $stmt->fetch();
    return $user ?: null;
}

/**
 * Mencari user berdasarkan ID
 */
function cariUserById(PDO $pdo, int $userId): ?array
{
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id_user = :id_user LIMIT 1");
    $stmt->execute(['id_user' => $userId]);
    $user = $stmt->fetch();
    return $user ?: null;
}

/**
 * Mengubah password user
 */
function updatePasswordUser(PDO $pdo, int $userId, string $newPasswordHash): bool
{
    $stmt = $pdo->prepare("UPDATE users SET password = :password WHERE id_user = :id_user");
    return $stmt->execute([
        'password' => $newPasswordHash,
        'id_user'  => $userId,
    ]);
}
