<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../models/ProkerModel.php';
require_once __DIR__ . '/../../models/PresensiModel.php';

requireLogin();

$pageTitle     = 'Validasi Presensi';
$activeMenu    = 'validasi';
$currentUserId = (int) ($_SESSION['user_id'] ?? 0);
$sessionRole   = $_SESSION['role'] ?? 'anggota';
$isAdmin       = isAdminRole($sessionRole);

$prokerModel   = new ProkerModel($pdo);
$presensiModel = new PresensiModel($pdo);

$antreanPresensi = [];
$subJudulHalaman = '';

if ($isAdmin) {
    $prokerFilter = (int) ($_GET['proker_id'] ?? 0);
    $semuaProker  = $prokerModel->getAll();
    $antreanPresensi = $presensiModel->getAntreanAdmin($prokerFilter);
    $countPresensiWait = $presensiModel->getAntreanCount($sessionRole, $currentUserId);
    $subJudulHalaman = 'Validasi bukti selfie kehadiran para Koordinator Divisi di setiap program kerja.';
} else {
    $daftarProkerUser = $prokerModel->getProkerByUser($currentUserId);
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

    $jabatanDiProker = 'Anggota';
    $idDivisiKoor    = 0;
    $namaDivisiKoor  = '';
    foreach ($daftarProkerUser as $pk) {
        if ((int) $pk['id_proker'] === $selectedProkerId) {
            $jabatanDiProker = $pk['jabatan'] ?? 'Anggota';
            $idDivisiKoor    = (int) ($pk['id_divisi'] ?? 0);
            $namaDivisiKoor  = $pk['nama_divisi'] ?? '';
            break;
        }
    }

    if ($jabatanDiProker !== 'Koordinator') {
        setFlash('error', 'Halaman Validasi Presensi hanya dapat diakses oleh Koordinator Divisi atau Admin.');
        header('Location: ../dashboard/index.php');
        exit;
    }

    $activeProker = $prokerModel->getById($selectedProkerId);
    $antreanPresensi = $presensiModel->getAntreanKoordinator($selectedProkerId, $idDivisiKoor);
    $countPresensiWait = $presensiModel->getAntreanCount('koordinator', $currentUserId);
    $subJudulHalaman = 'Periksa bukti selfie kehadiran anggota pada ' . ($namaDivisiKoor ?: 'Divisi Anda') . '. Saat disetujui, poin kehadiran akan langsung ditambahkan.';
}

include __DIR__ . '/../../includes/header.php';
?>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-oes-neutral-900 tracking-tight mb-1">Validasi Presensi Kehadiran</h1>
    <p class="text-sm text-oes-neutral-500"><?php echo e($subJudulHalaman); ?></p>
</div>

<?php if ($isAdmin && !empty($semuaProker)): ?>
    <form method="get" action="" class="flex flex-wrap items-center gap-3 mb-5">
        <select name="proker_id" class="form-select" onchange="this.form.submit()">
            <option value="0">Semua Program Kerja</option>
            <?php foreach ($semuaProker as $sp): ?>
                <option value="<?php echo (int) $sp['id_proker']; ?>" <?php echo ((int) ($prokerFilter ?? 0) === (int) $sp['id_proker']) ? 'selected' : ''; ?>>
                    <?php echo e($sp['nama_proker']); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </form>
<?php endif; ?>

