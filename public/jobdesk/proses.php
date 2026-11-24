<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../models/ProkerModel.php';
require_once __DIR__ . '/../../models/JobdeskModel.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../proker/index.php');
    exit;
}
requireCsrf();

$action        = $_POST['action'] ?? $_POST['aksi'] ?? '';
$idProker      = (int) ($_POST['id_proker'] ?? 0);
$currentUserId = (int) ($_SESSION['user_id'] ?? 0);
$isAdmin       = isAdminRole($_SESSION['role'] ?? 'anggota');

$back = '../proker/detail.php?id=' . $idProker . '&tab=jobdesk';
function kembali(string $type, string $pesan, string $url): void
{
    setFlash($type, $pesan);
    header('Location: ' . $url);
    exit;
}

$prokerModel  = new ProkerModel($pdo);
$jobdeskModel = new JobdeskModel($pdo);

if (!$prokerModel->getById($idProker)) {
    kembali('error', 'Program kerja tidak ditemukan.', '../proker/index.php');
}

$panitiaUser  = $prokerModel->getPanitiaUser($currentUserId, $idProker);
$isKoor       = ($panitiaUser && $panitiaUser['jabatan'] === 'Koordinator');
$idDivisiKoor = $isKoor ? (int) ($panitiaUser['id_divisi'] ?? 0) : 0;

if (!$isAdmin && !($isKoor && $idDivisiKoor > 0)) {
    kembali('error', 'Akses ditolak. Hanya Admin atau Koordinator divisi yang dapat mengelola jobdesk.', $back);
}

try {
    if ($action === 'tambah_jobdesk' || $action === 'edit_jobdesk') {
        $judul = trim($_POST['judul'] ?? '');
        if ($judul === '') {
            kembali('error', 'Judul jobdesk wajib diisi.', $back);
        }

        $deadline = trim($_POST['deadline'] ?? '');
        if ($deadline !== '') {
            $d = DateTime::createFromFormat('Y-m-d', $deadline);
            if (!$d || $d->format('Y-m-d') !== $deadline) {
                kembali('error', 'Format tenggat waktu tidak valid.', $back);
            }
        }

        $bobot = (int) ($_POST['bobot_poin'] ?? 0);
        if ($bobot < 0 || $bobot > 500) {
            kembali('error', 'Bobot poin harus antara 0 dan 500.', $back);
        }

        // Divisi: Koordinator dikunci ke divisinya; Admin bebas (kosong = semua divisi)
        if ($isAdmin) {
            $idDivisi = !empty($_POST['id_divisi']) ? (int) $_POST['id_divisi'] : null;
            if ($idDivisi) {
                $ok = array_filter($prokerModel->getDivisi($idProker), fn($x) => (int) $x['id_divisi'] === $idDivisi);
                if (!$ok) {
                    kembali('error', 'Divisi tidak valid untuk proker ini.', $back);
                }
            }
        } else {
            $idDivisi = $idDivisiKoor;
        }

        $penerima = $jobdeskModel->filterPenerima($idProker, (array) ($_POST['user_ids'] ?? []), $idDivisi);
        $data = [
            'id_proker'   => $idProker,
            'id_divisi'   => $idDivisi,
            'judul'       => $judul,
            'deskripsi'   => trim($_POST['deskripsi'] ?? '') ?: null,
            'deadline'    => $deadline !== '' ? $deadline : null,
            'bobot_poin'  => $bobot,
            'dibuat_oleh' => $currentUserId,
        ];

        if ($action === 'tambah_jobdesk') {
            $jobdeskModel->create($data, $penerima);
            kembali('success', 'Jobdesk berhasil ditambahkan.', $back);
        }

        $idJobdesk = (int) ($_POST['id_jobdesk'] ?? 0);
        $lama      = $jobdeskModel->getById($idJobdesk);
        if (!$lama || (int) $lama['id_proker'] !== $idProker) {
            kembali('error', 'Jobdesk tidak ditemukan.', $back);
        }
        if (!$isAdmin && (int) ($lama['id_divisi'] ?? 0) !== $idDivisiKoor) {
            kembali('error', 'Akses ditolak. Jobdesk ini bukan milik divisi Anda.', $back);
        }
        if ($lama['status_kanban'] === 'Closed') {
            kembali('error', 'Jobdesk yang sudah Closed tidak dapat diubah.', $back);
        }
        $jobdeskModel->update($idJobdesk, $data, $penerima);
        kembali('success', 'Jobdesk berhasil diperbarui.', $back);
    }

    if ($action === 'hapus_jobdesk') {
        $idJobdesk = (int) ($_POST['id_jobdesk'] ?? 0);
        $lama      = $jobdeskModel->getById($idJobdesk);
        if (!$lama || (int) $lama['id_proker'] !== $idProker) {
            kembali('error', 'Jobdesk tidak ditemukan.', $back);
        }
        if (!$isAdmin && (int) ($lama['id_divisi'] ?? 0) !== $idDivisiKoor) {
            kembali('error', 'Akses ditolak. Jobdesk ini bukan milik divisi Anda.', $back);
        }
        if ($lama['status_kanban'] === 'Closed') {
            kembali('error', 'Jobdesk yang sudah Closed tidak dapat dihapus karena poinnya sudah tercatat.', $back);
        }
        $jobdeskModel->delete($idJobdesk);
        kembali('success', 'Jobdesk berhasil dihapus.', $back);
    }

    kembali('error', 'Aksi tidak dikenali.', $back);
} catch (Throwable $e) {
    kembali('error', 'Gagal memproses jobdesk.', $back);
}
