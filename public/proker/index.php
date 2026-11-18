<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../models/ProkerModel.php';
require_once __DIR__ . '/../../models/PresensiModel.php';

requireLogin();

$pageTitle         = 'Program Kerja Organisasi';
$activeMenu        = 'proker';
$activeProker      = null;
unset($_SESSION['active_proker_id'], $_SESSION['active_role']);
$activeRole        = $_SESSION['role'] ?? 'anggota';
$currentUserId     = (int) ($_SESSION['user_id'] ?? 0);
$prokerModel   = new ProkerModel($pdo);
$presensiModel = new PresensiModel($pdo);

$isAdmin           = isAdminRole($activeRole);
$countPresensiWait = $presensiModel->getAntreanCount($activeRole, $currentUserId);

$statusFilter = $_GET['status'] ?? '';
$daftarProker = $prokerModel->getAll($statusFilter);

$prokerUserMap = [];
if (!$isAdmin) {
    $listProkerUser = $prokerModel->getProkerByUser($currentUserId);
    foreach ($listProkerUser as $pu) {
        $prokerUserMap[(int) $pu['id_proker']] = $pu;
    }
}

include __DIR__ . '/../../includes/header.php';
?>

<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-oes-neutral-900 tracking-tight mb-1">Program Kerja Organisasi</h1>
        <p class="text-sm text-oes-neutral-500">Daftar program kerja dan kepanitiaan organisasi periode berjalan.</p>
    </div>
    <?php if ($isAdmin): ?>
        <div>
            <button type="button" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg bg-oes-primary-950 hover:bg-oes-primary-900 text-white text-sm font-medium transition-colors cursor-pointer" onclick="bukaPopup('modal-tambah-proker')">
                + Tambah Program Kerja
            </button>
        </div>
    <?php endif; ?>
</div>

<form method="get" action="" class="flex flex-wrap items-center gap-3 mb-5">
    <select name="status" class="form-select" onchange="this.form.submit()">
        <option value="">Semua Status Proker</option>
        <option value="Berjalan" <?php echo $statusFilter === 'Berjalan' ? 'selected' : ''; ?>>Berjalan</option>
        <option value="Perencanaan" <?php echo $statusFilter === 'Perencanaan' ? 'selected' : ''; ?>>Perencanaan</option>
        <option value="Selesai" <?php echo $statusFilter === 'Selesai' ? 'selected' : ''; ?>>Selesai</option>
    </select>
</form>

<?php if (empty($daftarProker)): ?>
    <div class="empty-state">
        <div class="empty-state-icon">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
        </div>
        <div class="empty-state-title">Belum Ada Program Kerja</div>
        <div class="empty-state-desc">Tidak ada program kerja yang tercatat untuk filter ini.</div>
    </div>
