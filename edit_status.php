<?php
require 'config.php';
checkLogin();

if(!isDono()){
    die("Acesso negado.");
}

if(isset($_GET['id'], $_GET['status'])){
    $id = $_GET['id'];
    $status = $_GET['status'] === 'atendido' ? 'atendido' : 'agendado';

    $stmt = $pdo->prepare("UPDATE appointments SET status=? WHERE id=?");
    $stmt->execute([$status, $id]);
}

header("Location: index.php");
exit;
