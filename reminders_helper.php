<?php
require_once 'config.php';
require_once 'email_config.php';

/**
 * Busca configuração de lembrete
 */
function getReminderSetting($key, $default = null) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT setting_value FROM reminder_settings WHERE setting_key = :key");
        $stmt->execute([':key' => $key]);
        $result = $stmt->fetchColumn();
        return $result !== false ? $result : $default;
    } catch (PDOException $e) {
        return $default;
    }
}

/**
 * Atualiza configuração de lembrete
 */
function updateReminderSetting($key, $value) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            INSERT INTO reminder_settings (setting_key, setting_value) 
            VALUES (:key, :value)
            ON DUPLICATE KEY UPDATE setting_value = :value
        ");
        return $stmt->execute([':key' => $key, ':value' => $value]);
    } catch (PDOException $e) {
        error_log('Erro ao atualizar setting: ' . $e->getMessage());
        return false;
    }
}

/**
 * Verifica se lembrete já foi enviado
 */
function reminderAlreadySent($event_id, $reminder_type) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM reminders_sent 
            WHERE event_id = :event_id 
            AND reminder_type = :type 
            AND status = 'enviado'
        ");
        $stmt->execute([
            ':event_id' => $event_id,
            ':type' => $reminder_type
        ]);
        return $stmt->fetchColumn() > 0;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Registra lembrete enviado
 */
function logReminderSent($event_id, $user_id, $reminder_type, $sent_via, $status = 'enviado', $error = null) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            INSERT INTO reminders_sent 
            (event_id, user_id, reminder_type, sent_via, status, error_message)
            VALUES (:event_id, :user_id, :type, :via, :status, :error)
        ");
        return $stmt->execute([
            ':event_id' => $event_id,
            ':user_id' => $user_id,
            ':type' => $reminder_type,
            ':via' => $sent_via,
            ':status' => $status,
            ':error' => $error
        ]);
    } catch (PDOException $e) {
        error_log('Erro ao registrar lembrete: ' . $e->getMessage());
        return false;
    }
}

/**
 * Formata mensagem de lembrete para WhatsApp
 */
function formatReminderMessageWhatsApp($nome, $servico, $dataHora, $tipo) {
    $emoji = [
        '24h' => '📅',
        '2h' => '⏰',
        '1h' => '🔔'
    ];
    
    $tempoTexto = [
        '24h' => 'amanhã',
        '2h' => 'em 2 horas',
        '1h' => 'em 1 hora'
    ];
    
    $e = $emoji[$tipo] ?? '📢';
    $t = $tempoTexto[$tipo] ?? 'em breve';
    
    $mensagem = "{$e} *Lembrete de Agendamento*\n\n";
    $mensagem .= "Olá *{$nome}*!\n\n";
    $mensagem .= "Este é um lembrete do seu agendamento *{$t}*:\n\n";
    $mensagem .= "💅 *Serviço:* {$servico}\n";
    $mensagem .= "📆 *Data/Hora:* {$dataHora}\n";
    $mensagem .= "📍 *Local:* " . ESTABELECIMENTO_NOME . "\n";
    $mensagem .= "📞 *Contato:* " . ESTABELECIMENTO_TELEFONE . "\n\n";
    
    if ($tipo === '24h') {
        $mensagem .= "_Caso precise cancelar, por favor nos avise com antecedência._\n\n";
    } else {
        $mensagem .= "⚠️ _Seu horário está próximo! Não se atrase._\n\n";
    }
    
    $mensagem .= "Aguardamos você! ✨";
    
    return $mensagem;
}

/**
 * Formata mensagem de lembrete para Email
 */
