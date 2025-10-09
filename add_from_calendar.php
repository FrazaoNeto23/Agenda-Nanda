<?php
require 'config.php';
checkLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $service_id = intval($_POST['service_id'] ?? 0);
    $date = $_POST['date'] ?? '';
    $time = $_POST['time'] ?? '';
    $end_time = $_POST['end_time'] ?? '';

    if (!$service_id || !$date || !$time) {
        echo json_encode(['status' => 'error', 'msg' => 'Dados incompletos.']);
        exit;
    }

    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        echo json_encode(['status' => 'error', 'msg' => 'Formato de data inválido.']);
        exit;
    }

    if (!preg_match('/^\d{2}:\d{2}$/', $time)) {
        echo json_encode(['status' => 'error', 'msg' => 'Formato de horário inválido.']);
        exit;
    }

    $start_datetime = $date . ' ' . $time;
    if (strtotime($start_datetime) < time()) {
        echo json_encode(['status' => 'error', 'msg' => 'Não é possível agendar em horários passados.']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("SELECT name, duration FROM services WHERE id = :id");
        $stmt->execute([':id' => $service_id]);
        $service = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$service) {
            echo json_encode(['status' => 'error', 'msg' => 'Serviço não encontrado.']);
            exit;
        }

        $title = $service['name'];

        if (empty($end_time)) {
            $duration = intval($service['duration'] ?? 60);
            $end_time = date('H:i', strtotime($time) + ($duration * 60));
        }

        $start = $date . ' ' . $time;
        $end = $date . ' ' . $end_time;

        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM events 
            WHERE status != 'cancelado' 
            AND (
                (start <= :start AND end > :start) OR
                (start < :end AND end >= :end) OR
                (start >= :start AND end <= :end)
            )
        ");
        $stmt->execute([':start' => $start, ':end' => $end]);

        if ($stmt->fetchColumn() > 0) {
            echo json_encode(['status' => 'error', 'msg' => 'Já existe agendamento neste horário.']);
            exit;
        }

        // Cliente cria como 'pendente', dono cria como 'agendado'
        $user_role = $_SESSION['role'] ?? 'cliente';
        $initial_status = ($user_role === 'dono') ? 'agendado' : 'pendente';

        $stmt = $pdo->prepare("
            INSERT INTO events (title, start, end, status, user_id)
            VALUES (:title, :start, :end, :status, :user_id)
        ");
        $stmt->execute([
            ':title' => $title,
            ':start' => $start,
            ':end' => $end,
            ':status' => $initial_status,
            ':user_id' => $_SESSION['user_id'] ?? null
        ]);

        echo json_encode([
            'status' => 'success',
            'msg' => 'Agendamento criado com sucesso!',
            'event_id' => $pdo->lastInsertId()
        ]);

    } catch (PDOException $e) {
        error_log('Erro: ' . $e->getMessage());
        echo json_encode(['status' => 'error', 'msg' => 'Erro ao criar agendamento.']);
    }
} else {
    echo json_encode(['status' => 'error', 'msg' => 'Método inválido.']);
}