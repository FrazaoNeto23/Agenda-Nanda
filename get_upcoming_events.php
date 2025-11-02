<?php
require 'config.php';
checkLogin();

if (!isDono()) {
    http_response_code(403);
    echo json_encode(['error' => 'Acesso negado']);
    exit;
}

header('Content-Type: application/json');

try {
    // Buscar agendamentos confirmados dos próximos 7 dias
    $stmt = $pdo->query("
        SELECT 
            e.id,
            e.title,
            e.start,
            e.end,
            u.name as user_name,
            u.email as user_email,
            u.whatsapp
        FROM events e
        JOIN users u ON u.id = e.user_id
        WHERE e.status = 'agendado'
        AND e.start >= NOW()
        AND e.start <= NOW() + INTERVAL 7 DAY
        ORDER BY e.start ASC
    ");
    
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($events);
    
} catch (PDOException $e) {
    error_log('Erro ao buscar próximos eventos: ' . $e->getMessage());
    echo json_encode([]);
}
