<?php
require 'config.php';
require 'email_config.php';
checkLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id'] ?? 0);
    $status = $_POST['status'] ?? '';
    $motivo = $_POST['motivo'] ?? '';

    $allowed = ['pendente', 'agendado', 'concluido', 'cancelado'];

    if ($id > 0 && in_array($status, $allowed)) {
        
        // Buscar informações do agendamento e cliente
        $stmt = $pdo->prepare("
            SELECT e.*, u.name as user_name, u.email as user_email, 
                   u.role as user_role, u.phone as user_phone
            FROM events e
            LEFT JOIN users u ON u.id = e.user_id
            WHERE e.id = :id
        ");
        $stmt->execute([':id' => $id]);
        $event = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$event) {
            echo json_encode(['status' => 'error', 'msg' => 'Agendamento não encontrado.']);
            exit;
        }
        
        // Atualizar status
        $stmt = $pdo->prepare("UPDATE events SET status = :status WHERE id = :id");
        $stmt->execute([':status' => $status, ':id' => $id]);
        
        // Registrar histórico
        $acao_usuario_id = $_SESSION['user_id'] ?? null;
        $acao_usuario_nome = $_SESSION['name'] ?? 'Sistema';
        $acao_usuario_role = $_SESSION['role'] ?? 'sistema';
        
        $descricao = '';
        switch ($status) {
            case 'agendado':
                $descricao = "Agendamento confirmado por {$acao_usuario_nome}";
                break;
            case 'cancelado':
                $descricao = $motivo 
                    ? "Agendamento cancelado por {$acao_usuario_nome}. Motivo: {$motivo}"
                    : "Agendamento cancelado por {$acao_usuario_nome}";
                break;
            case 'concluido':
                $descricao = "Agendamento concluído por {$acao_usuario_nome}";
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
            ':user_id' => $acao_usuario_id
        ]);
        
        // Preparar informações para email/WhatsApp
        $nomeCliente = $event['user_name'] ?? 'Cliente';
        $emailCliente = $event['user_email'] ?? '';
        $telefoneCliente = $event['user_phone'] ?? '';
        $servicoNome = $event['title'];
        $dataHora = date('d/m/Y \à\s H:i', strtotime($event['start']));
        
        // ENVIAR EMAIL E WHATSAPP
        $emailEnviado = false;
        $whatsappLink = null;
        
        if ($emailCliente) {
            if ($status === 'agendado') {
                // CONFIRMADO
                $assunto = "✅ Agendamento Confirmado - " . ESTABELECIMENTO_NOME;
                $mensagemHtml = emailAgendamentoConfirmado($nomeCliente, $servicoNome, $dataHora);
                $emailEnviado = enviarEmail($emailCliente, $assunto, $mensagemHtml, true);
                
                // WhatsApp
                $mensagemWhats = "✅ *Agendamento Confirmado!*\n\n"
                    . "Olá {$nomeCliente}!\n\n"
                    . "Seu agendamento foi confirmado:\n"
                    . "📋 Serviço: {$servicoNome}\n"
                    . "📅 Data/Hora: {$dataHora}\n"
                    . "📍 Local: " . ESTABELECIMENTO_NOME . "\n\n"
                    . "Nos vemos em breve! 💖";
                    
            } elseif ($status === 'cancelado' && $acao_usuario_role === 'dono') {
                // RECUSADO PELO DONO
                $assunto = "❌ Agendamento Não Confirmado - " . ESTABELECIMENTO_NOME;
                $mensagemHtml = emailAgendamentoRecusado($nomeCliente, $servicoNome, $dataHora, $motivo);
                $emailEnviado = enviarEmail($emailCliente, $assunto, $mensagemHtml, true);
                
                // WhatsApp
                $motivoTexto = $motivo ? "\n🗨️ Motivo: {$motivo}" : "";
                $mensagemWhats = "❌ *Agendamento Não Confirmado*\n\n"
                    . "Olá {$nomeCliente},\n\n"
                    . "Infelizmente não conseguimos confirmar seu agendamento:\n"
                    . "📋 Serviço: {$servicoNome}\n"
                    . "📅 Data/Hora: {$dataHora}{$motivoTexto}\n\n"
                    . "Entre em contato para agendar outro horário!\n"
                    . "📱 " . ESTABELECIMENTO_TELEFONE;
                    
            } elseif ($status === 'cancelado' && $acao_usuario_role === 'cliente') {
                // CANCELADO PELO CLIENTE
                $assunto = "🔔 Agendamento Cancelado - " . ESTABELECIMENTO_NOME;
                $mensagemHtml = emailAgendamentoCancelado($nomeCliente, $servicoNome, $dataHora, $motivo);
                $emailEnviado = enviarEmail($emailCliente, $assunto, $mensagemHtml, true);
                
                // WhatsApp
                $motivoTexto = $motivo ? "\n🗨️ Motivo: {$motivo}" : "";
                $mensagemWhats = "🔔 *Agendamento Cancelado*\n\n"
                    . "Olá {$nomeCliente},\n\n"
                    . "Seu agendamento foi cancelado:\n"
                    . "📋 Serviço: {$servicoNome}\n"
                    . "📅 Data/Hora: {$dataHora}{$motivoTexto}\n\n"
                    . "Esperamos vê-la em breve! 💖";
            }
            
            // Gerar link do WhatsApp se mensagem foi criada e telefone existe
            if (isset($mensagemWhats) && $telefoneCliente) {
                $whatsappLink = gerarLinkWhatsApp($telefoneCliente, $mensagemWhats);
            }
        }
        
        echo json_encode([
            'status' => 'success',
            'msg' => "Status atualizado para {$status}!",
            'email_enviado' => $emailEnviado,
            'whatsapp_link' => $whatsappLink
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