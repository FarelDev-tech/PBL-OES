<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../models/AnggotaModel.php';
require_once __DIR__ . '/../../models/ProkerModel.php';
require_once __DIR__ . '/../../models/PresensiModel.php';

requireRole(['admin', 'super_admin']);

$pageTitle         = 'Kelola Anggota';
$activeMenu        = 'anggota';
$activeRole        = $_SESSION['role'] ?? 'admin';
$currentUserId = (int) ($_SESSION['user_id'] ?? 0);

$anggotaModel  = new AnggotaModel($pdo);
$prokerModel   = new ProkerModel($pdo);
$presensiModel = new PresensiModel($pdo);

$countPresensiWait = $presensiModel->getAntreanCount($activeRole, $currentUserId);

$keyword      = trim($_GET['q'] ?? '');
$roleFilter   = $_GET['role'] ?? '';
$statusFilter = $_GET['status'] ?? '';

$daftarAnggota = $anggotaModel->getAll($keyword, $roleFilter, $statusFilter);
$prokerAktif   = $prokerModel->getAll('Berjalan');

include __DIR__ . '/../../includes/header.php';
?>

<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-oes-neutral-900 tracking-tight mb-1">Kelola Anggota</h1>
        <p class="text-sm text-oes-neutral-500">Manajemen akun fungsionaris, penetapan peran, status aktif, reset password, dan koreksi poin.</p>
    </div>
    <div>
        <button type="button" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg bg-oes-primary-950 hover:bg-oes-primary-900 text-white text-sm font-medium transition-colors cursor-pointer" onclick="bukaPopup('modal-tambah-anggota')">
            + Tambah Anggota
        </button>
    </div>
</div>

<form method="get" action="" class="flex flex-wrap items-center gap-3 mb-5">
    <div class="relative flex-1 min-w-[240px]">
        <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-oes-neutral-500 pointer-events-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input type="text" name="q" class="w-full pl-10 pr-4 py-2 text-[13px] bg-white border border-oes-neutral-300 rounded-md text-oes-neutral-900 placeholder:text-oes-neutral-400 focus:outline-none focus:border-oes-primary-700 transition-colors" placeholder="Cari nama, NIM, atau email..." value="<?php echo e($keyword); ?>">
    </div>

    <select name="role" class="form-select" onchange="this.form.submit()">
        <option value="">Semua Role</option>
        <option value="anggota" <?php echo $roleFilter === 'anggota' ? 'selected' : ''; ?>>Anggota</option>
        <option value="admin" <?php echo $roleFilter === 'admin' ? 'selected' : ''; ?>>Admin</option>
        <option value="super_admin" <?php echo $roleFilter === 'super_admin' ? 'selected' : ''; ?>>Super Admin</option>
    </select>

    <select name="status" class="form-select" onchange="this.form.submit()">
        <option value="">Semua Status</option>
        <option value="Aktif" <?php echo $statusFilter === 'Aktif' ? 'selected' : ''; ?>>Aktif</option>
        <option value="Nonaktif" <?php echo $statusFilter === 'Nonaktif' ? 'selected' : ''; ?>>Nonaktif</option>
    </select>

    <?php if ($keyword !== '' || $roleFilter !== '' || $statusFilter !== ''): ?>
        <a href="index.php" class="btn btn-outline">Reset</a>
    <?php endif; ?>
</form>

