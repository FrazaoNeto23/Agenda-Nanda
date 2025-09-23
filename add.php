<?php
require 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}
if (!check_csrf($_POST['csrf_token'] ?? '')) {
    $_SESSION['flash'] = 'Token inválido.';
    header('Location: index.php');
    exit;
}

$client_name = trim($_POST['client_name']);
$phone = trim($_POST['phone'] ?? '');
$service = trim($_POST['service']);
$date = $_POST['date'];
$time = $_POST['time'];
$notes = trim($_POST['notes'] ?? '');

// validações básicas
if (!$client_name || !$service || !$date || !$time) {
    $_SESSION['flash'] = 'Preencha os campos obrigatórios.';
    header('Location: index.php');
    exit;
}

$stmt = $pdo->prepare("INSERT INTO appointments (client_name, phone, service, date, time, notes) VALUES (?,?,?,?,?,?)");
$stmt->execute([$client_name, $phone, $service, $date, $time, $notes]);

$_SESSION['flash'] = 'Agendamento criado.';
header('Location: index.php');
exit;