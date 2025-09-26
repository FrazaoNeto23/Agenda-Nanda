<?php
session_start();

$host = "localhost:3307";
$dbname = "agenda_manicure";
$user = "root";
$pass = ""; // coloque sua senha do MySQL se tiver

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Erro ao conectar: " . $e->getMessage());
}

function checkLogin() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit;
    }
}

function isDono() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'dono';
}
