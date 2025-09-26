<?php
require 'config.php';
checkLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $service = $_POST['service'] ?? '';
    $date = $_POST['date'] ?? '';
    $time = $_POST['time'] ?? '';
    $end_time = $_POST['end_time'] ?? '';

    if (empty($service) || empty($date) || empty($time) || empty($end_time)) {
        echo json_encode(['status' => 'error', 'msg' => 'Dados incompletos']);
        exit;
    }

    $user_id = $_SESSION['user_id'];

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM events WHERE date=? AND time=?");
    $stmt->execute([$date, $time]);
    if ($stmt->fetchColumn() > 0) {
        echo json_encode(['status' => 'error', 'msg' => 'Horário já agendado']);
        exit;
    }

    $stmt = $pdo->prepare("INSERT INTO events (user_id, service, date, time, end_time, status) VALUES (?,?,?,?,?,?)");
    $stmt->execute([$user_id, $service, $date, $time, $end_time, 'agendado']);

    echo json_encode(['status' => 'success', 'msg' => 'Agendamento realizado']);
    exit;
}

echo json_encode(['status' => 'error', 'msg' => 'Método inválido']);
