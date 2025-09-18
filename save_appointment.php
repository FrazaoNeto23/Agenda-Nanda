<?php
require_once __DIR__ . '/config.php';
session_start();

$client_name = trim($_POST['client_name'] ?? '');
$phone = trim($_POST['phone'] ?? null);
$service_id = $_POST['service_id'] ?? null;
$date = $_POST['date'] ?? null;
$time = $_POST['time'] ?? null;
$notes = trim($_POST['notes'] ?? null);

if (!$client_name || !$date || !$time) {
    header('Location: painel_cliente.php?msg=' . urlencode('Preencha os campos obrigatórios.'));
    exit;
}

// set service name snapshot (optional)
$service_name = null;
if ($service_id) {
    $s = $pdo->prepare('SELECT nome FROM services WHERE id = ?');
    $s->execute([$service_id]);
    $row = $s->fetch();
    if ($row)
        $service_name = $row['nome'];
}

$stmt = $pdo->prepare('SELECT COUNT(*) FROM appointments WHERE date = ? AND time = ?');
$stmt->execute([$date, $time]);
$exists = $stmt->fetchColumn();
if ($exists) {
    header('Location: painel_cliente.php?msg=' . urlencode('Horário já reservado. Escolha outro horário.'));
    exit;
}

$user_id = $_SESSION['user_id'] ?? null;
$stmt = $pdo->prepare('INSERT INTO appointments (user_id, service_id, client_name, phone, date, time, notes) VALUES (?, ?, ?, ?, ?, ?, ?)');
$stmt->execute([$user_id, $service_id, $client_name, $phone, $date, $time, $notes]);

header('Location: painel_cliente.php?msg=' . urlencode('Agendamento criado com sucesso!'));
exit;
?>