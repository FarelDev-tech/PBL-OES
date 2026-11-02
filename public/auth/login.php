<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/csrf.php';

// Jika sudah login, langsung lempar ke dashboard
if (!empty($_SESSION['user_id'])) {
    header('Location: ../dashboard/index.php');
    exit;
}

$errorMsg = $_SESSION['login_error'] ?? '';
$oldNim   = $_SESSION['old_nim'] ?? '';
unset($_SESSION['login_error'], $_SESSION['old_nim']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk ke OES — Organization Engagement System</title>
    <link rel="stylesheet" href="../assets/css/design-system.css">
    <link rel="stylesheet" href="../assets/css/tailwind.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="auth-page">
    <!-- Topbar -->
    <header class="auth-topbar">
        <div class="auth-brand">
            <span class="auth-brand-name">Organization Engagement System</span>
        </div>
    </header>

    <!-- Main Hero & Card -->
    <main class="auth-hero">
        <div class="auth-container">
            <!-- Left Hero Text -->
            <div>
                <h1 class="auth-headline">Masuk ke OES</h1>
                <p class="auth-desc">
                    Catat kehadiran dan kontribusimu secara transparan dan terukur.
                </p>
            </div>

            <!-- Right Form Login Card -->
            <div class="auth-card">
                <h2 class="auth-card-title">Form Login</h2>
                <p class="auth-card-sub">Masukkan kredensial akun mahasiswa Anda</p>

                <?php if (!empty($errorMsg)): ?>
                    <div class="auth-error-alert" role="alert">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
                        </svg>
                        <span><?php echo e($errorMsg); ?></span>
                    </div>
                <?php endif; ?>

                <form method="post" action="proses_login.php" autocomplete="off">
                    <?php echo csrfField(); ?>

                    <div class="form-group" style="margin-bottom: 18px;">
                        <label class="form-label" for="nim">NIM</label>
                        <input
                            type="text"
                            name="nim"
                            id="nim"
                            class="form-input"
                            placeholder="Masukkan NIM"
                            value="<?php echo e($oldNim); ?>"
                            required
                            autofocus
                        >
                    </div>

                    <div class="form-group" style="margin-bottom: 22px;">
                        <label class="form-label" for="password">Password</label>
                        <div class="input-group-password">
                            <input
                                type="password"
                                name="password"
                                id="password"
                                class="form-input-pass"
                                placeholder="Masukkan password"
                                required
                            >
                            <button
                                type="button"
                                class="btn-toggle-pass"
                                id="btn-toggle-pass"
                                onclick="togglePassword()"
                                title="Tampilkan / Sembunyikan Password"
                                aria-label="Tampilkan atau sembunyikan password"
                            >
                                <svg id="icon-eye" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                    <circle cx="12" cy="12" r="3"/>
                                </svg>
                                <span id="label-toggle-pass">Tampilkan</span>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary" style="width: 100%; height: 44px; padding: 0 18px; font-size: 15px; font-weight: 600; border-radius: 8px;">
                        Masuk
                    </button>
                </form>

                <div class="auth-footer-help" style="margin-top: 20px; text-align: center; font-size: 13px; color: var(--oes-neutral-500);">
                    <span>Lupa password? <a href="#" style="color: inherit; text-decoration: underline;" onclick="alert('Silakan hubungi Admin Ormawa untuk melakukan reset password akun Anda.'); return false;">Hubungi Admin untuk reset.</a></span>
                </div>
            </div>
        </div>
    </main>
</div>

<script src="../assets/js/app.js"></script>
</body>
</html>
