<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../models/ProkerModel.php';
require_once __DIR__ . '/../../models/PresensiModel.php';

requireLogin();

$pageTitle         = 'Kelola Sesi Presensi';
$activeMenu        = 'kelola_presensi';
$currentUserId     = (int) ($_SESSION['user_id'] ?? 0);

$prokerModel   = new ProkerModel($pdo);
$presensiModel = new PresensiModel($pdo);

$sessionRole       = $_SESSION['role'] ?? 'anggota';
$isAdmin           = isAdminRole($sessionRole);
$countPresensiWait = $presensiModel->getAntreanCount($isAdmin ? 'admin' : 'koordinator', $currentUserId);

$daftarProkerUser = $isAdmin ? $prokerModel->getAll() : $prokerModel->getProkerByUser($currentUserId);
$daftarProkerTopbar = $daftarProkerUser;

$selectedProkerId = 0;
if (isset($_GET['proker_id'])) {
    $selectedProkerId = (int) $_GET['proker_id'];
} elseif (isset($_SESSION['active_proker_id'])) {
    $selectedProkerId = (int) $_SESSION['active_proker_id'];
} elseif (!empty($daftarProkerUser)) {
    $selectedProkerId = (int) $daftarProkerUser[0]['id_proker'];
}
$_SESSION['active_proker_id'] = $selectedProkerId;

$jabatanDiProker = $isAdmin ? 'Admin' : 'Anggota';
$idDivisiKoor    = 0;
$namaDivisiKoor  = $isAdmin ? 'Semua Divisi' : '';
if (!$isAdmin) {
    foreach ($daftarProkerUser as $pk) {
        if ((int) $pk['id_proker'] === $selectedProkerId) {
            $jabatanDiProker = $pk['jabatan'] ?? 'Anggota';
            $idDivisiKoor    = (int) ($pk['id_divisi'] ?? 0);
            $namaDivisiKoor  = $pk['nama_divisi'] ?? '';
            break;
        }
    }
}

if (!$isAdmin && $jabatanDiProker !== 'Koordinator') {
    setFlash('error', 'Halaman Kelola Presensi hanya dapat diakses oleh Koordinator Divisi pada proker ini atau Administrator.');
    header('Location: ../dashboard/index.php');
    exit;
}

$activeProker = $prokerModel->getById($selectedProkerId);

$semuaKegiatan = $prokerModel->getKegiatan($selectedProkerId);
$kegiatanDivisi = $isAdmin ? $semuaKegiatan : array_filter($semuaKegiatan, function ($k) use ($idDivisiKoor) {
    return empty($k['id_divisi']) || (int) $k['id_divisi'] === $idDivisiKoor;
});

include __DIR__ . '/../../includes/header.php';
?>

<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-oes-neutral-900 tracking-tight mb-1">Kelola Jadwal &amp; Sesi Presensi</h1>
        <p class="text-sm text-oes-neutral-500">
            Kelola sesi rapat dan kegiatan presensi untuk <strong><?php echo e($namaDivisiKoor ?: 'Divisi Anda'); ?></strong>.
        </p>
    </div>
    <button type="button" class="btn btn-primary" onclick="bukaPopup('modal-tambah-sesi')">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 4px;"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Tambah Sesi Kegiatan
    </button>
</div>

