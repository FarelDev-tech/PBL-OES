<?php
// =========================================================================
// DASHBOARD LANDING (Milestone Auth & Login OES)
// =========================================================================

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/auth.php';

requireLogin();

$pageTitle = 'Dashboard Utama';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — OES</title>
    <link rel="stylesheet" href="../assets/css/design-system.css">
    <link rel="stylesheet" href="../assets/css/tailwind.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body style="background: var(--oes-neutral-50); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px;">
    <div style="background: #ffffff; border: 1px solid var(--oes-neutral-200); border-radius: 16px; padding: 40px; max-width: 540px; width: 100%; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05); text-align: center;">
        <div style="width: 64px; height: 64px; border-radius: 50%; background: #dcfce7; color: #16a34a; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px auto;">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M20 6L9 17l-5-5"/>
            </svg>
        </div>
        <h1 style="font-size: 22px; font-weight: 700; color: var(--oes-neutral-900); margin-bottom: 8px;">Login Berhasil!</h1>
        <p style="font-size: 14px; color: var(--oes-neutral-600); margin-bottom: 24px;">Selamat datang di Sistem OES (Organization Engagement System).</p>
        
        <div style="background: var(--oes-neutral-100); border-radius: 10px; padding: 16px; margin-bottom: 24px; text-align: left; font-size: 14px; line-height: 1.8;">
            <div><strong>Nama:</strong> <?php echo e($_SESSION['nama'] ?? '—'); ?></div>
            <div><strong>NIM:</strong> <?php echo e($_SESSION['nim'] ?? '—'); ?></div>
            <div><strong>Role:</strong> <span class="oes-badge badge-dark"><?php echo e(labelRole($_SESSION['role'] ?? 'anggota')); ?></span></div>
            <div><strong>Email:</strong> <?php echo e($_SESSION['email'] ?? '—'); ?></div>
        </div>

        <a href="../auth/logout.php" class="btn btn-danger" style="display: inline-block; width: 100%; text-decoration: none; padding: 12px; font-weight: 600; border-radius: 8px; text-align: center;">
            Keluar (Logout)
        </a>
    </div>
</body>
</html>
