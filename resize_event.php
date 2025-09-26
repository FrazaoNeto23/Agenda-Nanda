<?php
require 'config.php';
checkLogin();

if (!isDono()) {
    die("Acesso negado.");
}

$data = json_decode(file_get_contents("php://input"), true);

$id = $data['id'];
$newEndTime = $data['end_time'];

$stmt = $pdo->prepare("UPDATE appointments SET end_time=? WHERE id=?");
$stmt->execute([$newEndTime, $id]);

echo json_encode(["success" => true]);
