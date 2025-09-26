<?php
require 'config.php';
checkLogin();

$id = $_POST['id'] ?? null;
$status = $_POST['status'] ?? null;

if (!$id || !$status) {
    exit;
}

$stmt = $pdo->prepare("UPDATE events SET status=? WHERE id=?");
$stmt->execute([$status, $id]);
