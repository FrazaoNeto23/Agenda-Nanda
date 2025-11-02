<?php
require 'config.php';
require 'email_config.php';

checkLogin();
requireCSRF();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id'] ?? 0);
    $status = sanitizeInput($_POST['status'] ?? '');
    $motivo = sanitizeInput($_POST['motivo'] ?? '');

    // Lista de status permitidos
    $allowed = ['pendente', 'agendado', 'concluido', 'cancelado'];

    if ($id <= 0) {
        echo json_encode(['status' => 'error', 'msg' => 'ID inválido']);
        exit;
    }

    if (!in_array($status, $allowed)) {
        echo json_encode(['status' => 'error', 'msg' => 'Status inválido']);
        exit;
    }

    try {
        // Buscar informações do evento
        $stmt = $pdo->prepare("
            SELECT e.*, u.name as user_name, u.email as user_email, 
                   u.phone as user_phone, u.whatsapp as user_whatsapp,
                   s.name as service_name, s.price as service_price
            FROM events e
            LEFT JOIN users u ON u.id = e.user_id
            LEFT JOIN services s ON s.id = e.service_id
            WHERE e.id = :id
        ");
        $stmt->execute([':id' => $id]);
        $event = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$event) {
            echo json_encode(['status' => 'error', 'msg' => 'Evento não encontrado']);
            exit;
        }

        // Validar permissões
        $user_role = $_SESSION['role'] ?? 'cliente';
        $user_id = $_SESSION['user_id'] ?? 0;

        // Se não for dono, verificar se é o dono do agendamento
        if ($user_role !== 'dono' && $event['user_id'] != $user_id) {
            echo json_encode(['status' => 'error', 'msg' => 'Você não tem permissão para alterar este agendamento']);
            exit;
        }

        // Validações específicas por status
        if ($status === 'cancelado') {
            $canCancel = canCancelEvent($id, $pdo);
            if (!$canCancel['can']) {
                echo json_encode(['status' => 'error', 'msg' => $canCancel['reason']]);
                exit;
            }
        }

        // Cliente não pode mudar status para 'agendado' ou 'concluido'
        if ($user_role === 'cliente' && in_array($status, ['agendado', 'concluido'])) {
            echo json_encode(['status' => 'error', 'msg' => 'Você não tem permissão para esta ação']);
            exit;
        }

        // Atualizar status
        $updateData = [':status' => $status, ':id' => $id];
        $updateQuery = "UPDATE events SET status = :status";

        // Se for cancelamento, salvar motivo
        if ($status === 'cancelado' && !empty($motivo)) {
            $updateQuery .= ", cancel_reason = :cancel_reason";
            $updateData[':cancel_reason'] = $motivo;
        }

        // Se for confirmação, registrar data de confirmação
        if ($status === 'agendado' && $event['status'] === 'pendente') {
            $updateQuery .= ", confirmed_at = NOW()";
        }

        $updateQuery .= " WHERE id = :id";

        $stmt = $pdo->prepare($updateQuery);
        $result = $stmt->execute($updateData);

        if (!$result) {
            echo json_encode(['status' => 'error', 'msg' => 'Erro ao atualizar status']);
            exit;
        }

        // Registrar no histórico
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
            case 'pendente':
                $descricao = "Status alterado para pendente por {$acao_usuario_nome}";
                break;
        }

        logAuditoria($status, $descricao, $id);

        // Preparar resposta
        $response = [
            'status' => 'success',
            'msg' => ucfirst($status) . ' com sucesso!',
            'email_enviado' => false,
            'whatsapp_link' => null
        ];

        // Gerar link do WhatsApp se aplicável
        if ($status !== 'pendente' && !empty($event['user_whatsapp'])) {
            $dataHora = formatDateTimeBR($event['start']);
            $servicoNome = $event['service_name'] ?? $event['title'];
            
            $mensagem = '';
            switch ($status) {
                case 'agendado':
                    $mensagem = "✅ Olá {$event['user_name']}!\n\n";
                    $mensagem .= "Seu agendamento foi CONFIRMADO!\n\n";
                    $mensagem .= "📋 *Detalhes:*\n";
                    $mensagem .= "🔹 Serviço: {$servicoNome}\n";
                    $mensagem .= "🔹 Data/Hora: {$dataHora}\n";
                    $mensagem .= "🔹 Local: " . SITE_ADDRESS . "\n\n";
                    $mensagem .= "Nos vemos em breve! 💅";
                    break;

                case 'cancelado':
                    $mensagem = "❌ Olá {$event['user_name']},\n\n";
                    $mensagem .= "Seu agendamento foi cancelado.\n\n";
                    $mensagem .= "📋 *Detalhes:*\n";
                    $mensagem .= "🔹 Serviço: {$servicoNome}\n";
                    $mensagem .= "🔹 Data/Hora: {$dataHora}\n";
                    if (!empty($motivo)) {
                        $mensagem .= "🔹 Motivo: {$motivo}\n";
                    }
                    $mensagem .= "\nPodemos reagendar para outra data? Entre em contato!";
                    break;

                case 'concluido':
                    $mensagem = "✅ Olá {$event['user_name']}!\n\n";
                    $mensagem .= "Obrigada pela preferência! 💅\n\n";
                    $mensagem .= "Esperamos que tenha gostado do serviço.\n";
                    $mensagem .= "Avalie nossa experiência e ganhe pontos de fidelidade!\n\n";
                    $mensagem .= "Até a próxima! ✨";
                    break;
            }

            $response['whatsapp_link'] = gerarLinkWhatsApp($event['user_whatsapp'], $mensagem);
        }

        // Enviar email (se configurado)
        if (getenv('EMAIL_ENABLED') === 'true' && !empty($event['user_email'])) {
            $emailSent = false;
            $dataHora = formatDateTimeBR($event['start']);
            $servicoNome = $event['service_name'] ?? $event['title'];

            switch ($status) {
                case 'agendado':
                    $assunto = "Agendamento Confirmado - " . SITE_NAME;
                    $mensagemEmail = emailAgendamentoConfirmado(
                        $event['user_name'],
                        $servicoNome,
                        $dataHora
                    );
                    $emailSent = enviarEmail($event['user_email'], $assunto, $mensagemEmail, true);
                    break;

                case 'cancelado':
                    $assunto = "Agendamento Cancelado - " . SITE_NAME;
                    $mensagemEmail = emailAgendamentoCancelado(
                        $event['user_name'],
                        $servicoNome,
                        $dataHora,
                        $motivo
                    );
                    $emailSent = enviarEmail($event['user_email'], $assunto, $mensagemEmail, true);
                    break;
            }

            $response['email_enviado'] = $emailSent;
        }

        echo json_encode($response);

    } catch (PDOException $e) {
        error_log('Erro update_status: ' . $e->getMessage());
        echo json_encode([
            'status' => 'error',
            'msg' => 'Erro ao atualizar status. Tente novamente.'
        ]);
    }

} else {
    echo json_encode([
        'status' => 'error',
        'msg' => 'Método inválido. Use POST.'
    ]);
}