function formatReminderMessageEmail($nome, $servico, $dataHora, $tipo) {
    $tempoTexto = [
        '24h' => 'amanhã',
        '2h' => 'em 2 horas',
        '1h' => 'em 1 hora'
    ];
    
    $t = $tempoTexto[$tipo] ?? 'em breve';
    
    return "
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body { 
                font-family: 'Arial', sans-serif; 
                color: #333;
                line-height: 1.6;
            }
            .container { 
                max-width: 600px; 
                margin: 0 auto; 
                padding: 20px; 
            }
            .header { 
                background: linear-gradient(135deg, #c89b4b, #5a3a1c); 
                color: white; 
                padding: 30px; 
                text-align: center;
                border-radius: 8px 8px 0 0;
            }
            .header h1 {
                margin: 0;
                font-size: 24px;
            }
            .content { 
                background: #f9f9f9; 
                padding: 30px;
                border-radius: 0 0 8px 8px;
            }
            .info-box { 
                background: white; 
                padding: 20px; 
                margin: 20px 0; 
                border-left: 4px solid #c89b4b;
                border-radius: 4px;
            }
            .info-box p {
                margin: 10px 0;
            }
            .footer {
                text-align: center;
                padding: 20px;
                color: #999;
                font-size: 12px;
            }
            .btn {
                display: inline-block;
                padding: 12px 24px;
                background: linear-gradient(135deg, #c89b4b, #5a3a1c);
                color: white;
                text-decoration: none;
                border-radius: 6px;
                margin-top: 15px;
            }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>💅 Lembrete de Agendamento</h1>
            </div>
            <div class='content'>
                <p>Olá <strong>{$nome}</strong>,</p>
                <p>Este é um lembrete do seu agendamento <strong>{$t}</strong>!</p>
                
                <div class='info-box'>
                    <h3>📋 Detalhes do Agendamento</h3>
                    <p><strong>💅 Serviço:</strong> {$servico}</p>
                    <p><strong>📆 Data/Hora:</strong> {$dataHora}</p>
                    <p><strong>📍 Local:</strong> " . ESTABELECIMENTO_NOME . "</p>
                    <p><strong>📌 Endereço:</strong> " . ESTABELECIMENTO_ENDERECO . "</p>
                    <p><strong>📞 Telefone:</strong> " . ESTABELECIMENTO_TELEFONE . "</p>
                </div>
                
                " . ($tipo === '24h' 
                    ? "<p style='color: #666;'><em>Caso precise cancelar ou remarcar, por favor nos avise com antecedência através do nosso sistema ou telefone.</em></p>"
                    : "<p style='color: #e65100; font-weight: bold;'>⚠️ Seu horário está próximo! Não se atrase.</p>"
                ) . "
                
                <p>Aguardamos você com carinho! ✨</p>
            </div>
            
            <div class='footer'>
                <p>" . ESTABELECIMENTO_NOME . "</p>
                <p>Este é um e-mail automático, não responda.</p>
            </div>
        </div>
    </body>
    </html>
    ";
}

/**
 * Envia lembrete via WhatsApp
 */
function sendReminderWhatsApp($whatsapp, $mensagem) {
    if (!WHATSAPP_ENABLED || empty($whatsapp)) {
        return ['success' => false, 'message' => 'WhatsApp não configurado'];
    }
    
    $link = gerarLinkWhatsApp($whatsapp, $mensagem);
    
    if ($link) {
        // Em produção, você usaria uma API de WhatsApp Business
        // Por enquanto, retorna o link (seria enviado via API)
        return [
            'success' => true, 
            'link' => $link,
            'message' => 'Link gerado com sucesso'
        ];
    }
    
    return ['success' => false, 'message' => 'Erro ao gerar link'];
}

/**
 * Envia lembrete via Email
 */
function sendReminderEmail($email, $assunto, $mensagem) {
    try {
        $resultado = enviarEmail($email, $assunto, $mensagem, true);
        
        if ($resultado) {
            return ['success' => true, 'message' => 'E-mail enviado'];
        } else {
            return ['success' => false, 'message' => 'Erro ao enviar e-mail'];
        }
    } catch (Exception $e) {
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Processa envio de lembrete para um agendamento
 */
function processReminder($event, $reminder_type) {
    $method = getReminderSetting('reminder_method', 'both');
    
    $nome = $event['user_name'] ?? 'Cliente';
    $servico = $event['title'];
    $dataHora = date('d/m/Y \à\s H:i', strtotime($event['start']));
    
    $whatsappSuccess = false;
    $emailSuccess = false;
    $errors = [];
    
    // Enviar via WhatsApp
    if (in_array($method, ['whatsapp', 'both']) && !empty($event['whatsapp'])) {
        $msgWhatsApp = formatReminderMessageWhatsApp($nome, $servico, $dataHora, $reminder_type);
        $resultWhatsApp = sendReminderWhatsApp($event['whatsapp'], $msgWhatsApp);
        
        if ($resultWhatsApp['success']) {
            $whatsappSuccess = true;
        } else {
            $errors[] = 'WhatsApp: ' . $resultWhatsApp['message'];
        }
    }
    
    // Enviar via Email
    if (in_array($method, ['email', 'both']) && !empty($event['user_email'])) {
        $assunto = "Lembrete: Seu agendamento em " . ESTABELECIMENTO_NOME;
        $msgEmail = formatReminderMessageEmail($nome, $servico, $dataHora, $reminder_type);
        $resultEmail = sendReminderEmail($event['user_email'], $assunto, $msgEmail);
        
        if ($resultEmail['success']) {
            $emailSuccess = true;
        } else {
            $errors[] = 'E-mail: ' . $resultEmail['message'];
        }
    }
    
    // Determinar status geral
    $anySuccess = $whatsappSuccess || $emailSuccess;
    
    // Definir via de envio
    $sent_via = 'both';
    if ($whatsappSuccess && !$emailSuccess) $sent_via = 'whatsapp';
    if ($emailSuccess && !$whatsappSuccess) $sent_via = 'email';
    
    // Registrar log
    logReminderSent(
        $event['id'],
        $event['user_id'],
        $reminder_type,
        $sent_via,
        $anySuccess ? 'enviado' : 'erro',
        !$anySuccess && !empty($errors) ? implode('; ', $errors) : null
    );
    
    return [
        'success' => $anySuccess,
        'whatsapp' => $whatsappSuccess,
        'email' => $emailSuccess,
        'errors' => $errors
    ];
}
