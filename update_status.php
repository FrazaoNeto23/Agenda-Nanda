<?php
require 'config.php';
checkLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id'] ?? 0);
    $status = $_POST['status'] ?? '';
    $motivo = $_POST['motivo'] ?? '';

    // Lista de status permitidos
    $allowed = ['pendente', 'agendado', 'concluido', 'cancelado'];

    if ($id > 0 && in_array($status, $allowed)) {

        try {
            // Atualizar status
            $stmt = $pdo->prepare("UPDATE events SET status = :status WHERE id = :id");
            $result = $stmt->execute([':status' => $status, ':id' => $id]);

            if (!$result) {
                echo json_encode([
                    'status' => 'error',
                    'msg' => 'Erro ao atualizar status no banco de dados.'
                ]);
                exit;
            }

            // Buscar informações do evento para histórico
            $stmt = $pdo->prepare("
                SELECT e.*, u.name as user_name, u.email as user_email
                FROM events e
                LEFT JOIN users u ON u.id = e.user_id
                WHERE e.id = :id
            ");
            $stmt->execute([':id' => $id]);
            $event = $stmt->fetch(PDO::FETCH_ASSOC);

            // Registrar no histórico (se a tabela existir)
            try {
                $acao_usuario_nome = $_SESSION['name'] ?? 'Sistema';
                $descricao = '';

                switch ($status) {
                    case 'agendado':
                        $descricao = "Agendamento confirmado por {$acao_usuario_nome}";
                        break;
                    case 'cancelado':
                        $descricao = $motivo
                            ? "Cancelado por {$acao_usuario_nome}. Motivo: {$motivo}"
                            : "Cancelado por {$acao_usuario_nome}";
                        break;
                    case 'concluido':
                        $descricao = "Concluído por {$acao_usuario_nome}";
                        break;
                }

                $stmtHist = $pdo->prepare("
                    INSERT INTO historico (event_id, acao, descricao, user_id, created_at)
                    VALUES (:event_id, :acao, :descricao, :user_id, NOW())
                ");
                $stmtHist->execute([
                    ':event_id' => $id,
                    ':acao' => $status,
                    ':descricao' => $descricao,
                    ':user_id' => $_SESSION['user_id'] ?? null
                ]);
            } catch (PDOException $e) {
                // Ignora erro de histórico se tabela não existir
                error_log('Aviso histórico: ' . $e->getMessage());
            }

            echo json_encode([
                'status' => 'success',
                'msg' => "Status atualizado para {$status}!",
                'email_enviado' => false,
                'whatsapp_link' => null
            ]);

        } catch (PDOException $e) {
            error_log('Erro update_status: ' . $e->getMessage());
            echo json_encode([
                'status' => 'error',
                'msg' => 'Erro ao atualizar: ' . $e->getMessage()
            ]);
        }

    } else {
        echo json_encode([
            'status' => 'error',
            'msg' => 'Parâmetros inválidos. ID: ' . $id . ', Status: ' . $status
        ]);
    }
} else {
    echo json_encode([
        'status' => 'error',
        'msg' => 'Método inválido. Use POST.'
    ]);
}