<div class="bg-white border border-oes-neutral-200 rounded-2xl shadow-xs overflow-hidden">
    <div class="w-full overflow-x-auto">
        <table class="w-full min-w-[960px] border-collapse text-left">
            <thead>
                <tr class="bg-oes-canvas-50 border-b border-oes-neutral-200 text-xs font-semibold text-oes-neutral-500 uppercase tracking-wider">
                    <th class="py-3.5 px-5">Nama Kegiatan</th>
                    <th class="py-3.5 px-5">Cakupan</th>
                    <th class="py-3.5 px-5">Jenis</th>
                    <th class="py-3.5 px-5">Tanggal &amp; Jam</th>
                    <th class="py-3.5 px-5">Pelaksanaan</th>
                    <th class="py-3.5 px-5">Tempat / Link</th>
                    <th class="py-3.5 px-5">Bobot Poin</th>
                    <th class="py-3.5 px-5">Status Detail</th>
                    <th class="py-3.5 px-5">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-oes-neutral-200 text-sm">
                <?php if (empty($kegiatanDivisi)): ?>
                    <tr>
                        <td colspan="9" class="py-10 px-5 text-center text-oes-neutral-500">
                            Belum ada kegiatan yang dibuat oleh Admin untuk proker/divisi ini.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($kegiatanDivisi as $k): ?>
                        <?php $belumLengkap = ($k['status_detail'] === 'Belum Dilengkapi'); ?>
                        <tr class="hover:bg-oes-canvas-50 transition-colors">
                            <td class="py-4 px-5 font-semibold text-oes-neutral-900"><?php echo e($k['nama_kegiatan']); ?></td>
                            <td class="py-4 px-5 text-oes-neutral-700"><?php echo !empty($k['nama_divisi']) ? e($k['nama_divisi']) : 'Semua Divisi'; ?></td>
                            <td class="py-4 px-5"><?php echo renderBadge($k['jenis_kegiatan']); ?></td>
                            <td class="py-4 px-5">
                                <?php if ($belumLengkap): ?>
                                    <span class="text-oes-neutral-500">Belum diatur</span>
                                <?php else: ?>
                                    <div class="font-medium text-oes-neutral-900"><?php echo formatTanggal($k['tanggal']); ?></div>
                                    <div class="text-xs text-oes-neutral-500"><?php echo formatJam($k['jam_mulai'], $k['jam_selesai']); ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="py-4 px-5">
                                <?php if ($belumLengkap): ?>
                                    <span class="text-oes-neutral-500">-</span>
                                <?php else: ?>
                                    <?php echo renderBadge($k['pelaksanaan'] ?? $k['mode_kegiatan'] ?? 'Offline'); ?>
                                <?php endif; ?>
                            </td>
                            <td class="py-4 px-5 max-w-[200px] whitespace-normal text-xs text-oes-neutral-700">
                                <?php if ($belumLengkap): ?>
                                    <span class="text-oes-neutral-500">-</span>
                                <?php else: ?>
                                    <?php echo e($k['tempat_atau_link'] ?? '-'); ?>
                                <?php endif; ?>
                            </td>
                            <td class="py-4 px-5 font-bold text-oes-primary-800">+<?php echo (int) $k['bobot_poin']; ?> poin</td>
                            <td class="py-4 px-5">
                                <?php if ($belumLengkap): ?>
                                    <span class="oes-badge badge-warning">Belum Dilengkapi</span>
                                <?php else: ?>
                                    <span class="oes-badge badge-success">Siap Presensi</span>
                                <?php endif; ?>
                            </td>
                            <td class="py-4 px-5">
                                <div class="inline-flex items-center gap-2">
                                    <button type="button" class="btn <?php echo $belumLengkap ? 'btn-primary' : 'btn-outline'; ?> btn-sm"
                                            onclick='bukaModalAturJadwal(<?php echo json_encode([
                                                "id_kegiatan"      => (int) $k["id_kegiatan"],
                                                "nama_kegiatan"    => $k["nama_kegiatan"],
                                                "tanggal"          => $k["tanggal"] ?? date("Y-m-d"),
                                                "jam_mulai"        => !empty($k["jam_mulai"]) ? substr($k["jam_mulai"], 0, 5) : "15:30",
                                                "jam_selesai"      => !empty($k["jam_selesai"]) ? substr($k["jam_selesai"], 0, 5) : "17:00",
                                                "pelaksanaan"      => $k["pelaksanaan"] ?? $k["mode_kegiatan"] ?? "Offline",
                                                "tempat_atau_link" => $k["tempat_atau_link"] ?? "",
                                            ], JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'>
                                        <?php echo $belumLengkap ? 'Lengkapi' : 'Ubah'; ?>
                                    </button>
                                    <button type="button" class="btn btn-danger-outline btn-sm"
                                            onclick="konfirmasiHapusKegiatan(<?php echo (int) $k['id_kegiatan']; ?>, <?php echo htmlspecialchars(json_encode($k['nama_kegiatan']), ENT_QUOTES, 'UTF-8'); ?>)">
                                        Hapus
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="popup-backdrop modal-backdrop" id="modal-tambah-sesi">
    <div class="popup-dialog modal-dialog" style="max-width: 480px;">
        <div class="popup-header modal-header">
            <div>
                <h3 class="popup-title modal-title">Tambah Sesi Kegiatan</h3>
                <p class="popup-subtitle modal-subtitle">Buat rapat atau kegiatan baru untuk <?php echo e($namaDivisiKoor ?: 'Divisi Anda'); ?></p>
            </div>
            <button type="button" class="popup-close-btn modal-close-btn" onclick="tutupPopup('modal-tambah-sesi')">&times;</button>
        </div>

        <form method="post" action="proses.php" onsubmit="return validasiFormSesi(this)">
            <?php echo csrfField(); ?>
            <input type="hidden" name="action" value="tambah_kegiatan">
            <input type="hidden" name="proker_id" value="<?php echo (int) $selectedProkerId; ?>">

            <div class="form-group" style="margin-bottom: 14px;">
                <label class="form-label">Nama Kegiatan *</label>
                <input type="text" name="nama_kegiatan" class="form-control w-full" placeholder="Contoh: Rapat Internal Divisi 1" required>
            </div>

            <div class="form-row-2" style="margin-bottom: 14px;">
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">Jenis Kegiatan *</label>
                    <select name="jenis_kegiatan" class="form-select w-full" required>
                        <option value="Rapat">Rapat</option>
                        <option value="Hari-H">Hari-H</option>
                    </select>
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">Bobot Poin *</label>
                    <input type="number" name="bobot_poin" class="form-control w-full" value="20" min="1" max="500" required>
                </div>
            </div>

            <div class="form-group" style="margin-bottom: 14px;">
                <label class="form-label">Tanggal Pelaksanaan *</label>
                <input type="date" name="tanggal" class="form-control w-full"
                       value="<?php echo !empty($activeProker['tanggal_mulai']) && date('Y-m-d') < $activeProker['tanggal_mulai'] ? $activeProker['tanggal_mulai'] : (!empty($activeProker['tanggal_selesai']) && date('Y-m-d') > $activeProker['tanggal_selesai'] ? $activeProker['tanggal_selesai'] : date('Y-m-d')); ?>"
                       min="<?php echo htmlspecialchars($activeProker['tanggal_mulai'] ?? ''); ?>"
                       max="<?php echo htmlspecialchars($activeProker['tanggal_selesai'] ?? ''); ?>" required>
                <?php if (!empty($activeProker['tanggal_mulai']) && !empty($activeProker['tanggal_selesai'])): ?>
                    <p class="text-xs text-oes-neutral-500 mt-1">
                        Batas tanggal kegiatan: <strong><?php echo formatTanggal($activeProker['tanggal_mulai']); ?></strong> s/d <strong><?php echo formatTanggal($activeProker['tanggal_selesai']); ?></strong> (sesuai periode proker)
                    </p>
                <?php endif; ?>
            </div>

            <div class="form-row-2" style="margin-bottom: 14px;">
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">Jam Mulai</label>
                    <input type="time" name="jam_mulai" class="form-control w-full" value="15:30" required>
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">Jam Selesai</label>
                    <input type="time" name="jam_selesai" class="form-control w-full" value="17:00" required>
                </div>
            </div>

            <div class="form-group" style="margin-bottom: 14px;">
                <label class="form-label">Pelaksanaan</label>
                <select name="pelaksanaan" class="form-select w-full" required>
                    <option value="Offline">Offline (Tatap Muka)</option>
                    <option value="Online">Online (Zoom / Google Meet)</option>
                </select>
            </div>

            <div class="form-group" style="margin-bottom: 14px;">
                <label class="form-label">Tempat Ruangan / Tautan Meeting</label>
                <input type="text" name="tempat_atau_link" class="form-control w-full" placeholder="Contoh: Ruang Rapat Lt. 6 Gedung Sipil / Link Zoom" required>
            </div>

            <div class="popup-footer modal-footer" style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                <button type="button" class="btn btn-outline" onclick="tutupPopup('modal-tambah-sesi')">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan Sesi</button>
            </div>
        </form>
    </div>
