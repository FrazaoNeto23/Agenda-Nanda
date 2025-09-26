<?php
session_start();

$host = "localhost:3307";
$db = "agenda_manicure";
$user = "root";
$pass = ""; // ajuste se usar senha no XAMPP

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erro ao conectar: ".$e->getMessage());
}

function checkLogin(){
    if(!isset($_SESSION['user_id'])){
        header("Location: login.php");
        exit;
    }
}
