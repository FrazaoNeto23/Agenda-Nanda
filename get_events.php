<?php
require 'config.php';
checkLogin();

$stmt = $pdo->query("
    SELECT e.id, e.title, e.start, e.end, e.status
    FROM events e
    INNER JOIN users u ON u.id = e.user_id
");

$events = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $events[] = [
        'id' => $row['id'],
        'title' => $row['title'],
        'start' => $row['start'],
        'end' => $row['end'],
        'status' => strtolower($row['status']), // 🔎 status vai pro extendedProps
        'className' => [strtolower($row['status'])] // 🔎 define a classe CSS no calendário
    ];
}

header('Content-Type: application/json');
echo json_encode($events);
