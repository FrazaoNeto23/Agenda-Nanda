<?php
require 'config.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $client = $_POST['client'];
    $date = $_POST['date'];
    $time = $_POST['time'];
    $service = $_POST['service'];

    $stmt = $pdo->prepare("INSERT INTO appointments (client, date, time, service) VALUES (?, ?, ?, ?)");
    $stmt->execute([$client, $date, $time, $service]);

    header("Location: index.php");
    exit;
}
?>