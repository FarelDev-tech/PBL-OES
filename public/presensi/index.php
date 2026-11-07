<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../models/ProkerModel.php';
require_once __DIR__ . '/../../models/PresensiModel.php';

requireLogin();

$pageTitle     = 'Presensi Saya';
$activeMenu    = 'presensi_saya';
$currentUserId = (int) ($_SESSION['user_id'] ?? 0);

$prokerModel   = new ProkerModel($pdo);
$presensiModel = new PresensiModel($pdo);

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

$jenisFilter  = $_GET['jenis'] ?? '';
$statusFilter = $_GET['status'] ?? '';

$daftarKegiatan = $presensiModel->getKegiatanUser($currentUserId, $selectedProkerId, $jenisFilter, $statusFilter);

include __DIR__ . '/../../includes/header.php';
?>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-oes-neutral-900 tracking-tight mb-1">Presensi Saya</h1>
    <p class="text-sm text-oes-neutral-500">Catat kehadiran rapat dan kegiatan Hari-H dengan selfie kamera langsung dari perangkatmu.</p>
</div>

<form method="get" action="" class="flex flex-wrap items-center gap-3 mb-5">
    <?php if (count($daftarProkerUser) > 1): ?>
        <select name="proker_id" class="form-select" onchange="this.form.submit()">
            <?php foreach ($daftarProkerUser as $pku): ?>
                <option value="<?php echo (int) $pku['id_proker']; ?>" <?php echo ((int)$selectedProkerId === (int)$pku['id_proker']) ? 'selected' : ''; ?>>
                    Proker: <?php echo e($pku['nama_proker']); ?>
                </option>
            <?php endforeach; ?>
        </select>
    <?php else: ?>
        <input type="hidden" name="proker_id" value="<?php echo (int) $selectedProkerId; ?>">
    <?php endif; ?>

    <select name="jenis" class="form-select" onchange="this.form.submit()">
        <option value="">Semua Jenis Kegiatan</option>
        <option value="Rapat" <?php echo $jenisFilter === 'Rapat' ? 'selected' : ''; ?>>Rapat</option>
        <option value="Hari-H" <?php echo $jenisFilter === 'Hari-H' ? 'selected' : ''; ?>>Hari-H</option>
    </select>

    <select name="status" class="form-select" onchange="this.form.submit()">
        <option value="">Semua Status Presensi</option>
        <option value="Belum Presensi" <?php echo $statusFilter === 'Belum Presensi' ? 'selected' : ''; ?>>Belum Presensi</option>
        <option value="Menunggu" <?php echo $statusFilter === 'Menunggu' ? 'selected' : ''; ?>>Menunggu Validasi</option>
        <option value="Disetujui" <?php echo $statusFilter === 'Disetujui' ? 'selected' : ''; ?>>Disetujui</option>
        <option value="Ditolak" <?php echo $statusFilter === 'Ditolak' ? 'selected' : ''; ?>>Ditolak</option>
    </select>
</form>

<?php if (empty($daftarProkerUser)): ?>
    <div class="empty-state">
        <div class="empty-state-icon">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
        </div>
        <div class="empty-state-title">Belum Terdaftar di Kepanitiaan Proker</div>
        <div class="empty-state-desc">Anda belum dimasukkan ke dalam kepanitiaan program kerja yang sedang berjalan.</div>
    </div>
<?php elseif (empty($daftarKegiatan)): ?>
    <div class="empty-state">
        <div class="empty-state-icon">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
        </div>
        <div class="empty-state-title">Tidak Ada Sesi Presensi</div>
        <div class="empty-state-desc">Belum ada sesi presensi yang sesuai dengan filter pilihan Anda.</div>
    </div>
