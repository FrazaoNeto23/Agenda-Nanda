<?php
require 'config.php';
checkLogin();

if (!isDono()) {
    die("Acesso negado.");
}

$data = json_decode(file_get_contents("php://input"), true);

$id = $data['id'];
$newDate = $data['date'];
$newTime = $data['time'];

$stmt = $pdo->prepare("UPDATE appointments SET date=?, time=? WHERE id=?");
$stmt->execute([$newDate, $newTime, $id]);

echo json_encode(["success" => true]);
