<?php
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/auth.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

requireLogin();

$isSuccess = isset($_GET['sukses']) && $_GET['sukses'] === '1';
$errorMsg  = $_SESSION['pass_error'] ?? '';
$oldPass   = $_SESSION['old_new_pass'] ?? '';
unset($_SESSION['pass_error'], $_SESSION['old_new_pass']);

$tujuanRedirect = '../dashboard/index.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ganti Password — Sistem OES</title>
    <link rel="stylesheet" href="../assets/css/tailwind.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .strength-bars {
            display: flex;
            gap: 6px;
            margin-top: 8px;
            margin-bottom: 6px;
        }
        .strength-bar {
            flex: 1;
            height: 4px;
            background-color: var(--oes-neutral-200);
            border-radius: 2px;
            transition: background-color 0.2s ease;
        }
        .strength-bar.active-weak {
            background-color: #dc2626;
        }
        .strength-bar.active-medium {
            background-color: #d97706;
        }
        .strength-bar.active-strong {
            background-color: #16a34a;
        }
        .strength-meta {
            display: flex;
            justify-content: space-between;
            font-size: 11px;
            color: var(--oes-neutral-500);
            margin-bottom: 12px;
        }
        .pass-checklist {
            display: flex;
            flex-direction: column;
            gap: 4px;
            font-size: 12px;
            color: var(--oes-neutral-600);
            margin-bottom: 16px;
            background-color: var(--oes-canvas-50);
            padding: 10px 12px;
            border-radius: var(--radius-md);
        }
        .pass-check-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .pass-check-item.valid {
            color: #16a34a;
            font-weight: 500;
        }
        .pass-check-box {
            width: 14px;
            height: 14px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            background: #cbd5e1;
            color: #fff;
        }
        .pass-check-item.valid .pass-check-box {
            background: #16a34a;
        }
    </style>
