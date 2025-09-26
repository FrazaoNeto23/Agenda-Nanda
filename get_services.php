<?php
require 'config.php';
checkLogin();

$stmt = $pdo->query("SELECT id, name FROM services ORDER BY name");
$services = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($services);