<?php else: ?>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
        <?php foreach ($daftarKegiatan as $k): ?>
            <?php
            $statusKartu = $k['status_kartu'];
            $belumDilengkapi = ($k['status_detail'] === 'Belum Dilengkapi');
            $bisaPresensi = (!$belumDilengkapi && $k['status_sesi'] === 'Dibuka' && in_array($statusKartu, ['Belum Presensi', 'Ditolak'], true));
            ?>
            <div class="bg-white border border-oes-neutral-200 rounded-2xl p-6 shadow-xs flex flex-col justify-between gap-4">
                <div>
                    <div class="flex items-start justify-between gap-3 mb-2">
                        <div>
                            <div class="text-base font-bold text-oes-neutral-900 mb-0.5"><?php echo e($k['nama_kegiatan']); ?></div>
                            <div class="text-xs text-oes-neutral-500 flex items-center gap-1.5 flex-wrap">
                                <span><?php echo e($k['nama_proker']); ?></span>
                                <span>•</span>
                                <span><?php echo !empty($k['nama_divisi_kegiatan']) ? e($k['nama_divisi_kegiatan']) : 'Semua Divisi'; ?></span>
                                <span>•</span>
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold <?php echo ($k['jabatan_di_proker'] === 'Koordinator') ? 'bg-oes-primary-950 text-white' : 'bg-oes-primary-100 text-oes-primary-800'; ?>">
                                    <?php echo e($k['jabatan_di_proker'] ?: 'Anggota'); ?>
                                </span>
                            </div>
                        </div>
                        <div class="flex items-center gap-1.5 flex-wrap justify-end">
                            <?php echo renderBadge($k['jenis_kegiatan']); ?>
                            <?php echo renderBadge($statusKartu); ?>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3 mt-3.5">
                        <div class="border border-oes-neutral-200 rounded-xl p-3 bg-oes-canvas-50">
                            <div class="text-[11px] font-medium text-oes-neutral-500 mb-0.5">Jadwal &amp; Jam</div>
                            <?php if ($belumDilengkapi): ?>
                                <div class="text-[13px] font-semibold text-amber-700">Belum Dilengkapi</div>
                                <div class="text-xs text-oes-neutral-500">Menunggu Koordinator</div>
                            <?php else: ?>
                                <div class="text-[13px] font-semibold text-oes-neutral-900"><?php echo formatTanggal($k['tanggal'], true); ?></div>
                                <div class="text-xs text-oes-neutral-700"><?php echo formatJam($k['jam_mulai'], $k['jam_selesai']); ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="border border-oes-neutral-200 rounded-xl p-3 bg-oes-canvas-50">
                            <div class="text-[11px] font-medium text-oes-neutral-500 mb-0.5">Lokasi / Pelaksanaan &amp; Poin</div>
                            <?php if ($belumDilengkapi): ?>
                                <div class="text-[13px] font-semibold text-oes-neutral-900">-</div>
                            <?php else: ?>
                                <div class="text-[13px] font-semibold text-oes-neutral-900"><?php echo e($k['pelaksanaan'] ?? $k['mode_kegiatan'] ?? 'Offline'); ?> — <?php echo e($k['tempat_atau_link'] ?? '-'); ?></div>
                            <?php endif; ?>
                            <div class="text-xs font-bold text-oes-primary-800 mt-0.5">+<?php echo (int) $k['bobot_poin']; ?> poin kehadiran</div>
                        </div>
                    </div>

                    <?php if ($statusKartu === 'Ditolak' && !empty($k['catatan_penolakan'])): ?>
                        <div class="alert-box-danger mt-3">
                            <strong>Alasan Ditolak:</strong> <?php echo e($k['catatan_penolakan']); ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div>
                    <?php if ($belumDilengkapi): ?>
                        <button type="button" class="btn btn-disabled btn-block" disabled>Jadwal Belum Diatur Koordinator</button>
                    <?php elseif ($statusKartu === 'Disetujui' || $statusKartu === 'Sah'): ?>
                        <div class="flex justify-between items-center pt-3 border-t border-oes-neutral-200">
                            <span class="text-xs font-semibold text-emerald-700">
                                &#10003; Presensi Disetujui (+<?php echo (int) $k['bobot_poin']; ?> poin)
                            </span>
                            <button type="button" class="btn btn-outline btn-sm"
                                    onclick='lihatBuktiSelfie(<?php echo json_encode([
                                        "judul" => $k["nama_kegiatan"],
                                        "waktu" => formatTanggal($k["waktu_presensi"], true) . " • " . formatJam($k["waktu_presensi"]),
                                        "foto"  => $basePath . "uploads/presensi/" . $k["foto_selfie"],
                                    ], JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'>
                                Lihat Bukti Selfie
                            </button>
                        </div>
                    <?php elseif ($statusKartu === 'Menunggu'): ?>
                        <div class="flex justify-between items-center pt-3 border-t border-oes-neutral-200">
                            <span class="text-xs font-semibold text-amber-700">
                                Menunggu Validasi <?php echo ($k['jabatan_di_proker'] === 'Koordinator') ? 'Admin' : 'Koordinator'; ?>
                            </span>
                            <button type="button" class="btn btn-outline btn-sm"
                                    onclick='lihatBuktiSelfie(<?php echo json_encode([
                                        "judul" => $k["nama_kegiatan"],
                                        "waktu" => formatTanggal($k["waktu_presensi"], true) . " • " . formatJam($k["waktu_presensi"]),
                                        "foto"  => $basePath . "uploads/presensi/" . $k["foto_selfie"],
                                    ], JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'>
                                Lihat Selfie
                            </button>
                        </div>
                    <?php elseif ($bisaPresensi): ?>
                        <button type="button" class="w-full inline-flex items-center justify-center gap-2 py-2.5 px-4 rounded-lg bg-oes-primary-950 hover:bg-oes-primary-900 text-white text-sm font-medium transition-colors cursor-pointer"
                                onclick='bukaModalKameraPresensi(<?php echo json_encode([
                                    "id_kegiatan"   => (int) $k["id_kegiatan"],
                                    "nama_kegiatan" => $k["nama_kegiatan"],
                                    "nama_proker"   => $k["nama_proker"],
                                    "bobot_poin"    => (int) $k["bobot_poin"],
                                    "ulang"         => ($statusKartu === "Ditolak"),
                                ], JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'>
                            <?php echo $statusKartu === 'Ditolak' ? 'Ambil Ulang Selfie Presensi' : 'Ambil Selfie Presensi Sekarang'; ?>
                        </button>
                    <?php else: ?>
                        <button type="button" class="btn btn-disabled btn-block" disabled>Sesi Presensi Ditutup</button>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<div class="popup-backdrop modal-backdrop" id="modal-kamera-presensi">
    <div class="popup-dialog modal-dialog" style="max-width: 520px;">
        <div class="popup-header modal-header">
            <div>
                <h3 class="popup-title modal-title" id="kamera-judul">Ambil Selfie Presensi</h3>
                <p class="popup-subtitle modal-subtitle" id="kamera-subjudul">Pastikan wajah terlihat jelas di dalam bingkai kamera.</p>
            </div>
            <button type="button" class="popup-close-btn modal-close-btn" onclick="tutupModalKamera()">&times;</button>
        </div>

        <form method="post" action="proses.php" id="form-submit-selfie">
            <?php echo csrfField(); ?>
            <input type="hidden" name="action" value="submit_selfie">
            <input type="hidden" name="id_kegiatan" id="input-id-kegiatan">
            <input type="hidden" name="proker_id" value="<?php echo (int) $selectedProkerId; ?>">
            <input type="hidden" name="foto_base64" id="input-foto-base64">

            <div class="relative w-full h-[300px] bg-slate-900 rounded-xl overflow-hidden flex items-center justify-center mb-3.5">
                <video id="video-kamera" autoplay playsinline class="w-full h-full object-cover hidden -scale-x-100"></video>
                <canvas id="canvas-selfie" class="hidden"></canvas>
                <img id="preview-selfie" src="" alt="Preview Selfie" class="w-full h-full object-cover hidden">

                <div id="state-kamera-info" class="text-slate-300 text-center p-5 text-[13px]">
                    Mengaktifkan kamera perangkat...
                </div>
            </div>

            <div class="flex gap-2.5 justify-center mb-2">
                <button type="button" class="btn btn-primary" id="btn-jepret" onclick="jepretFotoSelfie()" style="display:none;">
                    Ambil Foto Selfie
                </button>
                <button type="button" class="btn btn-outline" id="btn-ulangi-foto" onclick="ulangiFotoSelfie()" style="display:none;">
                    Foto Ulang
                </button>
            </div>

            <div class="popup-footer modal-footer">
                <button type="button" class="btn btn-outline" onclick="tutupModalKamera()">Batal</button>
                <button type="submit" class="btn btn-dark" id="btn-kirim-presensi" disabled>Kirim Presensi</button>
            </div>
        </form>
    </div>
