<?php
session_start();

$host = "localhost:3307";
$dbname = "agenda_manicure";
$user = "root";
$pass = ""; // ajuste se tiver senha

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erro na conexão: " . $e->getMessage());
}

require_once "functions.php";
