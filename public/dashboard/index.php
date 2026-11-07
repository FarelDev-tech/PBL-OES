<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../models/PresensiModel.php';
require_once __DIR__ . '/../../models/ProkerModel.php';

requireLogin();

$userId      = (int) $_SESSION['user_id'];
$currentRole = $_SESSION['role'] ?? 'anggota';

$presensiModel = new PresensiModel($pdo);
$stats         = $presensiModel->getStatistikUser($userId);
$leaderboard   = $presensiModel->getLeaderboard(10);
$globalSummary = $presensiModel->getDashboardSummary();

$pageTitle    = 'Dashboard';
$activeMenu   = 'dashboard';
$activeProker = null;
unset($_SESSION['active_proker_id'], $_SESSION['active_role']);

include __DIR__ . '/../../includes/header.php';
?>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-oes-neutral-900">Selamat Datang, <?php echo e($_SESSION['nama'] ?? 'Fungsionaris'); ?>!</h1>
    <p class="text-sm text-oes-neutral-500 mt-1">Pantau keaktifan presensi kehadiran dan perolehan poin kontribusimu di organisasi.</p>
</div>

<?php if (isAdminRole($currentRole)): ?>
<div class="mb-8">
    <div class="flex items-center justify-between mb-3">
        <h2 class="text-xs font-bold text-oes-neutral-500 uppercase tracking-wider">Ringkasan Eksekutif Organisasi (Materialized View)</h2>
        <span class="text-xs font-medium text-oes-neutral-400 bg-oes-neutral-100 px-2 py-0.5 rounded">mv_dashboard_summary</span>
    </div>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white border border-oes-neutral-200 rounded-oes-lg p-5 shadow-sm">
            <span class="text-xs font-semibold text-oes-neutral-500 uppercase tracking-wider">Proker Aktif</span>
            <div class="text-2xl lg:text-3xl font-bold text-oes-primary-800 mt-2"><?php echo (int) ($globalSummary['total_proker_aktif'] ?? 0); ?></div>
        </div>
        <div class="bg-white border border-oes-neutral-200 rounded-oes-lg p-5 shadow-sm">
            <span class="text-xs font-semibold text-oes-neutral-500 uppercase tracking-wider">Anggota Aktif</span>
            <div class="text-2xl lg:text-3xl font-bold text-oes-primary-800 mt-2"><?php echo (int) ($globalSummary['total_anggota_aktif'] ?? 0); ?></div>
        </div>
        <div class="bg-white border border-oes-neutral-200 rounded-oes-lg p-5 shadow-sm">
            <span class="text-xs font-semibold text-oes-neutral-500 uppercase tracking-wider">Presensi Disetujui</span>
            <div class="text-2xl lg:text-3xl font-bold text-oes-primary-800 mt-2"><?php echo (int) ($globalSummary['total_presensi_disetujui'] ?? 0); ?></div>
        </div>
        <div class="bg-white border border-oes-neutral-200 rounded-oes-lg p-5 shadow-sm">
            <span class="text-xs font-semibold text-oes-neutral-500 uppercase tracking-wider">Total Poin Organisasi</span>
            <div class="text-2xl lg:text-3xl font-bold text-oes-primary-800 mt-2"><?php echo formatPoin((int) ($globalSummary['total_poin_terdistribusi'] ?? 0)); ?></div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Kartu statistik ringkas -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <div class="bg-white border border-oes-neutral-200 rounded-oes-lg p-6 shadow-sm">
        <span class="text-xs font-semibold text-oes-neutral-500 uppercase tracking-wider">Total Poin Anda</span>
        <div class="flex items-baseline gap-2 mt-3">
            <span class="text-3xl lg:text-4xl font-bold text-oes-primary-800"><?php echo formatPoin($stats['total_poin']); ?></span>
            <span class="text-sm font-medium text-oes-neutral-500">poin</span>
        </div>
    </div>

    <div class="bg-white border border-oes-neutral-200 rounded-oes-lg p-6 shadow-sm">
        <span class="text-xs font-semibold text-oes-neutral-500 uppercase tracking-wider">Kegiatan Dihadiri (Disetujui)</span>
        <div class="flex items-baseline gap-2 mt-3">
            <span class="text-3xl lg:text-4xl font-bold text-oes-primary-800"><?php echo $stats['total_hadir']; ?></span>
            <span class="text-sm font-medium text-oes-neutral-500">kegiatan</span>
        </div>
    </div>

    <div class="bg-white border border-oes-neutral-200 rounded-oes-lg p-6 shadow-sm">
        <span class="text-xs font-semibold text-oes-neutral-500 uppercase tracking-wider">Menunggu Validasi</span>
        <div class="flex items-baseline gap-2 mt-3">
            <span class="text-3xl lg:text-4xl font-bold text-oes-primary-800"><?php echo $stats['total_menunggu']; ?></span>
            <span class="text-sm font-medium text-oes-neutral-500">antrean</span>
        </div>
    </div>
