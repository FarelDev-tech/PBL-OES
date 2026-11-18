<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../models/ProkerModel.php';
require_once __DIR__ . '/../../models/PresensiModel.php';

requireLogin();

$idProker  = (int) ($_GET['id'] ?? 0);
$activeTab = trim($_GET['tab'] ?? 'kegiatan');
if (!in_array($activeTab, ['kegiatan', 'panitia'], true)) {
    $activeTab = 'kegiatan';
}

$prokerModel = new ProkerModel($pdo);
$proker = $prokerModel->getById($idProker);
if (!$proker) {
    setFlash('error', 'Program kerja tidak ditemukan.');
    header('Location: index.php');
    exit;
}

$pageTitle     = 'Detail Proker: ' . $proker['nama_proker'];
$activeMenu    = 'proker';
$activeProker  = $proker;
$_SESSION['active_proker_id'] = $idProker;

$activeRole    = $_SESSION['role'] ?? 'anggota';
$currentUserId = (int) ($_SESSION['user_id'] ?? 0);
$isAdmin       = isAdminRole($activeRole);

$panitiaUser    = $prokerModel->getPanitiaUser($currentUserId, $idProker);
$isKoorProker   = ($panitiaUser && $panitiaUser['jabatan'] === 'Koordinator');
$idDivisiKoor   = $isKoorProker ? (int) ($panitiaUser['id_divisi'] ?? 0) : 0;
$namaDivisiKoor = $isKoorProker ? ($panitiaUser['nama_divisi'] ?? '') : '';

$daftarDivisi       = $prokerModel->getDivisi($idProker);
$daftarKegiatan     = $prokerModel->getKegiatan($idProker);
$daftarPanitia      = $prokerModel->getPanitia($idProker);
$userBelumTerdaftar = $isAdmin ? $prokerModel->getUserBelumPanitia($idProker) : [];

include __DIR__ . '/../../includes/header.php';
?>

<div style="margin-bottom: 16px;">
    <a href="index.php" class="link-action" style="display: inline-flex; align-items: center; gap: 6px; font-weight: 500; text-decoration: none; color: var(--oes-neutral-600); font-size: 13px;">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
        Kembali ke Daftar Program Kerja
    </a>
</div>

<div class="oes-card" style="margin-bottom: 20px;">
    <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; flex-wrap: wrap;">
        <div>
            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 6px; flex-wrap: wrap;">
                <h1 class="page-title" style="font-size: 22px; margin: 0;"><?php echo e($proker['nama_proker']); ?></h1>
                <?php echo renderBadge($proker['status_proker']); ?>
            </div>
            <div style="font-size: 13px; color: var(--oes-neutral-500);">
                Periode <?php echo e($proker['periode'] ?? '2026/2027'); ?> &middot;
                Pelaksanaan: <?php echo formatTanggal($proker['tanggal_mulai']); ?> – <?php echo formatTanggal($proker['tanggal_selesai']); ?>
            </div>
            <?php if (!empty($proker['deskripsi'])): ?>
                <p style="font-size: 13px; color: var(--oes-neutral-700); margin-top: 10px; margin-bottom: 0; max-width: 800px; line-height: 1.5;">
                    <?php echo e($proker['deskripsi']); ?>
                </p>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="oes-tabs" style="margin-bottom: 20px;">
    <a href="detail.php?id=<?php echo $idProker; ?>&tab=kegiatan"
       class="oes-tab <?php echo $activeTab === 'kegiatan' ? 'active' : ''; ?>">
        Kegiatan &amp; Bobot Poin (<?php echo count($daftarKegiatan); ?>)
    </a>
    <a href="detail.php?id=<?php echo $idProker; ?>&tab=panitia"
       class="oes-tab <?php echo $activeTab === 'panitia' ? 'active' : ''; ?>">
        Kepanitiaan &amp; Divisi (<?php echo count($daftarPanitia); ?>)
    </a>
</div>

