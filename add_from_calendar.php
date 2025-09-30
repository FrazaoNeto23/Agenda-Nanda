<?php
require 'config.php';
checkLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $service_id = intval($_POST['service_id'] ?? 0);
    $date = $_POST['date'] ?? '';
    $time = $_POST['time'] ?? '';
    $end_time = $_POST['end_time'] ?? '';

    if ($service_id && $date && $time) {
        // 🔎 Recupera o serviço
        $stmt = $pdo->prepare("SELECT name FROM services WHERE id = :id");
        $stmt->execute([':id' => $service_id]);
        $service = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($service) {
            $title = $service['name'];

            $start = $date . ' ' . $time;
            $end = $date . ' ' . ($end_time ?: $time);

            $stmt = $pdo->prepare("
                INSERT INTO events (title, start, end, status, user_id)
                VALUES (:title, :start, :end, :status, :user_id)
            ");

            $stmt->execute([
                ':title' => $title,
                ':start' => $start,
                ':end' => $end,
                ':status' => 'agendado', // ✅ sempre cria como agendado
                ':user_id' => $_SESSION['user_id'] ?? null
            ]);

            echo json_encode([
                'status' => 'success',
                'msg' => 'Agendamento criado com sucesso!'
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'msg' => 'Serviço não encontrado.'
            ]);
        }
    } else {
        echo json_encode([
            'status' => 'error',
            'msg' => 'Dados incompletos.'
        ]);
    }
} else {
    echo json_encode([
        'status' => 'error',
        'msg' => 'Método inválido.'
    ]);
}
