<?php
require 'config.php';
checkLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id'] ?? 0);
    $status = $_POST['status'] ?? '';

    // 🔎 Lista de status permitidos
    $allowed = ['agendado', 'concluido', 'cancelado'];

    if ($id > 0 && in_array($status, $allowed)) {
        $stmt = $pdo->prepare("UPDATE events SET status = :status WHERE id = :id");
        $stmt->execute([
            ':status' => $status,
            ':id' => $id
        ]);

        echo json_encode([
            'status' => 'success',
            'msg' => "Status atualizado para {$status}!"
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'msg' => 'Parâmetros inválidos.'
        ]);
    }
} else {
    echo json_encode([
        'status' => 'error',
        'msg' => 'Método inválido.'
    ]);
}