<?php if ($activeTab === 'kegiatan'): ?>
    <div class="oes-card" style="padding: 0; overflow: hidden; margin-bottom: 24px;">
        <div style="display: flex; align-items: center; justify-content: space-between; padding: 18px 22px; border-bottom: 1px solid var(--oes-neutral-200); flex-wrap: wrap; gap: 12px;">
            <div>
                <h2 class="section-title">Daftar Kegiatan Proker</h2>
                <div style="font-size: 12px; color: var(--oes-neutral-500);">
                    <?php if ($isAdmin): ?>
                        Admin menentukan nama kegiatan dan bobot poin presensi. Detail teknis pelaksanaan diatur oleh Koordinator di menu Kelola Presensi.
                    <?php else: ?>
                        Daftar rapat dan kegiatan Hari-H beserta bobot poin kontribusinya.
                    <?php endif; ?>
                </div>
            </div>
            <?php if ($isAdmin || $isKoorProker): ?>
            <button type="button" class="btn btn-primary btn-sm" onclick="bukaPopup('modal-tambah-kegiatan')">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 4px;"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Tambah Kegiatan
            </button>
            <?php endif; ?>
        </div>

        <?php if (empty($daftarKegiatan)): ?>
            <div class="empty-state" style="border: none; padding: 48px 20px; text-align: center;">
                <div class="empty-state-title" style="font-weight: 600; font-size: 15px; color: var(--oes-neutral-800); margin-bottom: 4px;">Belum Ada Kegiatan</div>
                <div class="empty-state-desc" style="font-size: 13px; color: var(--oes-neutral-500);">
                    <?php echo ($isAdmin || $isKoorProker) ? 'Klik "Tambah Kegiatan" untuk menambahkan kegiatan Rapat atau Hari-H pada proker ini.' : 'Belum ada kegiatan pada program kerja ini.'; ?>
                </div>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="oes-table">
                    <thead>
                        <tr>
                            <th>Nama Kegiatan</th>
                            <th>Cakupan Divisi</th>
                            <th>Jenis</th>
                            <th>Pelaksanaan</th>
                            <th>Tempat / Tautan</th>
                            <th>Bobot Poin</th>
                            <th>Hadir Disetujui</th>
                            <?php if ($isAdmin || $isKoorProker): ?>
                            <th style="text-align: right;">Aksi</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($daftarKegiatan as $keg): ?>
                            <tr>
                                <td style="font-weight: 600; color: var(--oes-neutral-900);">
                                    <?php echo e($keg['nama_kegiatan']); ?>
                                </td>
                                <td><?php echo !empty($keg['nama_divisi']) ? e($keg['nama_divisi']) : 'Semua Divisi'; ?></td>
                                <td><?php echo renderBadge($keg['jenis_kegiatan']); ?></td>
                                <td>
                                    <?php if ($keg['status_detail'] === 'Belum Dilengkapi'): ?>
                                        <span style="color: #b45309; font-size: 12px; font-weight: 500;">Menunggu Koordinator</span>
                                    <?php else: ?>
                                        <div style="font-weight: 500; color: var(--oes-neutral-900);"><?php echo formatTanggal($keg['tanggal']); ?></div>
                                        <div style="font-size: 12px; color: var(--oes-neutral-500);"><?php echo formatJam($keg['jam_mulai'], $keg['jam_selesai']); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($keg['status_detail'] === 'Belum Dilengkapi'): ?>
                                        <span style="color: var(--oes-neutral-400);">-</span>
                                    <?php else: ?>
                                        <div style="margin-bottom: 4px;"><?php echo renderBadge($keg['pelaksanaan'] ?? $keg['mode_kegiatan'] ?? 'Offline'); ?></div>
                                        <div style="font-size: 12px; color: var(--oes-neutral-600); max-width: 220px; white-space: normal;"><?php echo e($keg['tempat_atau_link'] ?? '-'); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td style="font-weight: 700; color: var(--oes-primary-800);">
                                    +<?php echo (int) $keg['bobot_poin']; ?> poin
                                </td>
                                <td style="font-size: 13px; color: var(--oes-neutral-700);">
                                    <?php echo (int) ($keg['total_hadir'] ?? 0); ?> orang
                                </td>
                                <?php if ($isAdmin || $isKoorProker): ?>
                                <?php
                                $canKelolaKeg = $isAdmin || ($isKoorProker && (empty($keg['id_divisi']) || (int) ($keg['id_divisi'] ?? 0) === $idDivisiKoor));
                                ?>
                                <td style="text-align: right;">
                                    <?php if ($canKelolaKeg): ?>
                                        <div style="display: inline-flex; align-items: center; gap: 6px; justify-content: flex-end;">
                                            <button type="button" class="btn <?php echo ($keg['status_detail'] === 'Belum Dilengkapi') ? 'btn-primary' : 'btn-outline'; ?> btn-sm"
                                                    onclick='bukaModalAturPelaksanaan(<?php echo json_encode([
                                                        "id_kegiatan"      => (int) $keg["id_kegiatan"],
                                                        "nama_kegiatan"    => $keg["nama_kegiatan"],
                                                        "tanggal"          => $keg["tanggal"] ?? date("Y-m-d"),
                                                        "jam_mulai"        => !empty($keg["jam_mulai"]) ? substr($keg["jam_mulai"], 0, 5) : "15:30",
                                                        "jam_selesai"      => !empty($keg["jam_selesai"]) ? substr($keg["jam_selesai"], 0, 5) : "17:00",
                                                        "pelaksanaan"      => $keg["pelaksanaan"] ?? $keg["mode_kegiatan"] ?? "Offline",
                                                        "tempat_atau_link" => $keg["tempat_atau_link"] ?? "",
                                                    ], JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'>
                                                <?php echo ($keg['status_detail'] === 'Belum Dilengkapi') ? 'Atur Pelaksanaan' : 'Ubah'; ?>
                                            </button>
                                            <button type="button" class="btn btn-danger-outline btn-sm"
                                                    onclick="konfirmasiHapusKegiatan(<?php echo (int) $keg['id_kegiatan']; ?>, <?php echo htmlspecialchars(json_encode($keg['nama_kegiatan']), ENT_QUOTES, 'UTF-8'); ?>)">
                                                Hapus
                                            </button>
                                        </div>
                                    <?php else: ?>
                                        <span style="color: var(--oes-neutral-400); font-size: 12px;">-</span>
                                    <?php endif; ?>
                                </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