</div>

<!-- Tabel leaderboard fungsionaris (Full Width) -->
<div class="bg-white border border-oes-neutral-200 rounded-oes-lg p-6 shadow-sm mb-8">
    <div class="flex items-center justify-between mb-5">
        <div>
            <h2 class="text-lg font-bold text-oes-neutral-900">Leaderboard Poin</h2>
            <p class="text-xs text-oes-neutral-500 mt-1">Peringkat transparansi keaktifan anggota berdasarkan akumulasi presensi valid.</p>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full border-collapse text-left">
            <thead>
                <tr class="bg-oes-canvas-50 border-b border-oes-neutral-200 text-xs font-semibold text-oes-neutral-500 uppercase tracking-wider">
                    <th class="py-3 px-4 w-16">Rank</th>
                    <th class="py-3 px-4 min-w-[200px]">Nama Fungsionaris</th>
                    <th class="py-3 px-4">NIM</th>
                    <th class="py-3 px-4 text-center">Kehadiran</th>
                    <th class="py-3 px-4 text-right">Total Poin</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-oes-neutral-100">
                <?php if (empty($leaderboard)): ?>
                    <tr>
                        <td colspan="5" class="py-8 text-center text-sm text-oes-neutral-500">
                            Belum ada data perolehan poin presensi yang tercatat.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($leaderboard as $lb): ?>
                        <?php $isSelf = ((int) $lb['id_user'] === $userId); ?>
                        <tr class="text-sm transition-colors <?php echo $isSelf ? 'bg-oes-primary-50/70 font-semibold' : 'hover:bg-oes-neutral-50'; ?>">
                            <td class="py-3.5 px-4 align-middle whitespace-nowrap">
                                <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg font-bold text-xs <?php
                                    if ((int) $lb['ranking'] === 1) echo 'bg-amber-100 text-amber-800 border border-amber-200';
                                    elseif ((int) $lb['ranking'] === 2) echo 'bg-slate-200 text-slate-700 border border-slate-300';
                                    elseif ((int) $lb['ranking'] === 3) echo 'bg-orange-100 text-orange-800 border border-orange-200';
                                    else echo 'bg-oes-canvas-50 text-oes-neutral-600 border border-oes-neutral-200';
                                ?>">
                                    #<?php echo $lb['ranking']; ?>
                                </span>
                            </td>
                            <td class="py-3.5 px-4 align-middle">
                                <div class="flex items-center gap-2">
                                    <span class="text-oes-neutral-900 font-medium whitespace-nowrap"><?php echo e($lb['nama'] ?? ''); ?></span>
                                    <?php if ($isSelf): ?>
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-oes-primary-800 text-white shrink-0">Anda</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="py-3.5 px-4 align-middle text-oes-neutral-600 font-mono text-xs whitespace-nowrap">
                                <?php echo e($lb['nim']); ?>
                            </td>
                            <td class="py-3.5 px-4 align-middle text-center text-oes-neutral-700 text-xs whitespace-nowrap">
                                <?php echo (int) ($lb['kali_presensi'] ?? 0); ?>x
                            </td>
                            <td class="py-3.5 px-4 align-middle text-right font-bold text-oes-primary-800 whitespace-nowrap">
                                +<?php echo formatPoin($lb['total_poin']); ?> poin
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
include __DIR__ . '/../../includes/footer.php';
?>
