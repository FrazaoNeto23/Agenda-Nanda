<?php
require 'config.php';
checkLogin();

$stmt = $pdo->query("
    SELECT e.id, e.title, e.start, e.end, e.status 
    FROM events e
    INNER JOIN users u ON u.id = e.user_id
");

$events = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($events);
