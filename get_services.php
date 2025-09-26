<?php
require 'config.php';
$stmt = $pdo->query("SELECT * FROM services ORDER BY name");
$services = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($services);