<div class="bg-white border border-oes-neutral-200 rounded-2xl shadow-xs overflow-hidden">
    <div class="w-full overflow-x-auto">
        <table class="w-full min-w-[900px] border-collapse text-left">
            <thead>
                <tr class="bg-oes-canvas-50 border-b border-oes-neutral-200 text-xs font-semibold text-oes-neutral-500 uppercase tracking-wider">
                    <th class="py-3.5 px-5">Nama &amp; Email</th>
                    <th class="py-3.5 px-5">NIM</th>
                    <th class="py-3.5 px-5">Role Sistem</th>
                    <th class="py-3.5 px-5">Total Poin</th>
                    <th class="py-3.5 px-5">Status</th>
                    <th class="py-3.5 px-5">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-oes-neutral-200 text-sm">
                <?php if (empty($daftarAnggota)): ?>
                    <tr>
                        <td colspan="6" class="py-10 px-5 text-center text-oes-neutral-500">
                            Tidak ada data anggota yang sesuai dengan filter pencarian.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($daftarAnggota as $u): ?>
                        <?php
                        $isNonaktif = ($u['status_akun'] === 'Nonaktif');
                        $isDiriSendiri = ((int) $u['id_user'] === $currentUserId);
                        ?>
                        <tr class="<?php echo $isNonaktif ? 'bg-oes-secondary-50 text-oes-neutral-500' : 'hover:bg-oes-canvas-50 text-oes-neutral-900'; ?> transition-colors">
                            <td class="py-4 px-5">
                                <div class="font-semibold text-oes-neutral-900">
                                    <?php echo e($u['nama']); ?>
                                    <?php if ($isDiriSendiri): ?>
                                        <span class="text-[11px] text-oes-primary-700 font-semibold ml-1">(Anda)</span>
                                    <?php endif; ?>
                                </div>
                                <div class="text-xs text-oes-neutral-500"><?php echo e($u['email']); ?></div>
                            </td>
                            <td class="py-4 px-5 text-oes-neutral-700"><?php echo e($u['nim']); ?></td>
                            <td class="py-4 px-5">
                                <?php if ($u['role'] === 'super_admin'): ?>
                                    <span class="oes-badge badge-primary-dark">Super Admin</span>
                                <?php elseif ($u['role'] === 'admin'): ?>
                                    <span class="oes-badge badge-dark">Admin</span>
                                <?php else: ?>
                                    <span class="oes-badge badge-neutral">Anggota</span>
                                <?php endif; ?>
                            </td>
                            <td class="py-4 px-5 font-bold text-oes-primary-800">
                                <?php echo formatPoin($u['total_poin']); ?> poin
                            </td>
                            <td class="py-4 px-5">
                                <?php echo renderBadge($u['status_akun']); ?>
                            </td>
                            <td class="py-4 px-5">
                                <div class="inline-flex items-center gap-2 flex-nowrap">
                                    <button type="button" class="btn btn-outline btn-sm"
                                            onclick='bukaModalKoreksiPoin(<?php echo json_encode([
                                                "id_user" => (int) $u["id_user"],
                                                "nama"    => $u["nama"],
                                                "poin"    => (int) $u["total_poin"],
                                            ], JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'>
                                        Koreksi Poin
                                    </button>

                                    <?php if ($u['role'] !== 'super_admin' || $activeRole === 'super_admin'): ?>
                                        <button type="button" class="btn btn-outline btn-sm"
                                                onclick="bukaPopupKonfirmasi({
                                                    judul: 'Reset Password ke NIM?',
                                                    deskripsi: 'Password akun <?php echo e(addslashes($u['nama'])); ?> akan direset kembali menjadi NIM (<?php echo e(addslashes($u['nim'])); ?>).',
                                                    action: 'proses.php',
                                                    fields: { action: 'reset_password', id_user: <?php echo (int) $u['id_user']; ?> },
                                                    teksTombol: 'Ya, Reset Password',
                                                    berbahaya: false
                                                })">
                                            Reset Pass
                                        </button>
                                    <?php endif; ?>

                                    <?php if (!$isDiriSendiri && $u['role'] !== 'super_admin'): ?>
                                        <?php if ($u['role'] !== 'admin' || $activeRole === 'super_admin'): ?>
                                            <button type="button" class="btn btn-outline btn-sm"
                                                    onclick='bukaModalUbahRole(<?php echo json_encode([
                                                        "id_user" => (int) $u["id_user"],
                                                        "nama"    => $u["nama"],
                                                        "role"    => $u["role"],
                                                    ], JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'>
                                                Role
                                            </button>

                                            <?php $targetStatus = $isNonaktif ? 'Aktif' : 'Nonaktif'; ?>
                                            <button type="button" class="btn <?php echo $isNonaktif ? 'btn-primary' : 'btn-danger-outline'; ?> btn-sm"
                                                    onclick="bukaPopupKonfirmasi({
                                                        judul: '<?php echo $isNonaktif ? 'Aktifkan Akun?' : 'Nonaktifkan Akun?'; ?>',
                                                        deskripsi: 'Status akun <?php echo e(addslashes($u['nama'])); ?> akan diubah menjadi <?php echo $targetStatus; ?>.',
                                                        action: 'proses.php',
                                                        fields: { action: 'toggle_status', id_user: <?php echo (int) $u['id_user']; ?>, status_baru: '<?php echo $targetStatus; ?>' },
                                                        teksTombol: 'Ya, <?php echo $isNonaktif ? 'Aktifkan' : 'Nonaktifkan'; ?>',
                                                        berbahaya: <?php echo $isNonaktif ? 'false' : 'true'; ?>
                                                    })">
                                                <?php echo $isNonaktif ? 'Aktifkan' : 'Nonaktifkan'; ?>
                                            </button>
                                        <?php endif; ?>

                                        <?php if ($activeRole === 'super_admin' && $u['role'] === 'admin'): ?>
                                            <button type="button" class="btn btn-danger-outline btn-sm"
                                                    onclick="bukaPopupKonfirmasi({
                                                        judul: 'Hapus Akun Admin?',
                                                        deskripsi: 'Tindakan ini hanya dimiliki oleh Super Admin. Akun Admin <?php echo e(addslashes($u['nama'])); ?> akan dihapus permanen.',
                                                        action: 'proses.php',
                                                        fields: { action: 'hapus_admin', id_user: <?php echo (int) $u['id_user']; ?> },
                                                        teksTombol: 'Ya, Hapus Admin',
                                                        berbahaya: true
                                                    })">
                                                Hapus Admin
                                            </button>
                                        <?php elseif ($u['role'] === 'anggota' && in_array($activeRole, ['admin', 'super_admin'], true)): ?>
                                            <button type="button" class="btn btn-danger-outline btn-sm"
                                                    onclick="bukaPopupKonfirmasi({
                                                        judul: 'Hapus Akun Anggota?',
                                                        deskripsi: 'Akun Anggota <?php echo e(addslashes($u['nama'])); ?> (<?php echo e(addslashes($u['nim'])); ?>) akan dihapus secara permanen beserta data presensi dan kepanitiaan terkait.',
                                                        action: 'proses.php',
                                                        fields: { action: 'hapus_anggota', id_user: <?php echo (int) $u['id_user']; ?> },
                                                        teksTombol: 'Ya, Hapus Anggota',
                                                        berbahaya: true
                                                    })">
                                                Hapus Anggota
                                            </button>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="popup-backdrop modal-backdrop" id="modal-tambah-anggota">
    <div class="popup-dialog modal-dialog">
        <div class="popup-header modal-header">
            <div>
                <h3 class="popup-title modal-title">Tambah Anggota Baru</h3>
                <p class="popup-subtitle modal-subtitle">Daftarkan akun fungsionaris baru ke dalam sistem OES.</p>
            </div>
            <button type="button" class="popup-close-btn modal-close-btn" onclick="tutupPopup('modal-tambah-anggota')">&times;</button>
        </div>

        <form method="post" action="proses.php">
            <?php echo csrfField(); ?>
            <input type="hidden" name="action" value="tambah_anggota">

            <div class="form-row-2">
                <div class="form-group">
                    <label class="form-label">NIM</label>
                    <input type="text" name="nim" class="form-control w-full" placeholder="Contoh: 24410706001" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Role Sistem</label>
                    <select name="role" class="form-select w-full" required>
                        <option value="anggota">Anggota</option>
                        <?php if ($activeRole === 'super_admin'): ?>
                            <option value="admin">Admin (Pengurus Inti)</option>
                        <?php endif; ?>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Nama Lengkap</label>
                <input type="text" name="nama" class="form-control w-full" placeholder="Masukkan nama lengkap mahasiswa" required>
            </div>

            <div class="form-group">
                <label class="form-label">Email Kampus</label>
                <input type="email" name="email" class="form-control w-full" placeholder="nama@student.polinema.ac.id" required>
            </div>

            <div class="p-3 bg-oes-canvas-50 border border-oes-neutral-200 rounded-lg text-xs text-oes-neutral-700 mb-4">
                <strong>Informasi Password:</strong> Password awal akun fungsionaris otomatis diatur sama dengan <strong>NIM</strong> yang didaftarkan. Anggota dapat mengganti password setelah login.
            </div>

            <div class="popup-footer modal-footer">
                <button type="button" class="btn btn-outline" onclick="tutupPopup('modal-tambah-anggota')">Batal</button>
                <button type="submit" class="btn btn-dark">Simpan Anggota</button>
            </div>
        </form>
    </div>
