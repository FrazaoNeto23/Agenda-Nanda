<?php
require 'config.php';

$id = intval($_GET['id'] ?? 0);
if ($id) {
    $stmt = $pdo->prepare("DELETE FROM appointments WHERE id=?");
    $stmt->execute([$id]);
    $_SESSION['flash']='Agendamento excluído.';
}
header('Location:index.php'); exit;