<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/AuthModel.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/auth.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ganti_password.php');
    exit;
}

if (!verifyCsrfToken()) {
    $_SESSION['pass_error'] = 'Sesi Anda telah kedaluwarsa. Silakan ulangi kembali.';
    header('Location: ganti_password.php');
    exit;
}

$userId             = (int) $_SESSION['user_id'];
$passwordBaru       = (string) ($_POST['password_baru'] ?? '');
$konfirmasiPassword = (string) ($_POST['konfirmasi_password'] ?? '');

$_SESSION['old_new_pass'] = $passwordBaru;

// Validasi Kolom Kosong
if ($passwordBaru === '' || $konfirmasiPassword === '') {
    $_SESSION['pass_error'] = 'Password baru dan konfirmasi password wajib diisi.';
    header('Location: ganti_password.php');
    exit;
}

// Validasi Kesamaan
if ($passwordBaru !== $konfirmasiPassword) {
    $_SESSION['pass_error'] = 'Konfirmasi password tidak cocok dengan password baru.';
    header('Location: ganti_password.php');
    exit;
}

// Validasi Panjang Minimal 8 Karakter
if (strlen($passwordBaru) < 8) {
    $_SESSION['pass_error'] = 'Password baru minimal harus terdiri dari 8 karakter.';
    header('Location: ganti_password.php');
    exit;
}

// Validasi Kombinasi Huruf dan Angka
if (!preg_match('/[a-zA-Z]/', $passwordBaru) || !preg_match('/[0-9]/', $passwordBaru)) {
    $_SESSION['pass_error'] = 'Password baru harus mengombinasikan huruf dan angka.';
    header('Location: ganti_password.php');
    exit;
}

// Hashing & Update ke Database
try {
    $hash = password_hash($passwordBaru, PASSWORD_BCRYPT);
    $sukses = (new AuthModel($pdo))->updatePassword($userId, $hash);

    if ($sukses) {
        unset($_SESSION['old_new_pass']);
        $_SESSION['flash'] = [
            'type'  => 'success',
            'pesan' => 'Password akun Anda berhasil diperbarui.',
        ];
        header('Location: ganti_password.php?sukses=1');
        exit;
    } else {
        $_SESSION['pass_error'] = 'Gagal menyimpan password baru ke database.';
        header('Location: ganti_password.php');
        exit;
    }
} catch (Throwable $e) {
    $_SESSION['pass_error'] = 'Terjadi kesalahan sistem: ' . $e->getMessage();
    header('Location: ganti_password.php');
    exit;
}
