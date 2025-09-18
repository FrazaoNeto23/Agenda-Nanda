<?php
require_once __DIR__ . '/config.php';
$pdo = connectDB();

$client_name = trim($_POST['client_name'] ?? '');
$phone = trim($_POST['phone'] ?? null);
$service = trim($_POST['service'] ?? 'Serviço');
$date = $_POST['date'] ?? null;
$time = $_POST['time'] ?? null;
$notes = trim($_POST['notes'] ?? null);

if(!$client_name || !$date || !$time || !$service) {
    header('Location: index.php?msg=' . urlencode('Preencha os campos obrigatórios.'));
    exit;
}

$stmt = $pdo->prepare('SELECT COUNT(*) FROM appointments WHERE date = ? AND time = ?');
$stmt->execute([$date, $time]);
$exists = $stmt->fetchColumn();
if($exists) {
    header('Location: index.php?msg=' . urlencode('Horário já reservado. Por favor escolha outro horário.'));
    exit;
}

$stmt = $pdo->prepare('INSERT INTO appointments (client_name, phone, service, date, time, notes) VALUES (?, ?, ?, ?, ?, ?)');
$stmt->execute([$client_name, $phone, $service, $date, $time, $notes]);

header('Location: index.php?msg=' . urlencode('Agendamento enviado com sucesso!'));
exit;
?>