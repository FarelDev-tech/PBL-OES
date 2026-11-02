<?php
date_default_timezone_set('Asia/Jakarta');

// Mencegah XSS
function e($value)
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}

// Format angka ribuan (contoh: 1250 -> 1.250)
function formatPoin($angka)
{
    return number_format((int) $angka, 0, ',', '.');
}

// Format tanggal Indonesia singkat (contoh: 2026-10-26 -> 26 Okt 2026)
function formatTanggal($tanggal, $denganHari = false)
{
    if (empty($tanggal)) {
        return 'Belum diatur';
    }
    $bulanIndo = [
        1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
        5 => 'Mei', 6 => 'Jun', 7 => 'Jul', 8 => 'Agu',
        9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des'
    ];
    $hariIndo = [
        'Sunday'    => 'Minggu',
        'Monday'    => 'Senin',
        'Tuesday'   => 'Selasa',
        'Wednesday' => 'Rabu',
        'Thursday'  => 'Kamis',
        'Friday'    => 'Jumat',
        'Saturday'  => 'Sabtu'
    ];

    $ts = strtotime($tanggal);
    if (!$ts) {
        return e($tanggal);
    }

    $tgl = (int) date('j', $ts);
    $bln = $bulanIndo[(int) date('n', $ts)] ?? '';
    $thn = date('Y', $ts);

    if ($denganHari) {
        $hari = $hariIndo[date('l', $ts)] ?? '';
        return "$hari, $tgl $bln $thn";
    }
    return "$tgl $bln $thn";
}

// Format jam (contoh: "2026-11-01 20:29:33" -> 20:29 WIB)
function formatJam($mulai, $selesai = null)
{
    if (empty($mulai)) {
        return 'Jam belum diatur';
    }

    $parseJam = function ($val) {
        if (empty($val)) return '';
        $val = trim((string) $val);
        if (strpos($val, '-') !== false || strlen($val) > 8) {
            $ts = strtotime($val);
            if ($ts !== false) return date('H:i', $ts);
        }
        return substr($val, 0, 5);
    };

    $m = $parseJam($mulai);
    if (!empty($selesai)) {
        $s = $parseJam($selesai);
        return "$m – $s WIB";
    }
    return "$m WIB";
}

// Inisial Nama (contoh: "Farel Maulana" -> "FM") untuk avatar
function inisialNama($nama)
{
    $kata = preg_split('/\s+/', trim((string) $nama));
    if (count($kata) >= 2) {
        return strtoupper(substr($kata[0], 0, 1) . substr($kata[1], 0, 1));
    }
    return strtoupper(substr($nama, 0, 2));
}

// Render Komponen Badge Status Solid Fill
function renderBadge($teks, $tipe = 'auto')
{
    $labelTampil = ($teks === 'Sah') ? 'Disetujui' : $teks;

    if ($tipe === 'auto') {
        $map = [
            'Sah'                 => 'badge-success',
            'Disetujui'           => 'badge-success',
            'Berjalan'            => 'badge-success',
            'Berlangsung'         => 'badge-success',
            'Aktif'               => 'badge-dark',
            'Menunggu'            => 'badge-warning',
            'Menunggu Validasi'   => 'badge-warning',
            'Ditolak'             => 'badge-danger',
            'Belum Presensi'      => 'badge-neutral',
            'Online'              => 'badge-primary',
            'Offline'             => 'badge-neutral',
            'Rapat'               => 'badge-dark',
            'Hari-H'              => 'badge-primary-dark',
            'Selesai'             => 'badge-neutral',
            'Nonaktif'            => 'badge-neutral',
            'Koreksi Admin'       => 'badge-primary-dark',
        ];
        $kelas = $map[$teks] ?? 'badge-neutral';
    } else {
        $kelas = 'badge-' . $tipe;
    }

    return '<span class="oes-badge ' . e($kelas) . '">' . e($labelTampil) . '</span>';
}

// Label Role Bahasa Indonesia
function labelRole($role)
{
    $map = [
        'anggota'     => 'Anggota',
        'koordinator' => 'Koordinator',
        'admin'       => 'Admin',
        'super_admin' => 'Super Admin',
    ];
    return $map[$role] ?? ucfirst((string) $role);
}

// Flash Message Session
function setFlash($type, $pesan)
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['flash'] = [
        'type'  => $type,
        'pesan' => $pesan,
    ];
}