</div>

<div class="popup-backdrop modal-backdrop" id="modal-lihat-selfie">
    <div class="popup-dialog modal-dialog" style="max-width: 460px;">
        <div class="popup-header modal-header">
            <div>
                <h3 class="popup-title modal-title" id="bukti-judul">Bukti Selfie Presensi</h3>
                <p class="popup-subtitle modal-subtitle" id="bukti-waktu"></p>
            </div>
            <button type="button" class="popup-close-btn modal-close-btn" onclick="tutupPopup('modal-lihat-selfie')">&times;</button>
        </div>

        <div class="w-full rounded-xl overflow-hidden bg-slate-900">
            <img id="bukti-img" src="" alt="Foto Selfie" class="w-full max-h-[340px] object-cover block">
        </div>

        <div class="mt-4 text-right">
            <button type="button" class="btn btn-outline" onclick="tutupPopup('modal-lihat-selfie')">Tutup</button>
        </div>
    </div>
</div>

<script>
let streamKameraAktif = null;

function bukaModalKameraPresensi(data) {
    document.getElementById('input-id-kegiatan').value = data.id_kegiatan;
    document.getElementById('input-foto-base64').value = '';
    document.getElementById('kamera-judul').textContent = data.nama_kegiatan;
    document.getElementById('kamera-subjudul').textContent = data.nama_proker + ' • Bobot +' + data.bobot_poin + ' poin';
    document.getElementById('btn-kirim-presensi').disabled = true;

    bukaPopup('modal-kamera-presensi');
    mulaiStreamKamera();
}

