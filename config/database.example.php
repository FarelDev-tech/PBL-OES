<?php
/**
 * Konfigurasi Database PostgreSQL OES (Template)
 * Salin berkas ini menjadi 'database.php' lalu sesuaikan kredensial server Anda.
 */

$host     = 'localhost';
$port     = '5432';
$dbname   = 'oes_db';
$username = 'postgres';
$password = 'password_anda';

try {
    $pdo = new PDO(
        "pgsql:host=$host;port=$port;dbname=$dbname",
        $username,
        $password
    );

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Koneksi database gagal: " . $e->getMessage());
}
