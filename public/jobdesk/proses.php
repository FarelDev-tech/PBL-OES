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

$back = '../proker/detail.php?id=' . $idProker . '&tab=jobdesk' . (!empty($_POST['div']) ? '&div=' . (int) $_POST['div'] : '');
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
$isKoor       = (!$isAdmin && $panitiaUser && $panitiaUser['jabatan'] === 'Koordinator');
$idDivisiKoor = $isKoor ? (int) ($panitiaUser['id_divisi'] ?? 0) : 0;

try {
    // Jobdesk milik proker ini (dipakai edit/hapus/atur penerima)
    $ambilJobdesk = function () use ($jobdeskModel, $idProker, $back) {
        $j = $jobdeskModel->getById((int) ($_POST['id_jobdesk'] ?? 0));
        if (!$j || (int) $j['id_proker'] !== $idProker) {
            kembali('error', 'Jobdesk tidak ditemukan.', $back);
        }
        if ($j['status_kanban'] === 'Closed') {
            kembali('error', 'Jobdesk yang sudah Closed tidak dapat diubah.', $back);
        }
        return $j;
    };

    // ---- Admin / Super Admin: tambah, edit, hapus jobdesk (tanpa penerima)
    if (in_array($action, ['tambah_jobdesk', 'edit_jobdesk', 'hapus_jobdesk'], true)) {
        if (!$isAdmin) {
            kembali('error', 'Akses ditolak. Hanya Admin yang dapat menambah, mengubah, atau menghapus jobdesk.', $back);
        }

        if ($action === 'hapus_jobdesk') {
            $ambilJobdesk();
            $jobdeskModel->delete((int) $_POST['id_jobdesk']);
            kembali('success', 'Jobdesk berhasil dihapus.', $back);
        }

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

        $idDivisi = (int) ($_POST['id_divisi'] ?? 0);
        $divisiValid = array_filter($prokerModel->getDivisi($idProker), fn($x) => (int) $x['id_divisi'] === $idDivisi);
        if (!$divisiValid) {
            kembali('error', 'Pilih divisi untuk jobdesk ini.', $back);
        }

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
            $jobdeskModel->create($data);
            kembali('success', 'Jobdesk berhasil ditambahkan. Koordinator divisi dapat menugaskannya ke anggota.', $back);
        }

        $ambilJobdesk();
        $jobdeskModel->update((int) $_POST['id_jobdesk'], $data);
        kembali('success', 'Jobdesk berhasil diperbarui.', $back);
    }

    // ---- Koordinator: atur penerima jobdesk divisinya
    if ($action === 'atur_penerima') {
        if (!$isKoor || $idDivisiKoor <= 0) {
            kembali('error', 'Akses ditolak. Hanya Koordinator divisi yang dapat menugaskan anggota.', $back);
        }
        $job = $ambilJobdesk();
        if ((int) ($job['id_divisi'] ?? 0) !== $idDivisiKoor) {
            kembali('error', 'Akses ditolak. Jobdesk ini bukan milik divisi Anda.', $back);
        }
        $penerima = $jobdeskModel->filterPenerima($idProker, $idDivisiKoor, (array) ($_POST['user_ids'] ?? []));
        $jobdeskModel->setPenerima((int) $job['id_jobdesk'], $penerima);
        kembali('success', 'Penerima jobdesk berhasil disimpan.', $back);
    }

    kembali('error', 'Aksi tidak dikenali.', $back);
} catch (Throwable $e) {
    kembali('error', 'Gagal memproses jobdesk.', $back);
}