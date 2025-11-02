<?php
require 'config.php';
checkLogin();
requireCSRF();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $service_id = intval($_POST['service_id'] ?? 0);
    $date = sanitizeInput($_POST['date'] ?? '');
    $time = sanitizeInput($_POST['time'] ?? '');
    $end_time = sanitizeInput($_POST['end_time'] ?? '');
    $client_notes = sanitizeInput($_POST['client_notes'] ?? '');
    $professional_id = intval($_POST['professional_id'] ?? 0) ?: null;

    // Validações básicas
    if (!$service_id || !$date || !$time) {
        echo json_encode(['status' => 'error', 'msg' => 'Dados incompletos. Preencha todos os campos obrigatórios.']);
        exit;
    }

    // Validar formato de data
    if (!validateDate($date)) {
        echo json_encode(['status' => 'error', 'msg' => 'Formato de data inválido.']);
        exit;
    }

    // Validar formato de hora
    if (!validateTime($time)) {
        echo json_encode(['status' => 'error', 'msg' => 'Formato de horário inválido.']);
        exit;
    }

    try {
        // Recupera o serviço
        $stmt = $pdo->prepare("SELECT * FROM services WHERE id = :id AND active = 1");
        $stmt->execute([':id' => $service_id]);
        $service = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$service) {
            echo json_encode(['status' => 'error', 'msg' => 'Serviço não encontrado ou inativo.']);
            exit;
        }

        $title = $service['name'];
        $service_price = $service['price'];
        $service_points = $service['points_reward'];

        // Se não forneceu end_time, calcular baseado na duração do serviço
        if (empty($end_time)) {
            $duration = intval($service['duration'] ?? 60);
            $end_timestamp = strtotime($time) + ($duration * 60);
            $end_time = date('H:i', $end_timestamp);
        }

        $start_datetime = $date . ' ' . $time;
        $end_datetime = $date . ' ' . $end_time;

        // ===== VALIDAÇÕES DE NEGÓCIO =====

        // 1. Verificar se a data/hora não é no passado
        if (strtotime($start_datetime) < time()) {
            echo json_encode(['status' => 'error', 'msg' => 'Não é possível agendar em horários passados.']);
            exit;
        }

        // 2. Verificar antecedência mínima
        $antecedencia_minima = (int)getConfiguracao('antecedencia_minima', 2);
        $prazo_minimo = time() + ($antecedencia_minima * 3600);
        
        if (strtotime($start_datetime) < $prazo_minimo) {
            echo json_encode([
                'status' => 'error', 
                'msg' => "Agendamento deve ser feito com pelo menos {$antecedencia_minima}h de antecedência."
            ]);
            exit;
        }

        // 3. Verificar se está dentro do horário comercial
        $hora = intval(date('H', strtotime($time)));
        $horario_abertura = (int)explode(':', getConfiguracao('horario_abertura', '08:00'))[0];
        $horario_fechamento = (int)explode(':', getConfiguracao('horario_fechamento', '20:00'))[0];

        if ($hora < $horario_abertura || $hora >= $horario_fechamento) {
            echo json_encode([
                'status' => 'error', 
                'msg' => "Horário fora do expediente ({$horario_abertura}h-{$horario_fechamento}h)."
            ]);
            exit;
        }

        // 4. Verificar se não é um dia fechado
        $dia_semana = date('w', strtotime($date));
        $dias_fechados = json_decode(getConfiguracao('dias_fechados', '[0]'), true);
        
        if (in_array($dia_semana, $dias_fechados)) {
            $nomes_dias = ['Domingo', 'Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado'];
            echo json_encode([
                'status' => 'error', 
                'msg' => 'Não atendemos aos ' . $nomes_dias[$dia_semana] . 's.'
            ]);
            exit;
        }

        // 5. Verificar limite de agendamentos por dia
        $max_agendamentos = (int)getConfiguracao('max_agendamentos_dia', 20);
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as total 
            FROM events 
            WHERE DATE(start) = :date 
            AND status IN ('pendente', 'agendado')
        ");
        $stmt->execute([':date' => $date]);
        $count = $stmt->fetch()['total'];

        if ($count >= $max_agendamentos) {
            echo json_encode([
                'status' => 'error', 
                'msg' => 'Limite de agendamentos do dia atingido. Escolha outra data.'
            ]);
            exit;
        }

        // 6. Verificar conflito de horários
        $conflictQuery = "
            SELECT COUNT(*) as conflicts
            FROM events 
            WHERE status NOT IN ('cancelado', 'concluido')
            AND (
                (start <= :start AND end > :start) OR
                (start < :end AND end >= :end) OR
                (start >= :start AND end <= :end)
            )
        ";

        // Se especificou profissional, verificar apenas conflito dele
        if ($professional_id) {
            $conflictQuery .= " AND professional_id = :professional_id";
        }

        $stmt = $pdo->prepare($conflictQuery);
        $params = [':start' => $start_datetime, ':end' => $end_datetime];
        
        if ($professional_id) {
            $params[':professional_id'] = $professional_id;
        }

        $stmt->execute($params);
        $conflictCount = $stmt->fetchColumn();

        if ($conflictCount > 0) {
            echo json_encode([
                'status' => 'error', 
                'msg' => 'Já existe um agendamento neste horário. Escolha outro horário.'
            ]);
            exit;
        }

        // 7. Verificar se cliente já tem agendamento no mesmo dia
        $user_role = $_SESSION['role'] ?? 'cliente';
        $user_id = $_SESSION['user_id'] ?? null;

        if ($user_role === 'cliente') {
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as total
                FROM events
                WHERE user_id = :user_id
                AND DATE(start) = :date
                AND status IN ('pendente', 'agendado')
            ");
            $stmt->execute([':user_id' => $user_id, ':date' => $date]);
            $existing = $stmt->fetchColumn();

            if ($existing > 0) {
                echo json_encode([
                    'status' => 'error',
                    'msg' => 'Você já possui um agendamento neste dia. Entre em contato para agendar mais de um serviço.'
                ]);
                exit;
            }
        }

        // ===== CRIAR AGENDAMENTO =====

        // Cliente cria como 'pendente', dono cria como 'agendado'
        $initial_status = ($user_role === 'dono') ? 'agendado' : 'pendente';

        $stmt = $pdo->prepare("
            INSERT INTO events (
                title, start, end, status, user_id, service_id, 
                professional_id, client_notes, created_at
            ) VALUES (
                :title, :start, :end, :status, :user_id, :service_id,
                :professional_id, :client_notes, NOW()
            )
        ");

        $stmt->execute([
            ':title' => $title,
            ':start' => $start_datetime,
            ':end' => $end_datetime,
            ':status' => $initial_status,
            ':user_id' => $user_id,
            ':service_id' => $service_id,
            ':professional_id' => $professional_id,
            ':client_notes' => $client_notes
        ]);

        $event_id = $pdo->lastInsertId();

        // Registrar no histórico
        $user_name = $_SESSION['name'] ?? 'Sistema';
        $descricao = "Agendamento criado por {$user_name}. Status inicial: {$initial_status}";
        logAuditoria('criado', $descricao, $event_id);

        // Criar notificação para o dono (se for cliente agendando)
        if ($user_role === 'cliente') {
            $stmt = $pdo->prepare("
                INSERT INTO notificacoes (user_id, tipo, titulo, mensagem, link)
                SELECT id, 'novo_agendamento', 
                       'Novo Agendamento Pendente',
                       CONCAT(:user_name, ' solicitou um agendamento de ', :service_name),
                       'index.php'
                FROM users WHERE role = 'dono'
            ");
            $stmt->execute([
                ':user_name' => $user_name,
                ':service_name' => $title
            ]);
        }

        echo json_encode([
            'status' => 'success',
            'msg' => $user_role === 'dono' 
                ? 'Agendamento criado com sucesso!' 
                : 'Agendamento enviado! Aguarde confirmação.',
            'event_id' => $event_id,
            'initial_status' => $initial_status
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