<div class="bg-white border border-oes-neutral-200 rounded-2xl shadow-xs overflow-hidden">
    <div class="w-full overflow-x-auto">
        <table class="w-full min-w-[980px] border-collapse text-left">
            <thead>
                <tr class="bg-oes-canvas-50 border-b border-oes-neutral-200 text-xs font-semibold text-oes-neutral-500 uppercase tracking-wider">
                    <th class="py-3.5 px-5">Nama &amp; NIM</th>
                    <?php if ($isAdmin): ?>
                        <th class="py-3.5 px-5">Proker &amp; Divisi</th>
                    <?php endif; ?>
                    <th class="py-3.5 px-5">Kegiatan</th>
                    <th class="py-3.5 px-5">Waktu Presensi</th>
                    <th class="py-3.5 px-5">Poin</th>
                    <th class="py-3.5 px-5">Status</th>
                    <th class="py-3.5 px-5">Aksi Validasi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-oes-neutral-200 text-sm">
                <?php if (empty($antreanPresensi)): ?>
                    <tr>
                        <td colspan="<?php echo $isAdmin ? 7 : 6; ?>" class="py-10 px-5 text-center text-oes-neutral-500">
                            Belum ada data presensi dalam antrean validasi.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($antreanPresensi as $pr): ?>
                        <?php
                        $namaPeserta = $pr['nama_anggota'] ?? ($pr['nama_koordinator'] ?? '-');
                        ?>
                        <tr class="hover:bg-oes-canvas-50 transition-colors">
                            <td class="py-4 px-5">
                                <div class="font-semibold text-oes-neutral-900"><?php echo e($namaPeserta); ?></div>
                                <div class="text-xs text-oes-neutral-500">NIM: <?php echo e($pr['nim']); ?></div>
                            </td>
                            <?php if ($isAdmin): ?>
                                <td class="py-4 px-5">
                                    <div class="font-medium text-oes-neutral-900"><?php echo e($pr['nama_proker'] ?? '-'); ?></div>
                                    <div class="text-xs text-oes-neutral-500"><?php echo e($pr['nama_divisi'] ?? '-'); ?></div>
                                </td>
                            <?php endif; ?>
                            <td class="py-4 px-5">
                                <div class="font-medium text-oes-neutral-900"><?php echo e($pr['nama_kegiatan']); ?></div>
                                <div class="text-xs text-oes-neutral-500"><?php echo formatTanggal($pr['tanggal']); ?></div>
                            </td>
                            <td class="py-4 px-5 text-oes-neutral-700">
                                <?php echo formatTanggal($pr['waktu_presensi']); ?> • <?php echo formatJam($pr['waktu_presensi']); ?>
                            </td>
                            <td class="py-4 px-5 font-bold text-oes-primary-800">+<?php echo (int) $pr['bobot_poin']; ?> poin</td>
                            <td class="py-4 px-5"><?php echo renderBadge($pr['status_validasi']); ?></td>
                            <td class="py-4 px-5">
                                <button type="button" class="btn <?php echo $pr['status_validasi'] === 'Menunggu' ? 'btn-primary' : 'btn-outline'; ?> btn-sm"
                                        onclick='bukaModalPeriksaPresensi(<?php echo json_encode([
                                            "id_presensi"       => (int) $pr["id_presensi"],
                                            "nama"              => $namaPeserta,
                                            "nim"               => $pr["nim"],
                                            "kegiatan"          => $pr["nama_kegiatan"],
                                            "waktu"             => formatTanggal($pr["waktu_presensi"], true) . " • " . formatJam($pr["waktu_presensi"]),
                                            "poin"              => (int) $pr["bobot_poin"],
                                            "status"            => $pr["status_validasi"],
                                            "catatan_penolakan" => $pr["catatan_penolakan"] ?? "",
                                            "foto"              => $basePath . "uploads/presensi/" . $pr["foto_selfie"],
                                        ], JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'>
                                    <?php echo $pr['status_validasi'] === 'Menunggu' ? 'Periksa & Validasi' : 'Tinjau Ulang'; ?>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="popup-backdrop modal-backdrop" id="modal-validasi-presensi">
    <div class="popup-dialog modal-dialog" style="max-width: 500px;">
        <div class="popup-header modal-header">
            <div>
                <h3 class="popup-title modal-title" id="val-nama">Validasi Bukti Selfie</h3>
                <p class="popup-subtitle modal-subtitle" id="val-kegiatan"></p>
            </div>
            <button type="button" class="popup-close-btn modal-close-btn" onclick="tutupPopup('modal-validasi-presensi')">&times;</button>
        </div>

        <div class="w-full rounded-xl overflow-hidden bg-slate-900 mb-3.5">
            <img id="val-foto" src="" alt="Bukti Selfie" class="w-full max-h-[300px] object-cover block">
        </div>

        <div class="text-xs text-oes-neutral-500 mb-3.5" id="val-waktu"></div>

        <form method="post" action="proses.php">
            <?php echo csrfField(); ?>
            <input type="hidden" name="action" value="validasi_presensi">
            <input type="hidden" name="id_presensi" id="val-id-presensi">
            <input type="hidden" name="proker_id" value="<?php echo (int) ($selectedProkerId ?? 0); ?>">

            <div class="form-group">
                <label class="form-label">Catatan Penolakan (Wajib diisi jika Ditolak)</label>
                <textarea name="catatan_penolakan" id="val-catatan" class="form-control w-full" rows="2" placeholder="Contoh: Foto buram / tidak berada di lokasi rapat..."></textarea>
            </div>

            <div class="popup-footer modal-footer">
                <button type="submit" name="keputusan" value="Ditolak" class="btn btn-danger-outline">
                    Tolak Presensi
                </button>
                <button type="submit" name="keputusan" value="Disetujui" class="btn btn-dark" id="val-btn-sah">
                    Setujui
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function bukaModalPeriksaPresensi(data) {
    document.getElementById('val-id-presensi').value = data.id_presensi;
    document.getElementById('val-nama').textContent = data.nama + ' (' + data.nim + ')';
    document.getElementById('val-kegiatan').textContent = data.kegiatan + ' • +' + data.poin + ' poin';
    document.getElementById('val-waktu').textContent = 'Waktu Presensi: ' + data.waktu;
    document.getElementById('val-foto').src = data.foto;
    document.getElementById('val-catatan').value = data.catatan_penolakan || '';
    document.getElementById('val-btn-sah').textContent = 'Setujui (+' + data.poin + ' Poin)';
    bukaPopup('modal-validasi-presensi');
}
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
