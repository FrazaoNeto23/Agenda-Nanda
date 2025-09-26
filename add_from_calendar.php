<?php
require 'config.php';
checkLogin();

$user_id = $_SESSION['user_id'];
$service_id = $_POST['service_id'] ?? null;
$date = $_POST['date'] ?? null;
$time = $_POST['time'] ?? null;
$end_time = $_POST['end_time'] ?? null;

if (!$service_id || !$date || !$time || !$end_time) {
    echo json_encode(['status' => 'error', 'msg' => 'Todos os campos são obrigatórios']);
    exit;
}

$start = $date . ' ' . $time;
$end = $date . ' ' . $end_time;

$stmt = $pdo->prepare("SELECT name FROM services WHERE id=?");
$stmt->execute([$service_id]);
$service = $stmt->fetch();
$title = $service['name'] ?? 'Serviço';

$stmt = $pdo->prepare("INSERT INTO events (user_id, service_id, title, start, end) VALUES (?,?,?,?,?)");
$stmt->execute([$user_id, $service_id, $title, $start, $end]);

echo json_encode(['status' => 'success']);