</div>

<div class="popup-backdrop modal-backdrop" id="modal-atur-jadwal">
    <div class="popup-dialog modal-dialog">
        <div class="popup-header modal-header">
            <div>
                <h3 class="popup-title modal-title">Atur Jadwal &amp; Tempat Kegiatan</h3>
                <p class="popup-subtitle modal-subtitle" id="jadwal-subjudul"></p>
            </div>
            <button type="button" class="popup-close-btn modal-close-btn" onclick="tutupPopup('modal-atur-jadwal')">&times;</button>
        </div>

        <form method="post" action="proses.php" onsubmit="return validasiFormSesi(this)">
            <?php echo csrfField(); ?>
            <input type="hidden" name="action" value="update_jadwal_kegiatan">
            <input type="hidden" name="id_kegiatan" id="jadwal-id-kegiatan">
            <input type="hidden" name="proker_id" value="<?php echo (int) $selectedProkerId; ?>">

            <div class="form-group">
                <label class="form-label">Tanggal Pelaksanaan *</label>
                <input type="date" name="tanggal" id="jadwal-tanggal" class="form-control w-full"
                       min="<?php echo htmlspecialchars($activeProker['tanggal_mulai'] ?? ''); ?>"
                       max="<?php echo htmlspecialchars($activeProker['tanggal_selesai'] ?? ''); ?>" required>
                <?php if (!empty($activeProker['tanggal_mulai']) && !empty($activeProker['tanggal_selesai'])): ?>
                    <p class="text-xs text-oes-neutral-500 mt-1">
                        Batas tanggal kegiatan: <strong><?php echo formatTanggal($activeProker['tanggal_mulai']); ?></strong> s/d <strong><?php echo formatTanggal($activeProker['tanggal_selesai']); ?></strong> (sesuai periode proker)
                    </p>
                <?php endif; ?>
            </div>

            <div class="form-row-2">
                <div class="form-group">
                    <label class="form-label">Jam Mulai</label>
                    <input type="time" name="jam_mulai" id="jadwal-jam-mulai" class="form-control w-full" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Jam Selesai</label>
                    <input type="time" name="jam_selesai" id="jadwal-jam-selesai" class="form-control w-full" required>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Pelaksanaan</label>
                <select name="pelaksanaan" id="jadwal-mode" class="form-select w-full" required>
                    <option value="Offline">Offline (Tatap Muka)</option>
                    <option value="Online">Online (Zoom / Google Meet)</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Tempat Ruangan / Tautan Meeting</label>
                <input type="text" name="tempat_atau_link" id="jadwal-tempat" class="form-control w-full" placeholder="Contoh: Ruang Rapat Lt. 6 Gedung Sipil / Link Zoom" required>
            </div>

            <div class="popup-footer modal-footer">
                <button type="button" class="btn btn-outline" onclick="tutupPopup('modal-atur-jadwal')">Batal</button>
                <button type="submit" class="btn btn-dark">Simpan Jadwal</button>
            </div>
        </form>
    </div>
