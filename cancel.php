<?php
require_once __DIR__ . '/config.php';
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id) {
    // ensure appointment belongs to user or user is dona
    $stmt = $pdo->prepare('SELECT user_id FROM appointments WHERE id = ?');
    $stmt->execute([$id]);
    $a = $stmt->fetch();
    if ($a && ($a['user_id'] == $_SESSION['user_id'] || $_SESSION['tipo'] === 'dono')) {
        $pdo->prepare('UPDATE appointments SET status = ? WHERE id = ?')->execute(['cancelado', $id]);
    }
}
header('Location: painel_cliente.php');
exit;
?>