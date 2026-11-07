<?php
$activeProker = $activeProker ?? null;
$currentRole  = $currentRole ?? ($activeRole ?? ($_SESSION['active_role'] ?? ($_SESSION['role'] ?? 'anggota')));
$activeMenu   = $activeMenu ?? 'dashboard';

// Hitung antrean menunggu validasi presensi (untuk badge angka di sidebar)
$countPresensiWait = $countPresensiWait ?? 0;

if (isset($pdo) && in_array($currentRole, ['koordinator', 'admin', 'super_admin'], true)) {
    $sidebarUserId = (int) ($_SESSION['user_id'] ?? 0);
    if (class_exists('PresensiModel')) {
        $countPresensiWait = (new PresensiModel($pdo))->getAntreanCount($currentRole, $sidebarUserId);
    }
}
?>
<aside class="oes-sidebar" id="oes-sidebar">
    <a href="<?php echo e($basePath); ?>dashboard/index.php" class="sidebar-brand" style="text-decoration: none;">
        <span style="font-size: 20px; font-weight: 700; color: var(--oes-white); letter-spacing: 0.4px;">Sistem OES</span>
    </a>

    <div class="sidebar-section-label">MENU</div>
    <ul class="sidebar-nav">
        <li>
            <a href="<?php echo e($basePath); ?>dashboard/index.php" class="sidebar-link <?php echo $activeMenu === 'dashboard' ? 'active' : ''; ?>">
                <span class="sidebar-link-left">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/></svg>
                    <span>Dashboard</span>
                </span>
            </a>
        </li>

        <li>
            <a href="<?php echo e($basePath); ?>proker/index.php" class="sidebar-link <?php echo $activeMenu === 'proker' ? 'active' : ''; ?>">
                <span class="sidebar-link-left">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
                    <span>Program Kerja</span>
                </span>
            </a>
        </li>

        <?php if (in_array($currentRole, ['anggota', 'koordinator'], true)): ?>
        <li>
            <a href="<?php echo e($basePath); ?>presensi/index.php" class="sidebar-link <?php echo $activeMenu === 'presensi_saya' ? 'active' : ''; ?>">
                <span class="sidebar-link-left">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
                    <span>Presensi Saya</span>
                </span>
            </a>
        </li>
        <?php endif; ?>

        <?php if (!empty($activeProker) && $currentRole === 'koordinator'): ?>
        <li style="margin-top: 14px;">
            <div class="sidebar-section-label">KELOLA (KOORDINATOR)</div>
        </li>
        <li>
            <a href="<?php echo e($basePath); ?>presensi/kelola.php?proker_id=<?php echo (int) $activeProker['id_proker']; ?>" class="sidebar-link <?php echo $activeMenu === 'kelola_presensi' ? 'active' : ''; ?>">
                <span class="sidebar-link-left">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    <span>Kelola Presensi</span>
                </span>
            </a>
        </li>
        <li>
            <a href="<?php echo e($basePath); ?>presensi/validasi.php?proker_id=<?php echo (int) $activeProker['id_proker']; ?>" class="sidebar-link <?php echo $activeMenu === 'validasi' ? 'active' : ''; ?>">
                <span class="sidebar-link-left">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                    <span>Validasi Presensi</span>
                </span>
                <?php if ($countPresensiWait > 0): ?>
                    <span class="sidebar-counter"><?php echo $countPresensiWait; ?></span>
                <?php endif; ?>
            </a>
        </li>
        <?php endif; ?>

        <?php if (in_array($currentRole, ['admin', 'super_admin'], true)): ?>
        <li style="margin-top: 14px;">
            <div class="sidebar-section-label">MANAJEMEN</div>
        </li>
        <li>
            <a href="<?php echo e($basePath); ?>presensi/validasi.php" class="sidebar-link <?php echo $activeMenu === 'validasi' ? 'active' : ''; ?>">
                <span class="sidebar-link-left">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                    <span>Validasi Presensi</span>
                </span>
                <?php if ($countPresensiWait > 0): ?>
                    <span class="sidebar-counter"><?php echo $countPresensiWait; ?></span>
                <?php endif; ?>
            </a>
        </li>
        <li>
            <a href="<?php echo e($basePath); ?>anggota/index.php" class="sidebar-link <?php echo $activeMenu === 'anggota' ? 'active' : ''; ?>">
                <span class="sidebar-link-left">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    <span>Kelola Anggota</span>
                </span>
            </a>
        </li>
        <?php endif; ?>

        <li style="margin-top: 14px;">
            <div class="sidebar-section-label">LAPORAN</div>
        </li>
        <li>
            <a href="<?php echo e($basePath); ?>dashboard/index.php" class="sidebar-link <?php echo $activeMenu === 'leaderboard' ? 'active' : ''; ?>">
                <span class="sidebar-link-left">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                    <span>Leaderboard</span>
                </span>
            </a>
        </li>
    </ul>
</aside>
