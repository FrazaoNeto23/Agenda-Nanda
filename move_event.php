<?php
require 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    $date = $_POST['date'];
    $time = $_POST['time'];
    $end_time = $_POST['end_time'];

    $stmt = $pdo->prepare("UPDATE events SET date=?,time=?,end_time=? WHERE id=?");
    $stmt->execute([$date, $time, $end_time, $id]);
    echo json_encode(['status' => 'success']);
}
