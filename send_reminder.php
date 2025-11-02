<?php
require 'config.php';
checkLogin();

if (!isDono()) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'msg' => 'Acesso negado']);
    exit;
}

require_once 'reminders_helper.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $event_id = intval($_POST['event_id'] ?? 0);
    $reminder_type = $_POST['reminder_type'] ?? '24h'; // 24h, 2h, 1h ou 'now'
    
    if ($event_id <= 0) {
        echo json_encode(['status' => 'error', 'msg' => 'ID do evento inválido']);
        exit;
    }
    
    // Validar tipo de lembrete
    if (!in_array($reminder_type, ['24h', '2h', '1h', 'now'])) {
        echo json_encode(['status' => 'error', 'msg' => 'Tipo de lembrete inválido']);
        exit;
    }
    
    try {
        // Buscar informações do agendamento
        $stmt = $pdo->prepare("
            SELECT 
                e.id, e.title, e.start, e.status, e.user_id,
                u.name as user_name, 
                u.email as user_email,
                u.whatsapp,
                u.receive_reminders
            FROM events e
            JOIN users u ON u.id = e.user_id
            WHERE e.id = :id
        ");
        
        $stmt->execute([':id' => $event_id]);
        $event = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$event) {
            echo json_encode(['status' => 'error', 'msg' => 'Agendamento não encontrado']);
            exit;
        }
        
        // Verificar se está confirmado
        if ($event['status'] !== 'agendado') {
            echo json_encode([
                'status' => 'error', 
                'msg' => 'Apenas agendamentos confirmados podem receber lembretes'
            ]);
            exit;
        }
        
        // Verificar se o agendamento já passou
        if (strtotime($event['start']) < time()) {
            echo json_encode([
                'status' => 'error', 
                'msg' => 'Não é possível enviar lembrete para agendamento passado'
            ]);
            exit;
        }
        
        // Cliente não quer receber lembretes
        if ($event['receive_reminders'] == 0) {
            echo json_encode([
                'status' => 'warning', 
                'msg' => 'Cliente optou por não receber lembretes automáticos'
            ]);
            exit;
        }
        
        // Verificar se já foi enviado (exceto se for 'now' que permite reenvio)
        if ($reminder_type !== 'now' && reminderAlreadySent($event_id, $reminder_type)) {
            echo json_encode([
                'status' => 'warning',
                'msg' => 'Lembrete deste tipo já foi enviado anteriormente',
                'allow_resend' => true
            ]);
            exit;
        }
        
        // Processar envio
        $result = processReminder($event, $reminder_type === 'now' ? '24h' : $reminder_type);
        
        if ($result['success']) {
            $methods = [];
            if ($result['whatsapp']) $methods[] = 'WhatsApp';
            if ($result['email']) $methods[] = 'E-mail';
            
            echo json_encode([
                'status' => 'success',
                'msg' => 'Lembrete enviado com sucesso via ' . implode(' e ', $methods),
                'methods' => $methods,
                'recipient' => [
                    'name' => $event['user_name'],
                    'email' => $event['user_email'],
                    'whatsapp' => $event['whatsapp']
                ]
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'msg' => 'Erro ao enviar lembrete',
                'errors' => $result['errors']
            ]);
        }
        
    } catch (PDOException $e) {
        error_log('Erro ao enviar lembrete manual: ' . $e->getMessage());
        echo json_encode([
            'status' => 'error',
            'msg' => 'Erro ao processar solicitação'
        ]);
    }
    
} else {
    echo json_encode([
        'status' => 'error',
        'msg' => 'Método inválido. Use POST.'
    ]);
}
