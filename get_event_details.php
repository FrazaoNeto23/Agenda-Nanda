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
    } else {
        $stmt = $pdo->prepare("
            SELECT e.*, u.name as user_name, u.email as user_email
            FROM events e
            LEFT JOIN users u ON u.id = e.user_id
            WHERE e.id = :id AND e.user_id = :user_id
        ");
        $stmt->bindValue(':user_id', $user_id);
    }

    $stmt->bindValue(':id', $id);
    $stmt->execute();
    $event = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$event) {
        echo json_encode(['status' => 'error', 'msg' => 'Não encontrado']);
        exit;
    }

    $stmtHist = $pdo->prepare("
        SELECT h.*, u.name as user_name
        FROM historico h
        LEFT JOIN users u ON u.id = h.user_id
        WHERE h.event_id = :event_id
        ORDER BY h.created_at ASC
    ");
    $stmtHist->execute([':event_id' => $id]);
    $historico = $stmtHist->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => 'success',
        'event' => $event,
        'historico' => $historico
    ]);

} catch (PDOException $e) {
    error_log('Erro: ' . $e->getMessage());
    echo json_encode(['status' => 'error', 'msg' => 'Erro']);
}