</div>

<div class="popup-backdrop modal-backdrop" id="modal-ubah-role">
    <div class="popup-dialog modal-dialog" style="max-width: 420px;">
        <div class="popup-header modal-header">
            <div>
                <h3 class="popup-title modal-title">Ubah Peran Akun</h3>
                <p class="popup-subtitle modal-subtitle" id="role-sub-nama"></p>
            </div>
            <button type="button" class="popup-close-btn modal-close-btn" onclick="tutupPopup('modal-ubah-role')">&times;</button>
        </div>

        <form method="post" action="proses.php">
            <?php echo csrfField(); ?>
            <input type="hidden" name="action" value="ubah_role">
            <input type="hidden" name="id_user" id="role-id-user">

            <div class="form-group">
                <label class="form-label">Pilih Role Baru</label>
                <select name="role_baru" id="role-select-baru" class="form-select w-full">
                    <option value="anggota">Anggota</option>
                    <?php if ($activeRole === 'super_admin'): ?>
                        <option value="admin">Admin</option>
                    <?php endif; ?>
                </select>
            </div>

            <div class="popup-footer modal-footer">
                <button type="button" class="btn btn-outline" onclick="tutupPopup('modal-ubah-role')">Batal</button>
                <button type="submit" class="btn btn-dark">Simpan Role</button>
            </div>
        </form>
    </div>
