<?php
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../models/AuthModel.php';
require_once __DIR__ . '/../models/ProkerModel.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pageTitle   = $pageTitle ?? 'Dashboard';
$currentUser = $_SESSION['nama'] ?? 'Pengguna';

// Sinkronkan nama user terbaru dari database jika sudah login
if (isset($pdo) && !empty($_SESSION['user_id'])) {
    $uSync = (new AuthModel($pdo))->findById((int) $_SESSION['user_id']);
    if ($uSync) {
        $_SESSION['nama'] = $uSync['nama'];
        $currentUser      = $uSync['nama'];
    }
}

$activeProker = $activeProker ?? null;

if (!empty($activeProker) && isset($pdo) && !empty($_SESSION['user_id'])) {
    $sessUid = (int) $_SESSION['user_id'];
    $userGlobalRole = $_SESSION['role'] ?? 'anggota';

    if (in_array($userGlobalRole, ['admin', 'super_admin'], true)) {
        $currentRole = $userGlobalRole;
        $badgeRole   = $userGlobalRole;
        $divisiLabel = '';
    } else {
        $panitiaUser = (new ProkerModel($pdo))->getPanitiaUser($sessUid, (int) $activeProker['id_proker']);
        if ($panitiaUser && $panitiaUser['jabatan'] === 'Koordinator') {
            $currentRole             = 'koordinator';
            $badgeRole               = 'koordinator';
            $divisiLabel             = $panitiaUser['nama_divisi'] ?? '';
            $activeRole              = 'koordinator';
            $_SESSION['active_role'] = 'koordinator';
        } else {
            $currentRole             = 'anggota';
            $badgeRole               = 'anggota';
            $divisiLabel             = $panitiaUser['nama_divisi'] ?? '';
            $activeRole              = 'anggota';
            $_SESSION['active_role'] = 'anggota';
        }
    }
} else {
    $userGlobalRole = $_SESSION['role'] ?? 'anggota';
    $currentRole    = in_array($userGlobalRole, ['admin', 'super_admin'], true) ? $userGlobalRole : 'anggota';
    $activeRole     = $currentRole;
    $badgeRole      = $currentRole;
    $divisiLabel    = '';
    unset($_SESSION['active_proker_id'], $_SESSION['active_role']);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($pageTitle); ?> — Sistem OES</title>
    <link rel="stylesheet" href="<?php echo e($basePath); ?>assets/css/tailwind.css">
    <link rel="stylesheet" href="<?php echo e($basePath); ?>assets/css/style.css">
</head>
<body>
<div class="app-layout">
    <?php include __DIR__ . '/sidebar.php'; ?>

    <div class="app-main">
        <header class="oes-topbar">
            <div class="topbar-left">
                <?php if (!empty($activeProker)): ?>
                    <div class="form-select" style="display: inline-flex; align-items: center; cursor: default; user-select: none;">
                        Proker: <?php echo e($activeProker['nama_proker']); ?>
                    </div>
                <?php else: ?>
                    <span class="topbar-title"><?php echo e($pageTitle); ?></span>
                <?php endif; ?>
            </div>

            <div class="topbar-right">
                <span class="topbar-user-name"><?php echo e($currentUser); ?></span>

                <?php if ($badgeRole === 'koordinator'): ?>
                    <span class="oes-badge badge-dark">Koordinator</span>
                    <?php if (!empty($divisiLabel)): ?>
                        <span class="oes-badge badge-neutral"><?php echo e($divisiLabel); ?></span>
                    <?php endif; ?>
                <?php elseif ($badgeRole === 'admin'): ?>
                    <span class="oes-badge badge-dark">Admin</span>
                <?php elseif ($badgeRole === 'super_admin'): ?>
                    <span class="oes-badge badge-primary-dark">Super Admin</span>
                <?php else: ?>
                    <span class="oes-badge badge-neutral">Anggota</span>
                    <?php if (!empty($activeProker) && !empty($divisiLabel)): ?>
                        <span class="oes-badge badge-outline"><?php echo e($divisiLabel); ?></span>
                    <?php endif; ?>
                <?php endif; ?>

                <button type="button" id="topbar-avatar-btn" class="topbar-avatar-btn" aria-label="Menu Profil">
                    <span class="topbar-avatar"><?php echo e(inisialNama($currentUser)); ?></span>
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
                </button>

                <div class="profile-dropdown" id="profile-dropdown">
                    <a href="<?php echo e($basePath); ?>auth/ganti_password.php">Ganti Password</a>
                    <a href="<?php echo e($basePath); ?>auth/logout.php">Keluar</a>
                </div>
            </div>
        </header>

        <main class="page-content">
            <?php if (!empty($_SESSION['flash'])): ?>
                <?php $fl = $_SESSION['flash']; unset($_SESSION['flash']); ?>
                <div class="flash-banner flash-<?php echo e($fl['type'] ?? 'success'); ?>">
                    <span><?php echo e($fl['pesan'] ?? ''); ?></span>
                    <button type="button" onclick="this.parentElement.remove()" style="background:none;border:none;cursor:pointer;font-weight:700;">&times;</button>
                </div>
            <?php endif; ?>
