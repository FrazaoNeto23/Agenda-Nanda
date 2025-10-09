<?php
require 'config.php';
checkLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $service_id = intval($_POST['service_id'] ?? 0);
    $date = $_POST['date'] ?? '';
    $time = $_POST['time'] ?? '';
    $end_time = $_POST['end_time'] ?? '';

    // Validações básicas
    if (!$service_id || !$date || !$time) {
        echo json_encode([
            'status' => 'error',
            'msg' => 'Dados incompletos. Preencha todos os campos obrigatórios.'
        ]);
        exit;
    }

    // Validar formato de data
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        echo json_encode([
            'status' => 'error',
            'msg' => 'Formato de data inválido.'
        ]);
        exit;
    }

    // Validar formato de hora
    if (!preg_match('/^\d{2}:\d{2}$/', $time)) {
        echo json_encode([
            'status' => 'error',
            'msg' => 'Formato de horário inválido.'
        ]);
        exit;
    }

    // Verificar se a data/hora não é no passado
    $start_datetime = $date . ' ' . $time;
    if (strtotime($start_datetime) < time()) {
        echo json_encode([
            'status' => 'error',
            'msg' => 'Não é possível agendar em horários passados.'
        ]);
        exit;
    }

    try {
        // Recupera o serviço
        $stmt = $pdo->prepare("SELECT name, duration FROM services WHERE id = :id");
        $stmt->execute([':id' => $service_id]);
        $service = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$service) {
            echo json_encode([
                'status' => 'error',
                'msg' => 'Serviço não encontrado.'
            ]);
            exit;
        }

        $title = $service['name'];

        // Se não forneceu end_time, calcular baseado na duração do serviço
        if (empty($end_time)) {
            $duration = intval($service['duration'] ?? 60);
            $end_time = date('H:i', strtotime($time) + ($duration * 60));
        }

        $start = $date . ' ' . $time;
        $end = $date . ' ' . $end_time;

        // Verificar se já existe agendamento no mesmo horário
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM events 
            WHERE status != 'cancelado' 
            AND (
                (start <= :start AND end > :start) OR
                (start < :end AND end >= :end) OR
                (start >= :start AND end <= :end)
            )
        ");
        $stmt->execute([
            ':start' => $start,
            ':end' => $end
        ]);

        $conflictCount = $stmt->fetchColumn();

        if ($conflictCount > 0) {
            echo json_encode([
                'status' => 'error',
                'msg' => 'Já existe um agendamento neste horário. Escolha outro horário.'
            ]);
            exit;
        }

        // Inserir o agendamento
        $stmt = $pdo->prepare("
            INSERT INTO events (title, start, end, status, user_id)
            VALUES (:title, :start, :end, :status, :user_id)
        ");

        $stmt->execute([
            ':title' => $title,
            ':start' => $start,
            ':end' => $end,
            ':status' => 'agendado',
            ':user_id' => $_SESSION['user_id'] ?? null
        ]);

        echo json_encode([
            'status' => 'success',
            'msg' => 'Agendamento criado com sucesso!',
            'event_id' => $pdo->lastInsertId()
        ]);

    } catch (PDOException $e) {
        error_log('Erro ao criar agendamento: ' . $e->getMessage());
        echo json_encode([
            'status' => 'error',
            'msg' => 'Erro ao criar agendamento. Tente novamente.'
        ]);
    }
} else {
    echo json_encode([
        'status' => 'error',
        'msg' => 'Método inválido.'
    ]);
}