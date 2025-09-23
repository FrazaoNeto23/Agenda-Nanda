<?php
require 'config.php';
checkLogin();

if(!isset($_GET['id'])){
    header("Location: index.php");
    exit;
}

$id = $_GET['id'];

// Verificar se o usuário pode excluir
$stmt = $pdo->prepare("SELECT * FROM appointments WHERE id=?");
$stmt->execute([$id]);
$appointment = $stmt->fetch();

if(!$appointment){
    die("Agendamento não encontrado.");
}

if(!isDono() && $appointment['client_id'] != $_SESSION['user_id']){
    die("Acesso negado.");
}

// Excluir
$stmt = $pdo->prepare("DELETE FROM appointments WHERE id=?");
$stmt->execute([$id]);

header("Location: index.php");
exit;
