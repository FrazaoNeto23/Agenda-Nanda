<?php
session_start();

$host = 'localhost';
$db   = 'agenda_manicure';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
     $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
     throw new \PDOException($e->getMessage(), (int)$e->getCode());
}

function checkLogin() {
    if(!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit;
    }
}

function isDono() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'dono';
}
