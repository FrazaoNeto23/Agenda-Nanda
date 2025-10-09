<?php
require 'config.php';
checkLogin();

header('Content-Type: application/json');

try {
    $user_role = $_SESSION['role'] ?? '';
    $user_id = $_SESSION['user_id'] ?? 0;

    if ($user_role === 'dono') {
        $stmt = $pdo->query("
            SELECT e.id, e.title, e.start, e.end, e.status, 
                   u.name as user_name, u.email as user_email
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
        $events[] = [
            'id' => (int) $row['id'],
            'title' => htmlspecialchars($row['title']),
            'start' => $row['start'],
            'end' => $row['end'],
            'status' => strtolower($row['status']),
            'user_name' => isset($row['user_name']) ? htmlspecialchars($row['user_name']) : null,
            'user_email' => $row['user_email'] ?? null
        ];
    }

    echo json_encode($events);

} catch (PDOException $e) {
    error_log('Erro ao buscar histórico: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => 'Erro ao carregar histórico',
        'events' => []
    ]);
}