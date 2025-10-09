<?php
require 'config.php';
checkLogin();

header('Content-Type: application/json');

$id = intval($_GET['id'] ?? 0);

if ($id <= 0) {
    echo json_encode(['status' => 'error', 'msg' => 'ID inválido']);
    exit;
}

try {
    $user_role = $_SESSION['role'] ?? '';
    $user_id = $_SESSION['user_id'] ?? 0;

    if ($user_role === 'dono') {
        $stmt = $pdo->prepare("
            SELECT e.*, u.name as user_name, u.email as user_email
            FROM events e
            LEFT JOIN users u ON u.id = e.user_id
            WHERE e.id = :id
        ");
        $stmt->execute([':id' => $id]);
    } else {
        $stmt = $pdo->prepare("
            SELECT e.*, u.name as user_name, u.email as user_email
            FROM events e
            LEFT JOIN users u ON u.id = e.user_id
            WHERE e.id = :id AND e.user_id = :user_id
        ");
        $stmt->execute([':id' => $id, ':user_id' => $user_id]);
    }

    $event = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$event) {
        echo json_encode(['status' => 'error', 'msg' => 'Evento não encontrado']);
        exit;
    }

    // Buscar histórico - verificar se tabela existe
    $historico = [];
    try {
        $stmtHist = $pdo->prepare("
            SELECT h.*, u.name as user_name
            FROM historico h
            LEFT JOIN users u ON u.id = h.user_id
            WHERE h.event_id = :event_id
            ORDER BY h.created_at ASC
        ");
        $stmtHist->execute([':event_id' => $id]);
        $historico = $stmtHist->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Aviso: Tabela historico não existe ainda: ' . $e->getMessage());
        // Continua sem histórico
    }

    echo json_encode([
        'status' => 'success',
        'event' => $event,
        'historico' => $historico
    ]);

} catch (PDOException $e) {
    error_log('Erro ao buscar detalhes: ' . $e->getMessage());
    echo json_encode(['status' => 'error', 'msg' => 'Erro ao carregar detalhes']);
}