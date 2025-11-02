<?php
require 'config.php';
checkLogin();

header('Content-Type: application/json');

$date = sanitizeInput($_GET['date'] ?? '');

if (!$date || !validateDate($date)) {
    echo json_encode(['error' => 'Data inválida']);
    exit;
}

try {
    // Buscar todos os agendamentos do dia
    $stmt = $pdo->prepare("
        SELECT 
            TIME_FORMAT(start, '%H:%i') as hora_inicio,
            TIME_FORMAT(end, '%H:%i') as hora_fim,
            status,
            title
        FROM events
        WHERE DATE(start) = :date
        AND status IN ('pendente', 'agendado', 'bloqueado')
        ORDER BY start ASC
    ");
    
    $stmt->execute([':date' => $date]);
    $slots = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($slots);

} catch (PDOException $e) {
    error_log("Erro ao buscar horários ocupados: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Erro ao buscar horários']);
}