</div>

<div class="popup-backdrop modal-backdrop" id="modal-koreksi-poin">
    <div class="popup-dialog modal-dialog" style="max-width: 460px;">
        <div class="popup-header modal-header">
            <div>
                <h3 class="popup-title modal-title">Koreksi Poin Anggota</h3>
                <p class="popup-subtitle modal-subtitle" id="koreksi-sub-nama"></p>
            </div>
            <button type="button" class="popup-close-btn modal-close-btn" onclick="tutupPopup('modal-koreksi-poin')">&times;</button>
        </div>

        <form method="post" action="proses.php">
            <?php echo csrfField(); ?>
            <input type="hidden" name="action" value="koreksi_poin">
            <input type="hidden" name="id_user" id="koreksi-id-user">

            <div class="form-group">
                <label class="form-label">Terkait Program Kerja</label>
                <select name="id_proker" class="form-select w-full" required>
                    <?php foreach ($prokerAktif as $pk): ?>
                        <option value="<?php echo (int) $pk['id_proker']; ?>"><?php echo e($pk['nama_proker']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Nilai Perubahan Poin (Positif / Negatif)</label>
                <input type="number" name="poin_delta" class="form-control w-full" placeholder="Contoh: 10 atau -5" required>
                <div class="text-xs text-oes-neutral-500 mt-1">Gunakan tanda minus (-) untuk pengurangan poin.</div>
            </div>

            <div class="form-group">
                <label class="form-label">Alasan / Keterangan Koreksi</label>
                <textarea name="keterangan" class="form-control w-full" rows="2" placeholder="Contoh: Bonus apresiasi kepanitiaan terbaik..." required></textarea>
            </div>

            <div class="popup-footer modal-footer">
                <button type="button" class="btn btn-outline" onclick="tutupPopup('modal-koreksi-poin')">Batal</button>
                <button type="submit" class="btn btn-dark">Simpan Koreksi</button>
            </div>
        </form>
    </div>
</div>

<script>
function bukaModalUbahRole(data) {
    document.getElementById('role-id-user').value = data.id_user;
    document.getElementById('role-sub-nama').textContent = data.nama;
    document.getElementById('role-select-baru').value = data.role;
    bukaPopup('modal-ubah-role');
}

function bukaModalKoreksiPoin(data) {
    document.getElementById('koreksi-id-user').value = data.id_user;
    document.getElementById('koreksi-sub-nama').textContent = data.nama + ' — Saat ini: ' + data.poin + ' poin';
    bukaPopup('modal-koreksi-poin');
}
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
