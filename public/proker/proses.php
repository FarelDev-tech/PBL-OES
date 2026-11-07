<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../models/ProkerModel.php';

requireLogin();

$currentUserId = (int) ($_SESSION['user_id'] ?? 0);
$userRole      = $_SESSION['role'] ?? 'anggota';
$isAdmin       = isAdminRole($userRole);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

requireCsrf();

$action   = $_POST['action'] ?? $_POST['aksi'] ?? '';
$idProker = (int) ($_POST['id_proker'] ?? 0);
$prokerModel = new ProkerModel($pdo);

try {
    if ($action === 'tambah_kegiatan') {
        $panitiaUser  = $prokerModel->getPanitiaUser($currentUserId, $idProker);
        $isKoorProker = ($panitiaUser && $panitiaUser['jabatan'] === 'Koordinator');
        if (!$isAdmin && !$isKoorProker) {
            setFlash('error', 'Akses ditolak. Hanya Admin atau Koordinator yang dapat menambahkan kegiatan.');
            header('Location: detail.php?id=' . $idProker . '&tab=kegiatan');
            exit;
        }

        $namaKeg   = trim($_POST['nama_kegiatan'] ?? '');
        $jenisKeg  = $_POST['jenis_kegiatan'] ?? 'Rapat';
        $bobotPoin = max(1, (int) ($_POST['bobot_poin'] ?? 5));
        $idDivisi  = ($isKoorProker && !$isAdmin) ? (int) ($panitiaUser['id_divisi'] ?? 0) : (!empty($_POST['id_divisi']) ? (int) $_POST['id_divisi'] : null);

        if ($namaKeg === '') {
            setFlash('error', 'Nama kegiatan wajib diisi.');
            header('Location: detail.php?id=' . $idProker . '&tab=kegiatan');
            exit;
        }

        $prokerModel->createKegiatan([
            'id_proker'        => $idProker,
            'id_divisi'        => $idDivisi,
            'nama_kegiatan'    => $namaKeg,
            'jenis_kegiatan'   => $jenisKeg,
            'bobot_poin'       => $bobotPoin,
            'tanggal'          => !empty($_POST['tanggal']) ? $_POST['tanggal'] : null,
            'jam_mulai'        => !empty($_POST['jam_mulai']) ? $_POST['jam_mulai'] : null,
            'jam_selesai'      => !empty($_POST['jam_selesai']) ? $_POST['jam_selesai'] : null,
            'pelaksanaan'      => !empty($_POST['pelaksanaan']) ? $_POST['pelaksanaan'] : (!empty($_POST['mode_kegiatan']) ? $_POST['mode_kegiatan'] : null),
            'tempat_atau_link' => !empty($_POST['tempat_atau_link']) ? trim($_POST['tempat_atau_link']) : null,
        ]);
        setFlash('success', 'Kegiatan proker berhasil ditambahkan.');
        header('Location: detail.php?id=' . $idProker . '&tab=kegiatan');
        exit;
    } elseif ($action === 'update_jadwal_kegiatan' || $action === 'atur_pelaksanaan') {
        $idKegiatan   = (int) ($_POST['id_kegiatan'] ?? 0);
        $panitiaUser  = $prokerModel->getPanitiaUser($currentUserId, $idProker);
        $isKoorProker = ($panitiaUser && $panitiaUser['jabatan'] === 'Koordinator');
        $detailKeg    = $prokerModel->getDetailKegiatan($idKegiatan);

        $canKelola = $isAdmin || ($isKoorProker && $detailKeg && (empty($detailKeg['id_divisi']) || (int) ($detailKeg['id_divisi'] ?? 0) === (int) ($panitiaUser['id_divisi'] ?? 0)));
        if (!$canKelola) {
            setFlash('error', 'Akses ditolak. Anda tidak berhak mengatur kegiatan ini.');
            header('Location: detail.php?id=' . $idProker . '&tab=kegiatan');
            exit;
        }

        $tanggal     = $_POST['tanggal'] ?? null;
        $jamMulai    = $_POST['jam_mulai'] ?? null;
        $jamSelesai  = $_POST['jam_selesai'] ?? null;
        $pelaksanaan = $_POST['pelaksanaan'] ?? $_POST['mode_kegiatan'] ?? 'Offline';
        $tempat      = trim($_POST['tempat_atau_link'] ?? '');

        if (empty($tanggal) || empty($jamMulai) || empty($jamSelesai) || empty($tempat)) {
            setFlash('error', 'Harap lengkapi tanggal, jam mulai, jam selesai, dan tempat/tautan kegiatan.');
            header('Location: detail.php?id=' . $idProker . '&tab=kegiatan');
            exit;
        }

        $prokerModel->updateDetailKegiatan($idKegiatan, [
            'tanggal'          => $tanggal,
            'jam_mulai'        => $jamMulai,
            'jam_selesai'      => $jamSelesai,
            'pelaksanaan'      => $pelaksanaan,
            'tempat_atau_link' => $tempat,
        ]);

        setFlash('success', 'Jadwal dan pelaksanaan kegiatan berhasil disimpan.');
        header('Location: detail.php?id=' . $idProker . '&tab=kegiatan');
        exit;
    } elseif ($action === 'hapus_kegiatan') {
        $idKegiatan   = (int) ($_POST['id_kegiatan'] ?? 0);
        $panitiaUser  = $prokerModel->getPanitiaUser($currentUserId, $idProker);
        $isKoorProker = ($panitiaUser && $panitiaUser['jabatan'] === 'Koordinator');
        $detailKeg    = $prokerModel->getDetailKegiatan($idKegiatan);

        $canHapus = $isAdmin || ($isKoorProker && $detailKeg && (empty($detailKeg['id_divisi']) || (int) ($detailKeg['id_divisi'] ?? 0) === (int) ($panitiaUser['id_divisi'] ?? 0)));
        if (!$canHapus) {
            setFlash('error', 'Akses ditolak untuk menghapus kegiatan ini.');
            header('Location: detail.php?id=' . $idProker . '&tab=kegiatan');
            exit;
        }

        $prokerModel->deleteKegiatan($idKegiatan);
        setFlash('success', 'Kegiatan berhasil dihapus.');
        header('Location: detail.php?id=' . $idProker . '&tab=kegiatan');
        exit;
    }

    if (!$isAdmin) {
        setFlash('error', 'Akses ditolak. Tindakan ini memerlukan hak akses Administrator.');
        header('Location: index.php');
        exit;
    }

    if ($action === 'tambah_proker') {
        $namaProker = trim($_POST['nama_proker'] ?? '');
        if ($namaProker === '' || empty($_POST['tanggal_mulai']) || empty($_POST['tanggal_selesai'])) {
            setFlash('error', 'Harap lengkapi nama dan tanggal pelaksanaan program kerja.');
            header('Location: index.php');
            exit;
        }
        $newId = $prokerModel->create([
            'nama_proker'     => $namaProker,
            'periode'         => trim($_POST['periode'] ?? '2026/2027'),
            'deskripsi'       => trim($_POST['deskripsi'] ?? ''),
            'tanggal_mulai'   => $_POST['tanggal_mulai'],
            'tanggal_selesai' => $_POST['tanggal_selesai'],
            'status_proker'   => $_POST['status_proker'] ?? 'Berjalan',
        ]);
        setFlash('success', 'Program kerja baru berhasil dibuat beserta 4 divisi standar.');
        header('Location: detail.php?id=' . $newId);
        exit;
    } elseif ($action === 'edit_proker') {
        $prokerModel->update($idProker, [
            'nama_proker'     => trim($_POST['nama_proker'] ?? ''),
            'periode'         => trim($_POST['periode'] ?? '2026/2027'),
            'deskripsi'       => trim($_POST['deskripsi'] ?? ''),
            'tanggal_mulai'   => $_POST['tanggal_mulai'],
            'tanggal_selesai' => $_POST['tanggal_selesai'],
            'status_proker'   => $_POST['status_proker'] ?? 'Berjalan',
        ]);
        setFlash('success', 'Informasi program kerja berhasil diperbarui.');
        header('Location: index.php');
        exit;
    } elseif ($action === 'hapus_proker') {
        $prokerModel->delete($idProker);
        setFlash('success', 'Program kerja berhasil dihapus.');
        header('Location: index.php');
        exit;
    } elseif ($action === 'tambah_divisi') {
        $namaDiv = trim($_POST['nama_divisi'] ?? '');
        if ($namaDiv !== '') {
            $prokerModel->addDivisi($idProker, $namaDiv);
            setFlash('success', 'Divisi baru berhasil ditambahkan.');
        }
        header('Location: detail.php?id=' . $idProker . '&tab=panitia');
        exit;
    } elseif ($action === 'hapus_divisi') {
        $idDivisi = (int) ($_POST['id_divisi'] ?? 0);
        $prokerModel->deleteDivisi($idProker, $idDivisi);
        setFlash('success', 'Divisi berhasil dihapus.');
        header('Location: detail.php?id=' . $idProker . '&tab=panitia');
        exit;
    } elseif ($action === 'atur_koordinator_divisi') {
        $idDivisi   = (int) ($_POST['id_divisi'] ?? 0);
        $idUserKoor = (int) ($_POST['id_user_koordinator'] ?? 0);

        if ($idDivisi <= 0) {
            setFlash('error', 'Divisi tidak valid.');
            header('Location: detail.php?id=' . $idProker . '&tab=panitia');
            exit;
        }

        $prokerModel->setKoordinatorDivisi($idProker, $idDivisi, $idUserKoor);

        if ($idUserKoor > 0) {
            setFlash('success', 'Koordinator divisi berhasil ditetapkan dan role akun otomatis disinkronkan.');
        } else {
            setFlash('success', 'Koordinator divisi berhasil dikosongkan.');
        }
        header('Location: detail.php?id=' . $idProker . '&tab=panitia');
        exit;
    } elseif ($action === 'tambah_panitia') {
        $userIds  = $_POST['user_ids'] ?? $_POST['id_users'] ?? [];
        $idDivisi = !empty($_POST['id_divisi']) ? (int) $_POST['id_divisi'] : null;
        $jabatan  = in_array($_POST['jabatan'] ?? '', ['Anggota', 'Koordinator'], true) ? $_POST['jabatan'] : 'Anggota';

        if ((!is_array($userIds) || empty($userIds)) && !empty($_POST['id_user_koor'])) {
            $userIds = [(int) $_POST['id_user_koor']];
        }

        if (!is_array($userIds) || empty($userIds)) {
            setFlash('error', 'Pilih minimal 1 anggota untuk ditambahkan ke kepanitiaan.');
            header('Location: detail.php?id=' . $idProker . '&tab=panitia');
            exit;
        }

        $jumlah = $prokerModel->addBatchPanitia($idProker, $userIds, $idDivisi, $jabatan);
        foreach ($userIds as $uid) {
            $prokerModel->syncUserRole((int) $uid);
        }
        setFlash('success', "$jumlah anggota berhasil ditambahkan ke kepanitiaan proker.");
        header('Location: detail.php?id=' . $idProker . '&tab=panitia');
        exit;
    } elseif ($action === 'ubah_panitia' || $action === 'atur_jabatan') {
        $idPanitia = (int) ($_POST['id_panitia'] ?? 0);
        $idDivisi  = !empty($_POST['id_divisi']) ? (int) $_POST['id_divisi'] : null;
        $jabatan   = in_array($_POST['jabatan'] ?? '', ['Anggota', 'Koordinator'], true) ? $_POST['jabatan'] : 'Anggota';

        $idUserPanitia = $prokerModel->getIdUserByPanitia($idPanitia);
        $prokerModel->updateJabatanPanitia($idPanitia, $idDivisi, $jabatan);
        if ($idUserPanitia > 0) {
            $prokerModel->syncUserRole($idUserPanitia);
        }
        setFlash('success', 'Data kepanitiaan anggota berhasil diperbarui.');
        header('Location: detail.php?id=' . $idProker . '&tab=panitia');
        exit;
    } elseif ($action === 'hapus_panitia' || $action === 'keluarkan_panitia') {
        $idPanitia     = (int) ($_POST['id_panitia'] ?? 0);
        $idUserPanitia = $prokerModel->getIdUserByPanitia($idPanitia);
        $prokerModel->deletePanitia($idPanitia);
        if ($idUserPanitia > 0) {
            $prokerModel->syncUserRole($idUserPanitia);
        }
        setFlash('success', 'Anggota berhasil dikeluarkan dari kepanitiaan.');
        header('Location: detail.php?id=' . $idProker . '&tab=panitia');
        exit;
    }
} catch (Throwable $e) {
    setFlash('error', 'Gagal memproses perubahan pada program kerja.');
}

header('Location: index.php');
exit;
