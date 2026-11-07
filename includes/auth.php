<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$basePath = '../';

// Memastikan pengguna sudah login
function requireLogin(): void
{
    global $basePath, $pdo;
    if (empty($_SESSION['user_id'])) {
        header('Location: ' . $basePath . 'auth/login.php');
        exit;
    }

    if (isset($pdo) && !empty($_SESSION['user_id'])) {
        try {
            $sessUserId = (int) $_SESSION['user_id'];
            $stmt = $pdo->prepare("SELECT role, status_akun FROM users WHERE id_user = :u LIMIT 1");
            $stmt->execute(['u' => $sessUserId]);
            $u = $stmt->fetch();
            if ($u) {
                $_SESSION['role'] = $u['role'];
            }
        } catch (Throwable $e) {
            // Lanjutkan jika koneksi DB sesaat terkendala
        }
    }
}

// Membatasi akses halaman berdasarkan role sistem
function requireRole(array $rolesDiizinkan): void
{
    global $basePath;
    requireLogin();
    $roleSekarang = $_SESSION['role'] ?? 'anggota';

    $isAllowed = in_array($roleSekarang, $rolesDiizinkan, true);
    if (!$isAllowed && isAdminRole($roleSekarang)) {
        if (in_array('admin', $rolesDiizinkan, true) || in_array('super_admin', $rolesDiizinkan, true) || in_array('koordinator', $rolesDiizinkan, true)) {
            $isAllowed = true;
        }
    }

    if (!$isAllowed) {
        $_SESSION['flash'] = [
            'type'  => 'error',
            'pesan' => 'Akses ditolak: Anda tidak memiliki izin untuk mengakses halaman tersebut.'
        ];
        header('Location: ' . $basePath . 'dashboard/index.php');
        exit;
    }
}

// Helper cek role Admin atau Super Admin
function isAdminRole(?string $role = null): bool
{
    $r = $role ?? ($_SESSION['role'] ?? 'anggota');
    return in_array($r, ['admin', 'super_admin'], true);
}
