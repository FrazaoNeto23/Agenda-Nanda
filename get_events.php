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
            WHERE e.user_id = :user_id AND e.status != 'cancelado'
            ORDER BY e.start DESC
        ");
        $stmt->execute([':user_id' => $user_id]);
    }

    $events = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $title = htmlspecialchars($row['title']);
        $status = strtolower($row['status']);

        // Se for dono, adiciona o nome do cliente e indicador de pendente
        if ($user_role === 'dono') {
            if (!empty($row['user_name'])) {
                $title .= ' - ' . htmlspecialchars($row['user_name']);
            }
            if ($status === 'pendente') {
                $title = '⏳ ' . $title . ' (PENDENTE)';
            }
        }

        $events[] = [
            'id' => $row['id'],
            'title' => $title,
            'start' => $row['start'],
            'end' => $row['end'],
            'status' => $status,
            'extendedProps' => [
                'status' => $status,
                'user_email' => $row['user_email'] ?? null
            ]
        ];
    }

    echo json_encode($events);

} catch (PDOException $e) {
    error_log('Erro ao buscar eventos: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => 'Erro ao carregar eventos',
        'events' => []
    ]);
}