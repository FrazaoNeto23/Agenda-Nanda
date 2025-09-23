<?php
// config.php - ajuste as credenciais conforme seu ambiente
session_start();

$db_host = 'localhost:3307';
$db_name = 'agenda_manicure';
$db_user = 'root';
$db_pass = '';

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (Exception $e) {
    die('Erro ao conectar ao banco: ' . $e->getMessage());
}

// CSRF simples
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
function csrf_token() { return $_SESSION['csrf_token']; }
function check_csrf($token) { return hash_equals($_SESSION['csrf_token'] ?? '', $token ?? ''); }