</div>

<form method="post" action="proses.php" id="form-hapus-kegiatan" style="display:none;">
    <?php echo csrfField(); ?>
    <input type="hidden" name="action" value="hapus_kegiatan">
    <input type="hidden" name="proker_id" value="<?php echo (int) $selectedProkerId; ?>">
    <input type="hidden" name="id_kegiatan" id="hapus-keg-id" value="">
</form>

<script>
function validasiFormSesi(form) {
    const inputTgl = form.querySelector('input[name="tanggal"]');
    if (inputTgl && inputTgl.value) {
        const val = inputTgl.value;
        const min = inputTgl.min;
        const max = inputTgl.max;
        if (min && val < min) {
            alert('Tanggal pelaksanaan kegiatan tidak boleh sebelum batas tanggal mulai program kerja (' + min + ').');
            inputTgl.focus();
            return false;
        }
        if (max && val > max) {
            alert('Tanggal pelaksanaan kegiatan tidak boleh melebihi batas tanggal selesai program kerja (' + max + ').');
            inputTgl.focus();
            return false;
        }
    }
    return true;
}

function konfirmasiHapusKegiatan(idKegiatan, namaKegiatan) {
    bukaPopupKonfirmasi({
        judul: 'Hapus Sesi Kegiatan?',
        deskripsi: 'Apakah kamu yakin ingin menghapus sesi kegiatan "' + namaKegiatan + '"? Presensi terkait kegiatan ini juga akan dihapus.',
        teksTombol: 'Ya, Hapus Sesi',
        berbahaya: true,
        action: '#',
        onConfirm: function() {
            document.getElementById('hapus-keg-id').value = idKegiatan;
            document.getElementById('form-hapus-kegiatan').submit();
        }
    });
}

function bukaModalAturJadwal(data) {
    document.getElementById('jadwal-id-kegiatan').value = data.id_kegiatan;
    document.getElementById('jadwal-subjudul').textContent = data.nama_kegiatan;
    document.getElementById('jadwal-tanggal').value = data.tanggal;
    document.getElementById('jadwal-jam-mulai').value = data.jam_mulai;
    document.getElementById('jadwal-jam-selesai').value = data.jam_selesai;
    document.getElementById('jadwal-mode').value = data.pelaksanaan || data.mode_kegiatan || 'Offline';
    document.getElementById('jadwal-tempat').value = data.tempat_atau_link;
    bukaPopup('modal-atur-jadwal');
}
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
