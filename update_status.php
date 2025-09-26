<?php
require 'config.php';
checkLogin();

if (!isDono()) {
    die("Acesso negado.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    $status = $_POST['status'];

    $stmt = $pdo->prepare("UPDATE appointments SET status=? WHERE id=?");
    $stmt->execute([$status, $id]);

    echo json_encode(["success" => true]);
}
