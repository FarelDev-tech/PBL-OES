<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../models/ProkerModel.php';
require_once __DIR__ . '/../../models/PresensiModel.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

requireCsrf();

$action        = $_POST['action'] ?? '';
$currentUserId = (int) ($_SESSION['user_id'] ?? 0);

$prokerModel   = new ProkerModel($pdo);
$presensiModel = new PresensiModel($pdo);

try {
    if ($action === 'submit_selfie') {
        $idKegiatan = (int) ($_POST['id_kegiatan'] ?? 0);
        $prokerId   = (int) ($_POST['proker_id'] ?? 0);
        $fotoBase64 = $_POST['foto_base64'] ?? '';

        if ($idKegiatan <= 0 || empty($fotoBase64)) {
            setFlash('error', 'Harap ambil foto selfie terlebih dahulu sebelum mengirim presensi.');
            header('Location: index.php?proker_id=' . $prokerId);
            exit;
        }

        if (!preg_match('/^data:image\/(jpeg|jpg|png);base64,/', $fotoBase64, $matches)) {
            setFlash('error', 'Format gambar selfie tidak valid.');
            header('Location: index.php?proker_id=' . $prokerId);
            exit;
        }

        $base64Data = substr($fotoBase64, strpos($fotoBase64, ',') + 1);
        $binaryData = base64_decode($base64Data, true);

        if ($binaryData === false || strlen($binaryData) > 3 * 1024 * 1024) {
            setFlash('error', 'Ukuran foto terlalu besar atau rusak (maksimal 3 MB).');
            header('Location: index.php?proker_id=' . $prokerId);
            exit;
        }

        $uploadDir = __DIR__ . '/../uploads/presensi/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0775, true);
        }

        $namaFile = 'selfie_u' . $currentUserId . '_k' . $idKegiatan . '_' . time() . '.jpg';
        $fullPath = $uploadDir . $namaFile;

        if (file_put_contents($fullPath, $binaryData) === false) {
            setFlash('error', 'Gagal menyimpan foto selfie ke server.');
            header('Location: index.php?proker_id=' . $prokerId);
            exit;
        }

        $presensiModel->simpanSelfie($idKegiatan, $currentUserId, $namaFile);

        setFlash('success', 'Presensi selfie berhasil dikirim dan sedang menunggu validasi.');
        header('Location: index.php?proker_id=' . $prokerId);
        exit;
    } elseif ($action === 'update_jadwal_kegiatan') {
        $idKegiatan = (int) ($_POST['id_kegiatan'] ?? 0);
        $prokerId   = (int) ($_POST['proker_id'] ?? 0);

        $pelaksanaan = in_array($_POST['pelaksanaan'] ?? $_POST['mode_kegiatan'] ?? '', ['Online', 'Offline'], true) ? ($_POST['pelaksanaan'] ?? $_POST['mode_kegiatan']) : 'Offline';
        $prokerModel->updateDetailKegiatan($idKegiatan, [
            'tanggal'          => $_POST['tanggal'] ?? date('Y-m-d'),
            'jam_mulai'        => $_POST['jam_mulai'] ?? '15:30',
            'jam_selesai'      => $_POST['jam_selesai'] ?? '17:00',
            'pelaksanaan'      => $pelaksanaan,
            'tempat_atau_link' => trim($_POST['tempat_atau_link'] ?? '-'),
        ]);

        setFlash('success', 'Jadwal dan tempat kegiatan berhasil diperbarui. Sesi siap digunakan untuk presensi.');
        header('Location: kelola.php?proker_id=' . $prokerId);
        exit;
    } elseif ($action === 'tambah_kegiatan') {
        $prokerId  = (int) ($_POST['proker_id'] ?? 0);
        $namaKeg   = trim($_POST['nama_kegiatan'] ?? '');
        $jenisKeg  = $_POST['jenis_kegiatan'] ?? 'Rapat';
        $bobotPoin = max(1, (int) ($_POST['bobot_poin'] ?? 10));

        $panitiaUser  = $prokerModel->getPanitiaUser($currentUserId, $prokerId);
        $isKoorProker = ($panitiaUser && $panitiaUser['jabatan'] === 'Koordinator');
        $isAdmin      = isAdminRole($_SESSION['role'] ?? 'anggota');

        if (!$isAdmin && !$isKoorProker) {
            setFlash('error', 'Akses ditolak.');
            header('Location: kelola.php?proker_id=' . $prokerId);
            exit;
        }

        $idDivisi = ($isKoorProker && !$isAdmin) ? (int) ($panitiaUser['id_divisi'] ?? 0) : (!empty($_POST['id_divisi']) ? (int) $_POST['id_divisi'] : null);

        if ($namaKeg === '') {
            setFlash('error', 'Nama kegiatan wajib diisi.');
            header('Location: kelola.php?proker_id=' . $prokerId);
            exit;
        }

        $pelaksanaan = in_array($_POST['pelaksanaan'] ?? $_POST['mode_kegiatan'] ?? '', ['Online', 'Offline'], true) ? ($_POST['pelaksanaan'] ?? $_POST['mode_kegiatan']) : 'Offline';
        $prokerModel->createKegiatan([
            'id_proker'        => $prokerId,
            'id_divisi'        => $idDivisi,
            'nama_kegiatan'    => $namaKeg,
            'jenis_kegiatan'   => $jenisKeg,
            'bobot_poin'       => $bobotPoin,
            'tanggal'          => !empty($_POST['tanggal']) ? $_POST['tanggal'] : null,
            'jam_mulai'        => !empty($_POST['jam_mulai']) ? $_POST['jam_mulai'] : null,
            'jam_selesai'      => !empty($_POST['jam_selesai']) ? $_POST['jam_selesai'] : null,
            'pelaksanaan'      => $pelaksanaan,
            'tempat_atau_link' => !empty($_POST['tempat_atau_link']) ? trim($_POST['tempat_atau_link']) : null,
        ]);

        setFlash('success', 'Sesi kegiatan baru berhasil ditambahkan.');
        header('Location: kelola.php?proker_id=' . $prokerId);
        exit;
    } elseif ($action === 'hapus_kegiatan') {
        $idKegiatan = (int) ($_POST['id_kegiatan'] ?? 0);
        $prokerId   = (int) ($_POST['proker_id'] ?? 0);

        $panitiaUser  = $prokerModel->getPanitiaUser($currentUserId, $prokerId);
        $isKoorProker = ($panitiaUser && $panitiaUser['jabatan'] === 'Koordinator');
        $isAdmin      = isAdminRole($_SESSION['role'] ?? 'anggota');
        $detailKeg    = $prokerModel->getDetailKegiatan($idKegiatan);

        $canHapus = $isAdmin || ($isKoorProker && $detailKeg && (int) ($detailKeg['id_divisi'] ?? 0) === (int) ($panitiaUser['id_divisi'] ?? 0));
        if (!$canHapus) {
            setFlash('error', 'Akses ditolak untuk menghapus kegiatan ini.');
            header('Location: kelola.php?proker_id=' . $prokerId);
            exit;
        }

        $prokerModel->deleteKegiatan($idKegiatan);
        setFlash('success', 'Sesi kegiatan berhasil dihapus.');
        header('Location: kelola.php?proker_id=' . $prokerId);
        exit;
    } elseif ($action === 'validasi_presensi') {
        $idPresensi = (int) ($_POST['id_presensi'] ?? 0);
        $keputusan  = $_POST['keputusan'] ?? 'Disetujui';
        if ($keputusan === 'Sah') {
            $keputusan = 'Disetujui';
        }
        $catatan    = trim($_POST['catatan_penolakan'] ?? '');

        if (!in_array($keputusan, ['Disetujui', 'Ditolak'], true)) {
            setFlash('error', 'Keputusan validasi tidak dikenali.');
            header('Location: validasi.php');
            exit;
        }

        if ($keputusan === 'Ditolak' && $catatan === '') {
            setFlash('error', 'Harap tuliskan alasan/catatan penolakan agar anggota dapat memperbaiki presensinya.');
            header('Location: validasi.php');
            exit;
        }

        $berhasil = $presensiModel->validasiPresensiACID($idPresensi, $keputusan, $catatan, $currentUserId);
        if ($berhasil) {
            if ($keputusan === 'Disetujui') {
                setFlash('success', 'Presensi berhasil disetujui.');
            } else {
                setFlash('success', 'Presensi berhasil ditolak.');
            }
        } else {
            setFlash('error', 'Gagal memproses validasi presensi.');
        }

        $prokerId = (int) ($_POST['proker_id'] ?? 0);
        $redirectUrl = $prokerId > 0 ? 'validasi.php?proker_id=' . $prokerId : 'validasi.php';
        header('Location: ' . $redirectUrl);
        exit;
    }
} catch (Throwable $e) {
    setFlash('error', 'Terjadi kesalahan saat memproses data presensi.');
}

header('Location: index.php');
exit;