</head>
<body class="auth-body">
<div class="auth-page">
    <!-- Topbar Navigation -->
    <header class="auth-topbar">
        <div class="auth-brand">
            <span class="auth-brand-name">Organization Engagement System</span>
        </div>
        <div>
            <a href="logout.php" class="btn btn-outline btn-sm" style="padding: 6px 14px; font-size: 13px;">
                Keluar
            </a>
        </div>
    </header>

    <!-- Main Container -->
    <main class="auth-hero">
        <div class="auth-container">
            <!-- Left Info Panel -->
            <div class="auth-intro">
                <span class="oes-badge badge-neutral" style="margin-bottom: 12px; font-size: 11px; padding: 4px 10px;">
                    Keamanan Akun Mahasiswa
                </span>
                <h1 class="auth-title">Buat Password Baru</h1>
                <p class="auth-desc">
                    Perbarui kata sandi akun Anda untuk melindungi hak akses dan rekapitulasi data poin presensi.
                </p>
            </div>

            <!-- Right Form Card -->
            <div class="auth-card" style="max-width: 440px;">
                <?php if ($isSuccess): ?>
                    <h2 class="auth-card-title">Password Berhasil Diubah</h2>
                    <p class="auth-card-sub" style="margin-bottom: 20px;">Kredensial baru Anda telah tersimpan dengan aman.</p>

                    <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: var(--radius-lg); padding: 16px; margin-bottom: 20px; text-align: center;">
                        <div style="font-size: 28px; margin-bottom: 6px;">✓</div>
                        <div style="font-weight: 600; color: #166534; font-size: 15px;">Pembaruan Berhasil!</div>
                        <div style="font-size: 13px; color: #15803d; margin-top: 4px;">Anda akan dialihkan otomatis ke Dashboard...</div>
                    </div>

                    <a href="<?php echo e($tujuanRedirect); ?>" class="btn btn-primary" style="width: 100%; justify-content: center;">
                        Lanjut ke Dashboard &rsaquo;
                    </a>

                    <script>
                        setTimeout(function () {
                            window.location.href = '<?php echo e($tujuanRedirect); ?>';
                        }, 2000);
                    </script>
                <?php else: ?>
                    <h2 class="auth-card-title">Form Ganti Password</h2>
                    <p class="auth-card-sub">Masukkan password baru dengan kombinasi yang aman</p>

                    <?php if (!empty($errorMsg)): ?>
                        <div class="auth-error-alert" style="margin-bottom: 16px;">
                            <span><?php echo e($errorMsg); ?></span>
                        </div>
                    <?php endif; ?>

                    <form method="post" action="proses_ganti_password.php" autocomplete="off">
                        <?php echo csrfField(); ?>

                        <div class="form-group" style="margin-bottom: 14px;">
                            <label class="form-label" for="password_baru">Password Baru *</label>
                            <div class="input-group-password">
                                <input
                                    type="password"
                                    name="password_baru"
                                    id="password_baru"
                                    class="form-input-pass"
                                    placeholder="Minimal 8 karakter"
                                    value="<?php echo e($oldPass); ?>"
                                    oninput="evaluasiKekuatanPassword(this.value)"
                                    required
                                    autofocus
                                >
                                <button type="button" class="btn-toggle-pass" onclick="togglePassVisibility('password_baru', this)">
                                    Tampilkan
                                </button>
                            </div>
                        </div>

                        <!-- Strength Meter 3 Bar -->
                        <div class="strength-bars">
                            <div class="strength-bar" id="bar-1"></div>
                            <div class="strength-bar" id="bar-2"></div>
                            <div class="strength-bar" id="bar-3"></div>
                        </div>
                        <div class="strength-meta">
                            <span>Tingkat kekuatan:</span>
                            <strong id="strength-label" style="color: var(--oes-neutral-700);">Belum diisi</strong>
                        </div>

                        <!-- Checklist Syarat Password -->
                        <div class="pass-checklist">
                            <div class="pass-check-item" id="check-len">
                                <span class="pass-check-box">&#10003;</span>
                                <span>Minimal 8 karakter</span>
                            </div>
                            <div class="pass-check-item" id="check-alnum">
                                <span class="pass-check-box">&#10003;</span>
                                <span>Kombinasi huruf dan angka</span>
                            </div>
                        </div>

                        <div class="form-group" style="margin-bottom: 22px;">
                            <label class="form-label" for="konfirmasi_password">Konfirmasi Password Baru *</label>
                            <div class="input-group-password">
                                <input
                                    type="password"
                                    name="konfirmasi_password"
                                    id="konfirmasi_password"
                                    class="form-input-pass"
                                    placeholder="Ulangi password baru"
                                    required
                                >
                                <button type="button" class="btn-toggle-pass" onclick="togglePassVisibility('konfirmasi_password', this)">
                                    Tampilkan
                                </button>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary" style="width: 100%; height: 44px; font-size: 14px; font-weight: 600; border-radius: 8px;">
                            Simpan Password Baru
                        </button>
                    </form>

                    <div style="margin-top: 18px; text-align: center;">
                        <a href="../dashboard/index.php" style="font-size: 13px; color: var(--oes-neutral-500); text-decoration: none;">
                            &larr; Kembali ke Dashboard
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

<script>
function togglePassVisibility(inputId, btn) {
    const input = document.getElementById(inputId);
    if (!input) return;
    if (input.type === 'password') {
        input.type = 'text';
        btn.textContent = 'Sembunyikan';
    } else {
        input.type = 'password';
        btn.textContent = 'Tampilkan';
    }
}

function evaluasiKekuatanPassword(val) {
    const hasLen   = val.length >= 8;
    const hasAlnum = /[a-zA-Z]/.test(val) && /[0-9]/.test(val);
    const hasUpper = /[A-Z]/.test(val) && /[a-z]/.test(val) && val.length >= 10;

    const checkLen   = document.getElementById('check-len');
    const checkAlnum = document.getElementById('check-alnum');
    const bar1       = document.getElementById('bar-1');
    const bar2       = document.getElementById('bar-2');
    const bar3       = document.getElementById('bar-3');
    const label      = document.getElementById('strength-label');

    if (!checkLen) return;

    checkLen.classList.toggle('valid', hasLen);
    checkAlnum.classList.toggle('valid', hasAlnum);

    bar1.className = 'strength-bar';
    bar2.className = 'strength-bar';
    bar3.className = 'strength-bar';

    if (val.length === 0) {
        label.textContent = 'Belum diisi';
    } else if (!hasLen || !hasAlnum) {
        bar1.classList.add('active-weak');
        label.textContent = 'Lemah';
    } else if (hasLen && hasAlnum && !hasUpper) {
        bar1.classList.add('active-medium');
        bar2.classList.add('active-medium');
        label.textContent = 'Sedang';
    } else {
        bar1.classList.add('active-strong');
        bar2.classList.add('active-strong');
        bar3.classList.add('active-strong');
        label.textContent = 'Kuat';
    }
}
</script>
</body>
</html>
