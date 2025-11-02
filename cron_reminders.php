<?php
require_once 'config.php';
require_once 'reminders_helper.php';

// Configurar timezone
date_default_timezone_set('America/Sao_Paulo');

// Log de início
$startTime = date('Y-m-d H:i:s');
echo "\n========================================\n";
echo "🔔 Início do processamento de lembretes\n";
echo "Data/Hora: {$startTime}\n";
echo "========================================\n\n";

try {
    // Verificar se lembretes estão habilitados
    $enable24h = getReminderSetting('enable_24h_reminder', '1') === '1';
    $enable2h = getReminderSetting('enable_2h_reminder', '1') === '1';
    $enable1h = getReminderSetting('enable_1h_reminder', '0') === '1';
    
    echo "📋 Configurações:\n";
    echo "   - Lembrete 24h: " . ($enable24h ? 'ATIVO' : 'INATIVO') . "\n";
    echo "   - Lembrete 2h: " . ($enable2h ? 'ATIVO' : 'INATIVO') . "\n";
    echo "   - Lembrete 1h: " . ($enable1h ? 'ATIVO' : 'INATIVO') . "\n\n";
    
    $stats = [
        'total_processed' => 0,
        'reminders_sent' => 0,
        'errors' => 0,
        'skipped' => 0
    ];
    
    // ========== LEMBRETE 24 HORAS ANTES ==========
    if ($enable24h) {
        echo "📅 Processando lembretes de 24 horas...\n";
        
        $stmt = $pdo->prepare("
            SELECT 
                e.id, e.title, e.start, e.user_id,
                u.name as user_name, 
                u.email as user_email,
                u.whatsapp,
                u.receive_reminders
            FROM events e
            JOIN users u ON u.id = e.user_id
            WHERE e.status = 'agendado'
            AND u.receive_reminders = 1
            AND e.start BETWEEN NOW() + INTERVAL 23 HOUR AND NOW() + INTERVAL 25 HOUR
        ");
        
        $stmt->execute();
        $events24h = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "   Encontrados: " . count($events24h) . " agendamentos\n";
        
        foreach ($events24h as $event) {
            $stats['total_processed']++;
            
            // Verificar se já foi enviado
            if (reminderAlreadySent($event['id'], '24h')) {
                echo "   ⏭️  Agendamento #{$event['id']} - Já enviado anteriormente\n";
                $stats['skipped']++;
                continue;
            }
            
            echo "   📤 Enviando para: {$event['user_name']} - {$event['title']}\n";
            
            $result = processReminder($event, '24h');
            
            if ($result['success']) {
                $stats['reminders_sent']++;
                echo "      ✅ Enviado com sucesso!\n";
            } else {
                $stats['errors']++;
                echo "      ❌ Erro ao enviar: " . implode(', ', $result['errors']) . "\n";
            }
        }
        
        echo "\n";
    }
    
    // ========== LEMBRETE 2 HORAS ANTES ==========
    if ($enable2h) {
        echo "⏰ Processando lembretes de 2 horas...\n";
        
        $stmt = $pdo->prepare("
            SELECT 
                e.id, e.title, e.start, e.user_id,
                u.name as user_name, 
                u.email as user_email,
                u.whatsapp,
                u.receive_reminders
            FROM events e
            JOIN users u ON u.id = e.user_id
            WHERE e.status = 'agendado'
            AND u.receive_reminders = 1
            AND e.start BETWEEN NOW() + INTERVAL 1 HOUR 45 MINUTE AND NOW() + INTERVAL 2 HOUR 15 MINUTE
        ");
        
        $stmt->execute();
        $events2h = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "   Encontrados: " . count($events2h) . " agendamentos\n";
        
        foreach ($events2h as $event) {
            $stats['total_processed']++;
            
            if (reminderAlreadySent($event['id'], '2h')) {
                echo "   ⏭️  Agendamento #{$event['id']} - Já enviado anteriormente\n";
                $stats['skipped']++;
                continue;
            }
            
            echo "   📤 Enviando para: {$event['user_name']} - {$event['title']}\n";
            
            $result = processReminder($event, '2h');
            
            if ($result['success']) {
                $stats['reminders_sent']++;
                echo "      ✅ Enviado com sucesso!\n";
            } else {
                $stats['errors']++;
                echo "      ❌ Erro ao enviar: " . implode(', ', $result['errors']) . "\n";
            }
        }
        
        echo "\n";
    }
    
    // ========== LEMBRETE 1 HORA ANTES ==========
    if ($enable1h) {
        echo "🔔 Processando lembretes de 1 hora...\n";
        
        $stmt = $pdo->prepare("
            SELECT 
                e.id, e.title, e.start, e.user_id,
                u.name as user_name, 
                u.email as user_email,
                u.whatsapp,
                u.receive_reminders
            FROM events e
            JOIN users u ON u.id = e.user_id
            WHERE e.status = 'agendado'
            AND u.receive_reminders = 1
            AND e.start BETWEEN NOW() + INTERVAL 50 MINUTE AND NOW() + INTERVAL 1 HOUR 10 MINUTE
        ");
        
        $stmt->execute();
        $events1h = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "   Encontrados: " . count($events1h) . " agendamentos\n";
        
        foreach ($events1h as $event) {
            $stats['total_processed']++;
            
            if (reminderAlreadySent($event['id'], '1h')) {
                echo "   ⏭️  Agendamento #{$event['id']} - Já enviado anteriormente\n";
                $stats['skipped']++;
                continue;
            }
            
            echo "   📤 Enviando para: {$event['user_name']} - {$event['title']}\n";
            
            $result = processReminder($event, '1h');
            
            if ($result['success']) {
                $stats['reminders_sent']++;
                echo "      ✅ Enviado com sucesso!\n";
            } else {
                $stats['errors']++;
                echo "      ❌ Erro ao enviar: " . implode(', ', $result['errors']) . "\n";
            }
        }
        
        echo "\n";
    }
    
    // Resumo final
    $endTime = date('Y-m-d H:i:s');
    $duration = strtotime($endTime) - strtotime($startTime);
    
    echo "========================================\n";
    echo "📊 RESUMO DO PROCESSAMENTO\n";
    echo "========================================\n";
    echo "Total processados: {$stats['total_processed']}\n";
    echo "Lembretes enviados: {$stats['reminders_sent']}\n";
    echo "Erros: {$stats['errors']}\n";
    echo "Ignorados (já enviados): {$stats['skipped']}\n";
    echo "Duração: {$duration} segundos\n";
    echo "Término: {$endTime}\n";
    echo "========================================\n\n";
    
} catch (Exception $e) {
    echo "❌ ERRO FATAL: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}

exit(0);
