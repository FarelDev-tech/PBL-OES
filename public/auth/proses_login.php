<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/AuthModel.php';
require_once __DIR__ . '/../../models/ProkerModel.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/helpers.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit;
}

if (!verifyCsrfToken()) {
    $_SESSION['login_error'] = 'Sesi Anda telah kedaluwarsa. Silakan coba lagi.';
    header('Location: login.php');
    exit;
}

$nim      = trim($_POST['nim'] ?? '');
$password = (string) ($_POST['password'] ?? '');

if ($nim === '' || $password === '') {
    $_SESSION['login_error'] = 'NIM dan password wajib diisi.';
    $_SESSION['old_nim']     = $nim;
    header('Location: login.php');
    exit;
}

$user = (new AuthModel($pdo))->findByNim($nim);

if (!$user) {
    $_SESSION['login_error'] = 'NIM atau password salah.';
    $_SESSION['old_nim']     = $nim;
    header('Location: login.php');
    exit;
}

// Verifikasi password
$passwordValid = password_verify($password, $user['password']) || ($password === $user['password']);

if (!$passwordValid) {
    $_SESSION['login_error'] = 'NIM atau password salah.';
    $_SESSION['old_nim']     = $nim;
    header('Location: login.php');
    exit;
}

// Set session pengguna
$_SESSION['user_id'] = (int) $user['id_user'];
$_SESSION['nim']     = $user['nim'];
$_SESSION['nama']    = $user['nama'];
$_SESSION['role']    = $user['role'];
$_SESSION['email']   = $user['email'];

unset($_SESSION['active_proker_id'], $_SESSION['active_role']);

// Direct ke dashboard utama
header('Location: ../dashboard/index.php');
exit;