<?php else: ?>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
        <?php foreach ($daftarProker as $pk): ?>
            <?php
            $idPk = (int) $pk['id_proker'];
            $infoKepanitiaan = $prokerUserMap[$idPk] ?? null;
            ?>
            <div class="bg-white border border-oes-neutral-200 rounded-2xl p-6 shadow-xs flex flex-col justify-between gap-4">
                <div>
                    <div class="flex items-start justify-between gap-3 mb-2">
                        <div>
                            <div class="text-base font-bold text-oes-neutral-900 mb-0.5"><?php echo e($pk['nama_proker']); ?></div>
                            <div class="text-xs text-oes-neutral-500">Periode <?php echo e($pk['periode'] ?? '2026/2027'); ?></div>
                        </div>
                        <div class="flex items-center gap-1.5 flex-wrap justify-end">
                            <?php if ($infoKepanitiaan): ?>
                                <span class="oes-badge <?php echo $infoKepanitiaan['jabatan'] === 'Koordinator' ? 'badge-dark' : 'badge-primary'; ?>">
                                    <?php echo e($infoKepanitiaan['jabatan']); ?>
                                    <?php echo !empty($infoKepanitiaan['nama_divisi']) ? ' • ' . e($infoKepanitiaan['nama_divisi']) : ''; ?>
                                </span>
                            <?php endif; ?>
                            <?php echo renderBadge($pk['status_proker']); ?>
                        </div>
                    </div>

                    <?php if (!empty($pk['deskripsi'])): ?>
                        <p class="text-[13px] text-oes-neutral-700 mt-2 mb-3.5 leading-relaxed">
                            <?php echo e($pk['deskripsi']); ?>
                        </p>
                    <?php endif; ?>

                    <div class="grid grid-cols-2 gap-3 mt-3">
                        <div class="border border-oes-neutral-200 rounded-xl p-3 bg-oes-canvas-50">
                            <div class="text-[11px] font-medium text-oes-neutral-500 mb-0.5">Rentang Pelaksanaan</div>
                            <div class="text-[13px] font-semibold text-oes-neutral-900"><?php echo formatTanggal($pk['tanggal_mulai']); ?></div>
                            <div class="text-xs text-oes-neutral-700">s/d <?php echo formatTanggal($pk['tanggal_selesai']); ?></div>
                        </div>
                        <div class="border border-oes-neutral-200 rounded-xl p-3 bg-oes-canvas-50">
                            <div class="text-[11px] font-medium text-oes-neutral-500 mb-0.5">Statistik Proker</div>
                            <div class="text-[13px] font-semibold text-oes-primary-800"><?php echo (int) $pk['total_kegiatan']; ?> Kegiatan</div>
                            <div class="text-xs text-oes-neutral-700"><?php echo (int) $pk['total_panitia']; ?> Anggota Panitia</div>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-between gap-2.5 pt-3.5 border-t border-oes-neutral-200">
                    <a href="detail.php?id=<?php echo $idPk; ?>" class="inline-flex items-center justify-center px-3.5 py-2 rounded-lg bg-oes-primary-800 hover:bg-oes-primary-950 text-white text-xs font-medium transition-colors">
                        Lihat Detail &amp; Kepanitiaan
                    </a>

                    <?php if ($isAdmin): ?>
                        <div class="inline-flex items-center gap-2">
                            <button type="button" class="btn btn-outline btn-sm"
                                    onclick='bukaModalEditProker(<?php echo json_encode([
                                        "id_proker"       => $idPk,
                                        "nama_proker"     => $pk["nama_proker"],
                                        "periode"         => $pk["periode"] ?? "2026/2027",
                                        "deskripsi"       => $pk["deskripsi"] ?? "",
                                        "tanggal_mulai"   => $pk["tanggal_mulai"],
                                        "tanggal_selesai" => $pk["tanggal_selesai"],
                                        "status_proker"   => $pk["status_proker"],
                                    ], JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'>
                                Edit
                            </button>
                            <button type="button" class="btn btn-danger-outline btn-sm"
                                    onclick="bukaPopupKonfirmasi({
                                        judul: 'Hapus Program Kerja?',
                                        deskripsi: 'Menghapus proker <?php echo e(addslashes($pk['nama_proker'])); ?> juga akan menghapus seluruh divisi, sesi kegiatan, dan kepanitiaan di dalamnya.',
                                        action: 'proses.php',
                                        fields: { action: 'hapus_proker', id_proker: <?php echo $idPk; ?> },
                                        teksTombol: 'Ya, Hapus Proker',
                                        berbahaya: true
                                    })">
                                Hapus
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php if ($isAdmin): ?>
<div class="popup-backdrop modal-backdrop" id="modal-tambah-proker">
    <div class="popup-dialog modal-dialog">
        <div class="popup-header modal-header">
            <div>
                <h3 class="popup-title modal-title">Tambah Program Kerja</h3>
                <p class="popup-subtitle modal-subtitle">Sistem otomatis membuat 4 divisi standar (Acara, Humas, Dokumentasi, Perlengkapan).</p>
            </div>
            <button type="button" class="popup-close-btn modal-close-btn" onclick="tutupPopup('modal-tambah-proker')">&times;</button>
        </div>

        <form method="post" action="proses.php" onsubmit="return validasiProker(this)">
            <?php echo csrfField(); ?>
            <input type="hidden" name="action" value="tambah_proker">

            <div class="form-row-2">
                <div class="form-group">
                    <label class="form-label">Nama Program Kerja *</label>
                    <input type="text" name="nama_proker" class="form-control w-full" placeholder="Contoh: Rapat Kerja 2026" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Periode Kepengurusan *</label>
                    <input type="text" name="periode" class="form-control w-full" value="2026/2027" required>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Deskripsi Singkat</label>
                <textarea name="deskripsi" class="form-control w-full" rows="2" placeholder="Tujuan dan cakupan program kerja..."></textarea>
            </div>

            <div class="form-row-2">
                <div class="form-group">
                    <label class="form-label">Tanggal Mulai *</label>
                    <input type="date" name="tanggal_mulai" id="tambah-mulai-proker" class="form-control w-full" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Tanggal Selesai *</label>
                    <input type="date" name="tanggal_selesai" id="tambah-selesai-proker" class="form-control w-full" required>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Status Proker</label>
                <select name="status_proker" class="form-select w-full">
                    <option value="Berjalan">Berjalan</option>
                    <option value="Perencanaan">Perencanaan</option>
                    <option value="Selesai">Selesai</option>
                </select>
            </div>

            <div class="popup-footer modal-footer">
                <button type="button" class="btn btn-outline" onclick="tutupPopup('modal-tambah-proker')">Batal</button>
                <button type="submit" class="btn btn-dark">Simpan Proker</button>
            </div>
        </form>
    </div>