<?php else: ?>
    <div class="oes-card" style="margin-bottom: 20px;">
        <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;">
            <div>
                <h2 class="section-title">Daftar Divisi Proker</h2>
                <div style="font-size: 12px; color: var(--oes-neutral-500);">Divisi yang tersedia dalam susunan panitia proker ini.</div>
            </div>
            <?php if ($isAdmin): ?>
            <button type="button" class="btn btn-outline btn-sm" onclick="bukaPopup('modal-tambah-divisi')">
                + Tambah Divisi
            </button>
            <?php endif; ?>
        </div>

        <div style="display: flex; flex-wrap: wrap; gap: 8px; margin-top: 14px;">
            <?php if (empty($daftarDivisi)): ?>
                <span style="font-size: 13px; color: var(--oes-neutral-500);">Belum ada divisi yang dibuat.</span>
            <?php else: ?>
                <?php foreach ($daftarDivisi as $d): ?>
                    <span class="oes-badge badge-outline" style="padding: 6px 12px; font-size: 12px; display: inline-flex; align-items: center; gap: 8px; border-radius: 9999px;">
                        <span><?php echo e($d['nama_divisi']); ?></span>
                        <?php if ($isAdmin): ?>
                        <button type="button"
                                onclick="konfirmasiHapusDivisi(<?php echo (int) $d['id_divisi']; ?>, <?php echo htmlspecialchars(json_encode($d['nama_divisi']), ENT_QUOTES, 'UTF-8'); ?>)"
                                title="Hapus divisi"
                                style="border: none; background: transparent; color: var(--oes-neutral-400); cursor: pointer; font-size: 15px; font-weight: bold; line-height: 1; padding: 0;">
                            &times;
                        </button>
                        <?php endif; ?>
                    </span>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="oes-card" style="padding: 0; overflow: hidden; margin-bottom: 24px;">
        <div style="display: flex; align-items: center; justify-content: space-between; padding: 18px 22px; border-bottom: 1px solid var(--oes-neutral-200); flex-wrap: wrap; gap: 12px;">
            <div>
                <h2 class="section-title">Susunan Panitia Proker</h2>
                <div style="font-size: 12px; color: var(--oes-neutral-500);">Daftar Koordinator dan Anggota divisi pada proker ini.</div>
            </div>
            <?php if ($isAdmin): ?>
            <button type="button" class="btn btn-primary btn-sm" onclick="bukaPopup('modal-tambah-panitia')">
                + Tambah ke Kepanitiaan
            </button>
            <?php endif; ?>
        </div>

        <?php if (empty($daftarPanitia)): ?>
            <div class="empty-state" style="border: none; padding: 48px 20px; text-align: center;">
                <div class="empty-state-title" style="font-weight: 600; font-size: 15px; color: var(--oes-neutral-800); margin-bottom: 4px;">Belum Ada Panitia</div>
                <div class="empty-state-desc" style="font-size: 13px; color: var(--oes-neutral-500);">
                    <?php echo $isAdmin ? 'Klik "+ Tambah ke Kepanitiaan" untuk menempatkan anggota dan koordinator.' : 'Belum ada panitia yang terdaftar.'; ?>
                </div>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="oes-table">
                    <thead>
                        <tr>
                            <th>Nama</th>
                            <th>NIM</th>
                            <th>Divisi</th>
                            <th>Jabatan di Proker</th>
                            <?php if ($isAdmin): ?>
                            <th style="text-align: right;">Aksi</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($daftarPanitia as $pan): ?>
                            <?php
                            $jsonPan = htmlspecialchars(json_encode([
                                'id_panitia' => (int) $pan['id_panitia'],
                                'nama'       => $pan['nama'],
                                'id_divisi'  => (int) ($pan['id_divisi'] ?? 0),
                                'jabatan'    => $pan['jabatan'],
                            ], JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
                            ?>
                            <tr>
                                <td style="font-weight: 600; color: var(--oes-neutral-900);"><?php echo e($pan['nama']); ?></td>
                                <td style="font-family: monospace; font-size: 12px; color: var(--oes-neutral-600);"><?php echo e($pan['nim']); ?></td>
                                <td><?php echo renderBadge($pan['nama_divisi'] ?? 'Belum Diatur'); ?></td>
                                <td>
                                    <?php if ($pan['jabatan'] === 'Koordinator'): ?>
                                        <span class="oes-badge badge-dark">Koordinator</span>
                                    <?php else: ?>
                                        <span class="oes-badge badge-neutral">Anggota</span>
                                    <?php endif; ?>
                                </td>
                                <?php if ($isAdmin): ?>
                                <td style="text-align: right;">
                                    <div class="table-actions" style="justify-content: flex-end;">
                                        <button type="button" class="btn btn-outline btn-sm" onclick='bukaModalAturJabatan(<?php echo $jsonPan; ?>)'>
                                            Atur
                                        </button>
                                        <button type="button" class="btn btn-danger-outline btn-sm"
                                                onclick="konfirmasiKeluarkanPanitia(<?php echo (int) $pan['id_panitia']; ?>, <?php echo htmlspecialchars(json_encode($pan['nama']), ENT_QUOTES, 'UTF-8'); ?>)">
                                            Keluarkan
                                        </button>
                                    </div>
                                </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php if ($isAdmin || $isKoorProker): ?>
    <div class="modal-backdrop" id="modal-tambah-kegiatan">
        <div class="modal-dialog" style="max-width: 480px;">
            <div class="modal-header">
                <h3 class="modal-title">Tambah Kegiatan Proker</h3>
                <button type="button" class="modal-close" onclick="tutupPopup('modal-tambah-kegiatan')">&times;</button>
            </div>
            <form method="post" action="proses.php" onsubmit="return validasiFormKegiatan(this)">
                <?php echo csrfField(); ?>
                <input type="hidden" name="aksi" value="tambah_kegiatan">
                <input type="hidden" name="id_proker" value="<?php echo $idProker; ?>">
                <div class="modal-body">
                    <div class="form-group" style="margin-bottom: 14px;">
                        <label class="form-label">Nama Kegiatan *</label>
                        <input type="text" name="nama_kegiatan" class="form-control" placeholder="Contoh: Rapat Pleno 1" required>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 14px;">
                        <div class="form-group" style="margin-bottom: 0;">
                            <label class="form-label">Jenis Kegiatan *</label>
                            <select name="jenis_kegiatan" class="form-select" style="width: 100%;" required>
                                <option value="Rapat">Rapat</option>
                                <option value="Hari-H">Hari-H</option>
                            </select>
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label class="form-label">Bobot Poin *</label>
                            <input type="number" name="bobot_poin" class="form-control" value="20" min="1" max="500" required>
                        </div>
                    </div>
                    <div class="form-group" style="margin-bottom: 14px;">
                        <label class="form-label">Divisi Penyelenggara</label>
                        <?php if ($isKoorProker && !$isAdmin): ?>
                            <input type="hidden" name="id_divisi" value="<?php echo $idDivisiKoor; ?>">
                            <input type="text" class="form-control" value="<?php echo e($namaDivisiKoor ?: 'Divisi Anda'); ?>" readonly style="background: #f1f5f9; cursor: not-allowed;">
                        <?php else: ?>
                            <select name="id_divisi" class="form-select" style="width: 100%;">
                                <option value="0">Semua Divisi (Umum)</option>
                                <?php foreach ($daftarDivisi as $d): ?>
                                    <option value="<?php echo (int) $d['id_divisi']; ?>"><?php echo e($d['nama_divisi']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        <?php endif; ?>
                    </div>

                    <div style="border-top: 1px dashed var(--oes-neutral-200); padding-top: 12px; margin-top: 4px; margin-bottom: 14px;">
                        <div style="font-size: 12px; font-weight: 600; color: var(--oes-neutral-700); margin-bottom: 10px;">Jadwal &amp; Pelaksanaan (Opsional)</div>
                        <div class="form-group" style="margin-bottom: 10px;">
                            <label class="form-label">Tanggal Pelaksanaan</label>
                            <input type="date" name="tanggal" class="form-control"
                                   min="<?php echo htmlspecialchars($proker['tanggal_mulai'] ?? ''); ?>"
                                   max="<?php echo htmlspecialchars($proker['tanggal_selesai'] ?? ''); ?>">
                            <?php if (!empty($proker['tanggal_mulai']) && !empty($proker['tanggal_selesai'])): ?>
                                <div style="font-size: 11px; color: var(--oes-neutral-500); margin-top: 4px;">
                                    Batas tanggal kegiatan: <strong><?php echo formatTanggal($proker['tanggal_mulai']); ?></strong> s/d <strong><?php echo formatTanggal($proker['tanggal_selesai']); ?></strong> (sesuai periode proker)
                                </div>
                            <?php endif; ?>
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 10px;">
                            <div class="form-group" style="margin-bottom: 0;">
                                <label class="form-label">Jam Mulai</label>
                                <input type="time" name="jam_mulai" class="form-control">
                            </div>
                            <div class="form-group" style="margin-bottom: 0;">
                                <label class="form-label">Jam Selesai</label>
                                <input type="time" name="jam_selesai" class="form-control">
                            </div>
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 12px; margin-bottom: 0;">
                            <div class="form-group" style="margin-bottom: 0;">
                                <label class="form-label">Pelaksanaan</label>
                                <select name="pelaksanaan" class="form-select" style="width: 100%;">
                                    <option value="Offline">Offline</option>
                                    <option value="Online">Online</option>
                                </select>
                            </div>
                            <div class="form-group" style="margin-bottom: 0;">
                                <label class="form-label">Tempat / Ruangan / Link</label>
                                <input type="text" name="tempat_atau_link" class="form-control" placeholder="Contoh: Gedung Sipil Lt. 7">
                            </div>
                        </div>
                    </div>

                    <div class="alert-box-neutral" style="font-size: 12px; margin-bottom: 0;">
                        💡 Jadwal dan tempat dapat diisi langsung sekarang atau diatur kapan saja via tombol <strong>Atur Pelaksanaan</strong>.
                    </div>
                </div>
                <div class="modal-footer" style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <button type="button" class="btn btn-outline" onclick="tutupPopup('modal-tambah-kegiatan')">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Kegiatan</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal-backdrop" id="modal-atur-pelaksanaan">
        <div class="modal-dialog" style="max-width: 480px;">
            <div class="modal-header">
                <h3 class="modal-title">Atur Pelaksanaan Kegiatan</h3>
                <button type="button" class="modal-close" onclick="tutupPopup('modal-atur-pelaksanaan')">&times;</button>
            </div>
            <form method="post" action="proses.php" onsubmit="return validasiFormKegiatan(this)">
                <?php echo csrfField(); ?>
                <input type="hidden" name="aksi" value="update_jadwal_kegiatan">
                <input type="hidden" name="id_proker" value="<?php echo $idProker; ?>">
                <input type="hidden" name="id_kegiatan" id="atur-id-kegiatan" value="">
                <div class="modal-body">
                    <div style="background-color: var(--oes-neutral-50); border-radius: var(--radius-md); padding: 12px 14px; margin-bottom: 14px;">
                        <div style="font-size: 11px; color: var(--oes-neutral-500); text-transform: uppercase;">Nama Kegiatan</div>
                        <strong id="atur-nama-kegiatan" style="font-size: 14px; color: var(--oes-neutral-900);">—</strong>
                    </div>

                    <div class="form-group" style="margin-bottom: 14px;">
                        <label class="form-label">Tanggal Pelaksanaan *</label>
                        <input type="date" name="tanggal" id="atur-tanggal" class="form-control" required
                               min="<?php echo htmlspecialchars($proker['tanggal_mulai'] ?? ''); ?>"
                               max="<?php echo htmlspecialchars($proker['tanggal_selesai'] ?? ''); ?>">
                        <?php if (!empty($proker['tanggal_mulai']) && !empty($proker['tanggal_selesai'])): ?>
                            <div style="font-size: 11px; color: var(--oes-neutral-500); margin-top: 4px;">
                                Batas tanggal kegiatan: <strong><?php echo formatTanggal($proker['tanggal_mulai']); ?></strong> s/d <strong><?php echo formatTanggal($proker['tanggal_selesai']); ?></strong> (sesuai periode proker)
                            </div>
                        <?php endif; ?>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 14px;">
                        <div class="form-group" style="margin-bottom: 0;">
                            <label class="form-label">Jam Mulai *</label>
                            <input type="time" name="jam_mulai" id="atur-jam-mulai" class="form-control" required>
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label class="form-label">Jam Selesai *</label>
                            <input type="time" name="jam_selesai" id="atur-jam-selesai" class="form-control" required>
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom: 14px;">
                        <label class="form-label">Pelaksanaan *</label>
                        <select name="pelaksanaan" id="atur-mode" class="form-select" style="width: 100%;" required>
                            <option value="Offline">Offline (Tatap Muka)</option>
                            <option value="Online">Online (Zoom / GMeet)</option>
                        </select>
                    </div>

                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label">Tempat / Ruangan / Tautan *</label>
                        <input type="text" name="tempat_atau_link" id="atur-tempat" class="form-control" placeholder="Contoh: Gedung Sipil Lt. 7 R. 702 atau Link Zoom" required>
                    </div>
                </div>
                <div class="modal-footer" style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <button type="button" class="btn btn-outline" onclick="tutupPopup('modal-atur-pelaksanaan')">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Pelaksanaan</button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<?php if ($isAdmin): ?>

    <div class="modal-backdrop" id="modal-tambah-divisi">
        <div class="modal-dialog" style="max-width: 420px;">
            <div class="modal-header">
                <h3 class="modal-title">Tambah Divisi Baru</h3>
                <button type="button" class="modal-close" onclick="tutupPopup('modal-tambah-divisi')">&times;</button>
            </div>
            <form method="post" action="proses.php">
                <?php echo csrfField(); ?>
                <input type="hidden" name="aksi" value="tambah_divisi">
                <input type="hidden" name="id_proker" value="<?php echo $idProker; ?>">
                <div class="modal-body">
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label">Nama Divisi *</label>
                        <input type="text" name="nama_divisi" class="form-control" placeholder="Contoh: Divisi Konsumsi" required>
                    </div>
                </div>
                <div class="modal-footer" style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <button type="button" class="btn btn-outline" onclick="tutupPopup('modal-tambah-divisi')">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal-backdrop" id="modal-tambah-panitia">
        <div class="modal-dialog" style="max-width: 520px;">
            <div class="modal-header">
                <h3 class="modal-title">Tambah ke Kepanitiaan</h3>
                <button type="button" class="modal-close" onclick="tutupPopup('modal-tambah-panitia')">&times;</button>
            </div>
            <form method="post" action="proses.php" onsubmit="return cekSubmitPanitia(this);">
                <?php echo csrfField(); ?>
                <input type="hidden" name="aksi" value="tambah_panitia">
                <input type="hidden" name="id_proker" value="<?php echo $idProker; ?>">
                <div class="modal-body">
                    <div class="form-group" style="margin-bottom: 14px;">
                        <label class="form-label">Divisi Tujuan *</label>
                        <select name="id_divisi" class="form-select" style="width: 100%;" required>
                            <option value="">Pilih Divisi</option>
                            <?php foreach ($daftarDivisi as $d): ?>
                                <option value="<?php echo (int) $d['id_divisi']; ?>"><?php echo e($d['nama_divisi']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group" style="margin-bottom: 14px;">
                        <label class="form-label">Jabatan di Proker *</label>
                        <div style="display: flex; gap: 16px; padding: 10px 14px; background: var(--oes-neutral-50); border: 1px solid var(--oes-neutral-200); border-radius: var(--radius-md);">
                            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 13px;">
                                <input type="radio" name="jabatan" value="Anggota" checked onchange="gantiModePanitia('Anggota')">
                                <span>Anggota <span style="font-size: 11px; color: var(--oes-neutral-500);">(Pilih banyak)</span></span>
                            </label>
                            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 13px;">
                                <input type="radio" name="jabatan" value="Koordinator" onchange="gantiModePanitia('Koordinator')">
                                <span>Koordinator <span style="font-size: 11px; color: var(--oes-neutral-500);">(Maks 1 orang)</span></span>
                            </label>
                        </div>
                    </div>

                    <div class="form-group" id="box-anggota" style="margin-bottom: 0;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                            <label class="form-label" style="margin: 0;">Pilih Calon Anggota *</label>
                            <div style="font-size: 11px; display: flex; gap: 8px;">
                                <button type="button" onclick="centangSemuaCalon(true)" style="background: none; border: none; padding: 0; color: var(--oes-primary-700); font-weight: 600; cursor: pointer;">Pilih Semua</button>
                                <span style="color: var(--oes-neutral-300);">|</span>
                                <button type="button" onclick="centangSemuaCalon(false)" style="background: none; border: none; padding: 0; color: var(--oes-neutral-500); cursor: pointer;">Batal Semua</button>
                            </div>
                        </div>

                        <div style="margin-bottom: 8px;">
                            <input type="text" class="form-control" placeholder="Cari nama atau NIM..." style="font-size: 12px; padding: 6px 10px;" oninput="filterCalon(this.value)">
                        </div>

                        <div id="list-calon-anggota" style="max-height: 180px; overflow-y: auto; border: 1px solid var(--oes-neutral-200); border-radius: var(--radius-md); padding: 6px 10px; background: #fff;">
                            <?php if (empty($userBelumTerdaftar)): ?>
                                <div style="font-size: 12px; color: var(--oes-neutral-400); text-align: center; padding: 14px;">
                                    Semua akun aktif sudah terdaftar di kepanitiaan proker ini.
                                </div>
                            <?php else: ?>
                                <?php foreach ($userBelumTerdaftar as $c): ?>
                                    <label class="item-calon" style="display: flex; align-items: center; gap: 10px; padding: 6px 4px; border-bottom: 1px solid var(--oes-neutral-100); font-size: 13px; cursor: pointer;">
                                        <input type="checkbox" name="user_ids[]" value="<?php echo (int) $c['id_user']; ?>" class="cb-calon" onchange="hitungTerpilih()">
                                        <span class="nama-calon" style="font-weight: 500;"><?php echo e($c['nama']); ?></span>
                                        <span class="nim-calon" style="font-size: 11px; color: var(--oes-neutral-500); margin-left: auto;">NIM: <?php echo e($c['nim']); ?></span>
                                    </label>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <div id="counter-terpilih" style="font-size: 11px; color: var(--oes-neutral-500); margin-top: 5px;">0 anggota dipilih</div>
                    </div>

                    <div class="form-group" id="box-koor" style="display: none; margin-bottom: 0;">
                        <label class="form-label">Pilih Koordinator Divisi *</label>
                        <select name="id_user_koor" id="select-calon-koor" class="form-select" style="width: 100%;">
                            <option value="">Pilih 1 calon Koordinator</option>
                            <?php foreach ($userBelumTerdaftar as $c): ?>
                                <option value="<?php echo (int) $c['id_user']; ?>">
                                    <?php echo e($c['nama']); ?> (<?php echo e($c['nim']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="alert-box-neutral" style="font-size: 12px; margin-top: 10px; margin-bottom: 0;">
                            💡 Setiap divisi hanya boleh memiliki <strong>1 Koordinator</strong>. Role sistem akun otomatis disesuaikan.
                        </div>
                    </div>
                </div>
                <div class="modal-footer" style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <button type="button" class="btn btn-outline" onclick="tutupPopup('modal-tambah-panitia')">Batal</button>
                    <button type="submit" class="btn btn-primary">Tambahkan</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal-backdrop" id="modal-atur-jabatan">
        <div class="modal-dialog" style="max-width: 440px;">
            <div class="modal-header">
                <h3 class="modal-title">Atur Divisi &amp; Jabatan</h3>
                <button type="button" class="modal-close" onclick="tutupPopup('modal-atur-jabatan')">&times;</button>
            </div>
            <form method="post" action="proses.php">
                <?php echo csrfField(); ?>
                <input type="hidden" name="aksi" value="atur_jabatan">
                <input type="hidden" name="id_proker" value="<?php echo $idProker; ?>">
                <input type="hidden" name="id_panitia" id="atur-id-panitia" value="">
                <div class="modal-body">
                    <div style="background-color: var(--oes-neutral-50); border-radius: var(--radius-md); padding: 12px 14px; margin-bottom: 14px;">
                        <div style="font-size: 11px; color: var(--oes-neutral-500); text-transform: uppercase;">Nama Panitia</div>
                        <strong id="atur-nama-panitia" style="font-size: 14px; color: var(--oes-neutral-900);">—</strong>
                    </div>

                    <div class="form-group" style="margin-bottom: 14px;">
                        <label class="form-label">Divisi *</label>
                        <select name="id_divisi" id="atur-select-divisi" class="form-select" style="width: 100%;" required>
                            <?php foreach ($daftarDivisi as $d): ?>
                                <option value="<?php echo (int) $d['id_divisi']; ?>"><?php echo e($d['nama_divisi']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label">Jabatan *</label>
                        <select name="jabatan" id="atur-select-jabatan" class="form-select" style="width: 100%;" required>
                            <option value="Anggota">Anggota</option>
                            <option value="Koordinator">Koordinator</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer" style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <button type="button" class="btn btn-outline" onclick="tutupPopup('modal-atur-jabatan')">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <form method="post" action="proses.php" id="form-hapus-divisi" style="display:none;">
        <?php echo csrfField(); ?>
        <input type="hidden" name="aksi" value="hapus_divisi">
        <input type="hidden" name="id_proker" value="<?php echo $idProker; ?>">
        <input type="hidden" name="id_divisi" id="hapus-div-id" value="">
    </form>

    <form method="post" action="proses.php" id="form-keluarkan-panitia" style="display:none;">
        <?php echo csrfField(); ?>
        <input type="hidden" name="aksi" value="keluarkan_panitia">
        <input type="hidden" name="id_proker" value="<?php echo $idProker; ?>">
        <input type="hidden" name="id_panitia" id="keluarkan-pan-id" value="">
    </form>
<?php endif; ?>

<?php if ($isAdmin || $isKoorProker): ?>
    <form method="post" action="proses.php" id="form-hapus-kegiatan" style="display:none;">
        <?php echo csrfField(); ?>
        <input type="hidden" name="aksi" value="hapus_kegiatan">
        <input type="hidden" name="id_proker" value="<?php echo $idProker; ?>">
        <input type="hidden" name="id_kegiatan" id="hapus-keg-id" value="">
    </form>
<?php endif; ?>

<script>
function validasiFormKegiatan(form) {
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
        judul: 'Hapus Kegiatan?',
        deskripsi: 'Apakah kamu yakin ingin menghapus kegiatan "' + namaKegiatan + '"? Presensi terkait kegiatan ini juga akan dihapus.',
        teksTombol: 'Ya, Hapus Kegiatan',
        berbahaya: true,
        action: '#',
        onConfirm: function() {
            document.getElementById('hapus-keg-id').value = idKegiatan;
            document.getElementById('form-hapus-kegiatan').submit();
        }
    });
}

function bukaModalAturPelaksanaan(data) {
    document.getElementById('atur-id-kegiatan').value         = data.id_kegiatan;
    document.getElementById('atur-nama-kegiatan').textContent = data.nama_kegiatan;
    document.getElementById('atur-tanggal').value             = data.tanggal || '';
    document.getElementById('atur-jam-mulai').value           = data.jam_mulai || '15:30';
    document.getElementById('atur-jam-selesai').value         = data.jam_selesai || '17:00';
    document.getElementById('atur-mode').value                = data.pelaksanaan || data.mode_kegiatan || 'Offline';
    document.getElementById('atur-tempat').value              = data.tempat_atau_link || '';
    bukaPopup('modal-atur-pelaksanaan');
}

function konfirmasiHapusDivisi(idDivisi, namaDivisi) {
    bukaPopupKonfirmasi({
        judul: 'Hapus Divisi?',
        deskripsi: 'Hapus divisi "' + namaDivisi + '"? Panitia pada divisi ini akan dipindahkan ke status tanpa divisi.',
        teksTombol: 'Ya, Hapus Divisi',
        berbahaya: true,
        action: '#',
        onConfirm: function() {
            document.getElementById('hapus-div-id').value = idDivisi;
            document.getElementById('form-hapus-divisi').submit();
        }
    });
}

function konfirmasiKeluarkanPanitia(idPanitia, nama) {
    bukaPopupKonfirmasi({
        judul: 'Keluarkan Panitia?',
        deskripsi: 'Keluarkan "' + nama + '" dari kepanitiaan proker ini?',
        teksTombol: 'Ya, Keluarkan',
        berbahaya: true,
        action: '#',
        onConfirm: function() {
            document.getElementById('keluarkan-pan-id').value = idPanitia;
            document.getElementById('form-keluarkan-panitia').submit();
        }
    });
}

function bukaModalAturJabatan(pan) {
    document.getElementById('atur-id-panitia').value      = pan.id_panitia;
    document.getElementById('atur-nama-panitia').textContent = pan.nama;
    document.getElementById('atur-select-divisi').value   = pan.id_divisi;
    document.getElementById('atur-select-jabatan').value  = pan.jabatan;
    bukaPopup('modal-atur-jabatan');
}

function gantiModePanitia(mode) {
    const boxAnggota = document.getElementById('box-anggota');
    const boxKoor    = document.getElementById('box-koor');
    const selKoor    = document.getElementById('select-calon-koor');

    if (mode === 'Koordinator') {
        boxAnggota.style.display = 'none';
        boxKoor.style.display    = 'block';
        if (selKoor) selKoor.required = true;
    } else {
        boxAnggota.style.display = 'block';
        boxKoor.style.display    = 'none';
        if (selKoor) {
            selKoor.required = false;
            selKoor.value    = '';
        }
    }
}

function centangSemuaCalon(status) {
    const items = document.querySelectorAll('.item-calon');
    items.forEach(item => {
        if (item.style.display !== 'none') {
            const cb = item.querySelector('.cb-calon');
            if (cb) cb.checked = status;
        }
    });
    hitungTerpilih();
}

function filterCalon(q) {
    q = q.toLowerCase().trim();
    const items = document.querySelectorAll('.item-calon');
    items.forEach(item => {
        const nama = item.querySelector('.nama-calon')?.textContent.toLowerCase() || '';
        const nim  = item.querySelector('.nim-calon')?.textContent.toLowerCase() || '';
        item.style.display = (q === '' || nama.includes(q) || nim.includes(q)) ? 'flex' : 'none';
    });
}

function hitungTerpilih() {
    const checked = document.querySelectorAll('.cb-calon:checked').length;
    const el = document.getElementById('counter-terpilih');
    if (el) el.textContent = checked + ' anggota dipilih';
}

function cekSubmitPanitia(form) {
    const radio = form.querySelector('input[name="jabatan"]:checked');
    const jabatan = radio ? radio.value : 'Anggota';
    if (jabatan === 'Anggota') {
        const count = form.querySelectorAll('.cb-calon:checked').length;
        if (count === 0) {
            bukaPopupKonfirmasi({
                judul: 'Pilih Calon Anggota',
                deskripsi: 'Silakan pilih minimal 1 orang calon anggota untuk ditambahkan ke kepanitiaan.',
                teksTombol: 'Mengerti',
                berbahaya: false,
                action: '#',
                onConfirm: function() {}
            });
            return false;
        }
    } else {
        const selKoor = document.getElementById('select-calon-koor');
        if (!selKoor || !selKoor.value) {
            bukaPopupKonfirmasi({
                judul: 'Pilih Calon Koordinator',
                deskripsi: 'Silakan pilih 1 orang calon Koordinator untuk divisi yang dipilih.',
                teksTombol: 'Mengerti',
                berbahaya: false,
                action: '#',
                onConfirm: function() {}
            });
            return false;
        }
    }
    return true;
}
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
