<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}

function csrfField(): string
{
    return csrf_field();
}

function verifyCsrfToken(): bool
{
    $token = $_POST['csrf_token'] ?? '';
    return !empty($token) && !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function requireCsrf(): void
{
    if (!verifyCsrfToken()) {
        http_response_code(403);
        die('Permintaan ditolak: Token keamanan (CSRF) tidak valid atau sesi telah berakhir.');
    }
}

function verifyCsrfOrDie(): void
{
    requireCsrf();
}