</div>

<div class="popup-backdrop modal-backdrop" id="modal-edit-proker">
    <div class="popup-dialog modal-dialog">
        <div class="popup-header modal-header">
            <div>
                <h3 class="popup-title modal-title">Edit Program Kerja</h3>
                <p class="popup-subtitle modal-subtitle">Perbarui informasi dan status pelaksanaan program kerja.</p>
            </div>
            <button type="button" class="popup-close-btn modal-close-btn" onclick="tutupPopup('modal-edit-proker')">&times;</button>
        </div>

        <form method="post" action="proses.php" onsubmit="return validasiProker(this)">
            <?php echo csrfField(); ?>
            <input type="hidden" name="action" value="edit_proker">
            <input type="hidden" name="id_proker" id="edit-id-proker">

            <div class="form-row-2">
                <div class="form-group">
                    <label class="form-label">Nama Program Kerja *</label>
                    <input type="text" name="nama_proker" id="edit-nama-proker" class="form-control w-full" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Periode Kepengurusan *</label>
                    <input type="text" name="periode" id="edit-periode-proker" class="form-control w-full" required>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Deskripsi Singkat</label>
                <textarea name="deskripsi" id="edit-deskripsi-proker" class="form-control w-full" rows="2"></textarea>
            </div>

            <div class="form-row-2">
                <div class="form-group">
                    <label class="form-label">Tanggal Mulai *</label>
                    <input type="date" name="tanggal_mulai" id="edit-mulai-proker" class="form-control w-full" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Tanggal Selesai *</label>
                    <input type="date" name="tanggal_selesai" id="edit-selesai-proker" class="form-control w-full" required>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Status Proker</label>
                <select name="status_proker" id="edit-status-proker" class="form-select w-full">
                    <option value="Berjalan">Berjalan</option>
                    <option value="Perencanaan">Perencanaan</option>
                    <option value="Selesai">Selesai</option>
                </select>
            </div>

            <div class="popup-footer modal-footer">
                <button type="button" class="btn btn-outline" onclick="tutupPopup('modal-edit-proker')">Batal</button>
                <button type="submit" class="btn btn-dark">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<script>
function validasiProker(form) {
    const mulai = form.querySelector('input[name="tanggal_mulai"]');
    const selesai = form.querySelector('input[name="tanggal_selesai"]');
    if (mulai && selesai && mulai.value && selesai.value) {
        if (selesai.value < mulai.value) {
            alert('Tanggal selesai proker tidak boleh lebih awal dari tanggal mulai (' + mulai.value + ').');
            selesai.focus();
            return false;
        }
    }
    return true;
}

const tMulai = document.getElementById('tambah-mulai-proker');
const tSelesai = document.getElementById('tambah-selesai-proker');
if (tMulai && tSelesai) {
    tMulai.addEventListener('change', function() {
        tSelesai.min = this.value;
        if (tSelesai.value && tSelesai.value < this.value) {
            tSelesai.value = this.value;
        }
    });
}

const eMulai = document.getElementById('edit-mulai-proker');
const eSelesai = document.getElementById('edit-selesai-proker');
if (eMulai && eSelesai) {
    eMulai.addEventListener('change', function() {
        eSelesai.min = this.value;
        if (eSelesai.value && eSelesai.value < this.value) {
            eSelesai.value = this.value;
        }
    });
}

function bukaModalEditProker(data) {
    document.getElementById('edit-id-proker').value = data.id_proker;
    document.getElementById('edit-nama-proker').value = data.nama_proker;
    document.getElementById('edit-periode-proker').value = data.periode;
    document.getElementById('edit-deskripsi-proker').value = data.deskripsi;
    document.getElementById('edit-mulai-proker').value = data.tanggal_mulai;
    document.getElementById('edit-selesai-proker').value = data.tanggal_selesai;
    document.getElementById('edit-selesai-proker').min = data.tanggal_mulai || '';
    document.getElementById('edit-status-proker').value = data.status_proker;
    bukaPopup('modal-edit-proker');
}
</script>
<?php endif; ?>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
