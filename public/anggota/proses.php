<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../models/AnggotaModel.php';

requireRole(['admin', 'super_admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

requireCsrf();

$action        = $_POST['action'] ?? '';
$currentUserId = (int) ($_SESSION['user_id'] ?? 0);
$currentRole   = $_SESSION['role'] ?? 'admin';
$anggotaModel  = new AnggotaModel($pdo);

try {
    if ($action === 'tambah_anggota') {
        $nim   = trim($_POST['nim'] ?? '');
        $nama  = trim($_POST['nama'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $roleInput = $_POST['role'] ?? 'anggota';
        $role      = ($roleInput === 'admin' && $currentRole === 'super_admin') ? 'admin' : 'anggota';

        if ($roleInput === 'admin' && $currentRole !== 'super_admin') {
            setFlash('error', 'Hanya Super Admin yang dapat membuat akun Admin baru.');
            header('Location: index.php');
            exit;
        }

        if ($nim === '' || $nama === '' || $email === '') {
            setFlash('error', 'Harap lengkapi NIM, Nama, dan Email.');
            header('Location: index.php');
            exit;
        }

        if ($anggotaModel->checkDuplicate($nim, $email)) {
            setFlash('error', 'NIM atau Email sudah terdaftar pada sistem.');
            header('Location: index.php');
            exit;
        }

        $anggotaModel->create([
            'nim'      => $nim,
            'nama'     => $nama,
            'email'    => $email,
            'role'     => $role,
            'password' => $nim,
        ]);

        setFlash('success', "Akun $nama ($nim) berhasil ditambahkan. Password awal adalah NIM.");
    } elseif ($action === 'ubah_role') {
        $idUser   = (int) ($_POST['id_user'] ?? 0);
        $roleBaru = ($_POST['role_baru'] ?? '') === 'admin' ? 'admin' : 'anggota';

        if ($idUser === $currentUserId) {
            setFlash('error', 'Anda tidak dapat mengubah role akun Anda sendiri.');
            header('Location: index.php');
            exit;
        }

        if ($roleBaru === 'admin' && $currentRole !== 'super_admin') {
            setFlash('error', 'Hanya Super Admin yang dapat mengangkat Admin.');
            header('Location: index.php');
            exit;
        }

        $anggotaModel->updateRole($idUser, $roleBaru);
        setFlash('success', 'Peran anggota berhasil diperbarui.');
    } elseif ($action === 'toggle_status') {
        $idUser     = (int) ($_POST['id_user'] ?? 0);
        $statusBaru = $_POST['status_baru'] ?? 'Aktif';

        if ($idUser === $currentUserId) {
            setFlash('error', 'Anda tidak dapat menonaktifkan akun Anda sendiri.');
            header('Location: index.php');
            exit;
        }

        $anggotaModel->toggleStatus($idUser, $statusBaru);
        setFlash('success', "Status akun berhasil diubah menjadi $statusBaru.");
    } elseif ($action === 'reset_password') {
        $idUser  = (int) ($_POST['id_user'] ?? 0);
        $anggota = $anggotaModel->getById($idUser);

        if (!$anggota) {
            setFlash('error', 'Data anggota tidak ditemukan.');
            header('Location: index.php');
            exit;
        }

        $anggotaModel->resetPassword($idUser, $anggota['nim']);
        setFlash('success', "Password akun {$anggota['nama']} berhasil direset kembali ke NIM ({$anggota['nim']}).");
    } elseif ($action === 'koreksi_poin') {
        $idUser     = (int) ($_POST['id_user'] ?? 0);
        $idProker   = (int) ($_POST['id_proker'] ?? 0);
        $poinDelta  = (int) ($_POST['poin_delta'] ?? 0);
        $keterangan = trim($_POST['keterangan'] ?? '');

        if ($idUser <= 0 || $poinDelta === 0 || $keterangan === '') {
            setFlash('error', 'Nilai perubahan poin dan alasan koreksi wajib diisi.');
            header('Location: index.php');
            exit;
        }

        $anggotaModel->koreksiPoin($idUser, $idProker, $poinDelta, $keterangan);
        setFlash('success', 'Koreksi poin anggota berhasil dicatat ke riwayat poin.');
    } elseif ($action === 'hapus_admin') {
        if ($currentRole !== 'super_admin') {
            setFlash('error', 'Hanya Super Admin yang memiliki wewenang menghapus akun Admin.');
            header('Location: index.php');
            exit;
        }

        $idUser = (int) ($_POST['id_user'] ?? 0);
        if ($idUser === $currentUserId) {
            setFlash('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
            header('Location: index.php');
            exit;
        }

        $ok = $anggotaModel->deleteAdmin($idUser, $currentUserId);
        if ($ok) {
            setFlash('success', 'Akun Admin berhasil dihapus secara permanen.');
        } else {
            setFlash('error', 'Gagal menghapus akun Admin.');
        }
    } elseif ($action === 'hapus_anggota') {
        if (!in_array($currentRole, ['admin', 'super_admin'], true)) {
            setFlash('error', 'Akses ditolak.');
            header('Location: index.php');
            exit;
        }

        $idUser = (int) ($_POST['id_user'] ?? 0);
        if ($idUser === $currentUserId) {
            setFlash('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
            header('Location: index.php');
            exit;
        }

        $target = $anggotaModel->getById($idUser);
        if (!$target || $target['role'] !== 'anggota') {
            setFlash('error', 'Akun anggota tidak ditemukan atau bukan berstatus Anggota.');
            header('Location: index.php');
            exit;
        }

        $ok = $anggotaModel->deleteAnggota($idUser);
        if ($ok) {
            setFlash('success', "Akun Anggota {$target['nama']} ({$target['nim']}) berhasil dihapus secara permanen.");
        } else {
            setFlash('error', 'Gagal menghapus akun Anggota.');
        }
    }
} catch (Throwable $e) {
    setFlash('error', 'Terjadi kesalahan saat memproses data anggota.');
}

header('Location: index.php');
exit;
