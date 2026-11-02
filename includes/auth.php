<?php
// Guard autentikasi & hak akses

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$basePath = '../';

// Memastikan pengguna sudah login
function requireLogin(): void
{
    global $basePath;
    if (empty($_SESSION['user_id'])) {
        header('Location: ' . $basePath . 'auth/login.php');
        exit;
    }
}

// Membatasi akses berdasarkan role tertentu
function requireRole(array $rolesDiizinkan): void
{
    global $basePath;
    requireLogin();
    $roleSekarang = $_SESSION['role'] ?? 'anggota';
    if (!in_array($roleSekarang, $rolesDiizinkan, true)) {
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
