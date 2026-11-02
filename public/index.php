<?php
// Entry web di folder public/
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['user_id'])) {
    header('Location: /auth/login.php');
    exit;
}

header('Location: /dashboard/index.php');
exit;