function mulaiStreamKamera() {
    const video = document.getElementById('video-kamera');
    const preview = document.getElementById('preview-selfie');
    const info = document.getElementById('state-kamera-info');
    const btnJepret = document.getElementById('btn-jepret');
    const btnUlang = document.getElementById('btn-ulangi-foto');

    preview.style.display = 'none';
    video.style.display = 'none';
    btnJepret.style.display = 'none';
    btnUlang.style.display = 'none';
    info.style.display = 'block';
    info.textContent = 'Meminta izin akses kamera...';

    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        info.textContent = 'Browser Anda tidak mendukung akses kamera langsung.';
        return;
    }

    navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user', width: { ideal: 640 }, height: { ideal: 480 } }, audio: false })
        .then(function (stream) {
            streamKameraAktif = stream;
            video.srcObject = stream;
            info.style.display = 'none';
            video.style.display = 'block';
            btnJepret.style.display = 'inline-flex';
        })
        .catch(function () {
            info.textContent = 'Akses kamera ditolak atau perangkat kamera tidak ditemukan. Izinkan kamera pada browser Anda.';
        });
}

function jepretFotoSelfie() {
    const video = document.getElementById('video-kamera');
    const canvas = document.getElementById('canvas-selfie');
    const preview = document.getElementById('preview-selfie');
    const inputBase64 = document.getElementById('input-foto-base64');

    const w = video.videoWidth || 640;
    const h = video.videoHeight || 480;
    canvas.width = w;
    canvas.height = h;

    const ctx = canvas.getContext('2d');
    ctx.translate(w, 0);
    ctx.scale(-1, 1);
    ctx.drawImage(video, 0, 0, w, h);

    const dataUrl = canvas.toDataURL('image/jpeg', 0.82);
    inputBase64.value = dataUrl;
    preview.src = dataUrl;

    video.style.display = 'none';
    preview.style.display = 'block';
    document.getElementById('btn-jepret').style.display = 'none';
    document.getElementById('btn-ulangi-foto').style.display = 'inline-flex';
    document.getElementById('btn-kirim-presensi').disabled = false;
}

function ulangiFotoSelfie() {
    document.getElementById('input-foto-base64').value = '';
    document.getElementById('btn-kirim-presensi').disabled = true;
    document.getElementById('preview-selfie').style.display = 'none';
    document.getElementById('video-kamera').style.display = 'block';
    document.getElementById('btn-jepret').style.display = 'inline-flex';
    document.getElementById('btn-ulangi-foto').style.display = 'none';
}

function tutupModalKamera() {
    if (streamKameraAktif) {
        streamKameraAktif.getTracks().forEach(function (t) { t.stop(); });
        streamKameraAktif = null;
    }
    tutupPopup('modal-kamera-presensi');
}

function lihatBuktiSelfie(data) {
    document.getElementById('bukti-judul').textContent = data.judul;
    document.getElementById('bukti-waktu').textContent = 'Waktu Presensi: ' + data.waktu;
    document.getElementById('bukti-img').src = data.foto;
    bukaPopup('modal-lihat-selfie');
}
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
