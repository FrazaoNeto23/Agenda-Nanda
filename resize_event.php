<?php
require 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    $end_time = $_POST['end_time'];

    $stmt = $pdo->prepare("UPDATE events SET end_time=? WHERE id=?");
    $stmt->execute([$end_time, $id]);
    echo json_encode(['status' => 'success']);
}
