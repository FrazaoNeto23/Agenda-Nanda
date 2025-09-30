<?php
require 'config.php';
checkLogin();

$user_role = $_SESSION['role'] ?? '';
$user_id = $_SESSION['user_id'] ?? 0;

// Se for dono, vê todos os eventos. Se for cliente, vê apenas os seus
if ($user_role === 'dono') {
    $stmt = $pdo->query("
        SELECT e.id, e.title, e.start, e.end, e.status, u.name as user_name
        FROM events e
        LEFT JOIN users u ON u.id = e.user_id
        ORDER BY e.start DESC
    ");
} else {
    $stmt = $pdo->prepare("
        SELECT e.id, e.title, e.start, e.end, e.status
        FROM events e
        WHERE e.user_id = :user_id
        ORDER BY e.start DESC
    ");
    $stmt->execute([':user_id' => $user_id]);
}

$events = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $title = $row['title'];

    // Se for dono, adiciona o nome do cliente ao título
    if ($user_role === 'dono' && !empty($row['user_name'])) {
        $title .= ' - ' . $row['user_name'];
    }

    $events[] = [
        'id' => $row['id'],
        'title' => $title,
        'start' => $row['start'],
        'end' => $row['end'],
        'status' => strtolower($row['status']),
        'extendedProps' => [
            'status' => strtolower($row['status'])
        ]
    ];
}

header('Content-Type: application/json');
echo json_encode($events);