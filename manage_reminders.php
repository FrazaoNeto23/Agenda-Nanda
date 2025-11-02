<?php
require 'config.php';
checkLogin();

if (!isDono()) {
    header("Location: index.php");
    exit;
}

require_once 'reminders_helper.php';

$user_name = $_SESSION['name'] ?? 'Dono';

// Processar atualizações de configuração
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_settings'])) {
    updateReminderSetting('enable_24h_reminder', isset($_POST['enable_24h']) ? '1' : '0');
    updateReminderSetting('enable_2h_reminder', isset($_POST['enable_2h']) ? '1' : '0');
    updateReminderSetting('enable_1h_reminder', isset($_POST['enable_1h']) ? '1' : '0');
    updateReminderSetting('reminder_method', $_POST['reminder_method'] ?? 'both');
    
    $success_msg = "Configurações atualizadas com sucesso!";
}

// Carregar configurações atuais
$enable24h = getReminderSetting('enable_24h_reminder', '1') === '1';
$enable2h = getReminderSetting('enable_2h_reminder', '1') === '1';
$enable1h = getReminderSetting('enable_1h_reminder', '0') === '1';
$reminderMethod = getReminderSetting('reminder_method', 'both');
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Lembretes - Agenda Manicure</title>
    <link href="styles.css" rel="stylesheet">
    <style>
        .reminders-container {
            background: white;
            padding: 28px;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            margin-top: 24px;
        }
        
        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 24px;
            border-bottom: 2px solid var(--beige-light);
        }
        
        .tab {
            padding: 12px 24px;
            background: transparent;
            border: none;
            border-bottom: 3px solid transparent;
            cursor: pointer;
            font-weight: 600;
            color: var(--brown);
            transition: var(--transition);
        }
        
        .tab.active {
            border-bottom-color: var(--gold);
            color: var(--gold);
        }
        
        .tab:hover {
            background: var(--beige-light);
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .settings-form {
            max-width: 600px;
        }
        
        .setting-group {
            padding: 20px;
            background: var(--beige-light);
            border-radius: 12px;
            margin-bottom: 20px;
        }
        
        .setting-group h3 {
            color: var(--brown);
            margin-bottom: 16px;
            font-size: 1.1rem;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 12px 0;
        }
        
        .checkbox-group input[type="checkbox"] {
            width: 20px;
            height: 20px;
            cursor: pointer;
        }
        
        .checkbox-group label {
            cursor: pointer;
            font-weight: 500;
        }
        
        .logs-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .logs-table th {
            background: linear-gradient(135deg, var(--gold), var(--brown));
            color: white;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        .logs-table td {
            padding: 10px 12px;
            border-bottom: 1px solid var(--beige-light);
            font-size: 0.9rem;
        }
        
        .logs-table tr:hover {
            background: var(--beige-light);
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .status-enviado {
            background: #e8f5e9;
            color: #2e7d32;
        }
        
        .status-erro {
            background: #ffebee;
            color: #c62828;
        }
        
        .status-pendente {
            background: #fff3e0;
            color: #e65100;
        }
        
        .success-message {
            background: #e8f5e9;
            color: #2e7d32;
            padding: 16px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-weight: 500;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: linear-gradient(135deg, var(--beige-light), var(--beige));
            padding: 20px;
            border-radius: 12px;
            text-align: center;
        }
        
        .stat-card h3 {
            font-size: 2rem;
            color: var(--brown);
            margin-bottom: 8px;
        }
        
        .stat-card p {
            color: var(--gold);
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">💅</div>
            <div>
                <h1>Gerenciar Lembretes</h1>
                <p class="subtitle">Olá, <?php echo htmlspecialchars($user_name); ?>!</p>
            </div>
            <div style="margin-left: auto; display: flex; gap: 10px;">
                <a href="index.php" class="btn btn-secondary">Voltar ao Calendário</a>
                <a href="logout.php" class="btn btn-secondary">Sair</a>
            </div>
        </div>

        <?php if (isset($success_msg)): ?>
        <div class="success-message">
            ✅ <?php echo $success_msg; ?>
        </div>
        <?php endif; ?>

        <div class="reminders-container">
            <div class="tabs">
                <button class="tab active" onclick="openTab('statistics')">📊 Estatísticas</button>
                <button class="tab" onclick="openTab('settings')">⚙️ Configurações</button>
                <button class="tab" onclick="openTab('logs')">📜 Logs</button>
                <button class="tab" onclick="openTab('manual')">📤 Envio Manual</button>
            </div>

            <!-- TAB: Estatísticas -->
            <div id="statistics" class="tab-content active">
                <h2>📊 Estatísticas de Lembretes</h2>
                <div class="stats-grid">
                    <div class="stat-card">
                        <h3 id="stat-total">-</h3>
                        <p>Total Enviados</p>
                    </div>
                    <div class="stat-card">
                        <h3 id="stat-hoje">-</h3>
                        <p>Hoje</p>
                    </div>
                    <div class="stat-card">
                        <h3 id="stat-semana">-</h3>
                        <p>Esta Semana</p>
                    </div>
                    <div class="stat-card">
                        <h3 id="stat-erros">-</h3>
                        <p>Erros</p>
                    </div>
                </div>
            </div>

            <!-- TAB: Configurações -->
            <div id="settings" class="tab-content">
                <h2>⚙️ Configurações de Lembretes</h2>
                
                <form method="POST" class="settings-form">
                    <input type="hidden" name="update_settings" value="1">
                    
                    <div class="setting-group">
                        <h3>🕒 Quando Enviar</h3>
                        
                        <div class="checkbox-group">
                            <input type="checkbox" id="enable_24h" name="enable_24h" 
                                   <?php echo $enable24h ? 'checked' : ''; ?>>
                            <label for="enable_24h">Enviar lembrete 24 horas antes</label>
                        </div>
                        
                        <div class="checkbox-group">
                            <input type="checkbox" id="enable_2h" name="enable_2h" 
                                   <?php echo $enable2h ? 'checked' : ''; ?>>
                            <label for="enable_2h">Enviar lembrete 2 horas antes</label>
                        </div>
                        
                        <div class="checkbox-group">
                            <input type="checkbox" id="enable_1h" name="enable_1h" 
                                   <?php echo $enable1h ? 'checked' : ''; ?>>
                            <label for="enable_1h">Enviar lembrete 1 hora antes</label>
                        </div>
                    </div>
                    
                    <div class="setting-group">
                        <h3>📱 Método de Envio</h3>
                        
                        <div class="checkbox-group">
                            <input type="radio" id="method_both" name="reminder_method" value="both"
                                   <?php echo $reminderMethod === 'both' ? 'checked' : ''; ?>>
                            <label for="method_both">WhatsApp + E-mail</label>
                        </div>
                        
                        <div class="checkbox-group">
                            <input type="radio" id="method_whatsapp" name="reminder_method" value="whatsapp"
                                   <?php echo $reminderMethod === 'whatsapp' ? 'checked' : ''; ?>>
                            <label for="method_whatsapp">Apenas WhatsApp</label>
                        </div>
                        
                        <div class="checkbox-group">
                            <input type="radio" id="method_email" name="reminder_method" value="email"
                                   <?php echo $reminderMethod === 'email' ? 'checked' : ''; ?>>
                            <label for="method_email">Apenas E-mail</label>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Salvar Configurações</button>
                </form>
            </div>

            <!-- TAB: Logs -->
            <div id="logs" class="tab-content">
                <h2>📜 Histórico de Lembretes Enviados</h2>
                
                <div style="margin-bottom: 20px;">
                    <button class="btn btn-secondary" onclick="carregarLogs('hoje')" style="width: auto;">Hoje</button>
                    <button class="btn btn-secondary" onclick="carregarLogs('semana')" style="width: auto;">Esta Semana</button>
                    <button class="btn btn-secondary" onclick="carregarLogs('mes')" style="width: auto;">Este Mês</button>
                    <button class="btn btn-secondary" onclick="carregarLogs('todos')" style="width: auto;">Todos</button>
                </div>
                
                <div id="logs-content">
                    <p style="text-align: center; color: #999;">Carregando logs...</p>
                </div>
            </div>

            <!-- TAB: Envio Manual -->
            <div id="manual" class="tab-content">
                <h2>📤 Enviar Lembrete Manual</h2>
                <p style="color: #666; margin-bottom: 20px;">
                    Envie um lembrete imediato para clientes com agendamento confirmado.
                </p>
                
                <div id="upcoming-events">
                    <p style="text-align: center; color: #999;">Carregando próximos agendamentos...</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        function openTab(tabName) {
            // Esconder todos os conteúdos
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.remove('active');
            });
            
            // Remover active de todas as tabs
            document.querySelectorAll('.tab').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Mostrar conteúdo selecionado
            document.getElementById(tabName).classList.add('active');
            event.target.classList.add('active');
            
            // Carregar dados se necessário
            if (tabName === 'logs') {
                carregarLogs('semana');
            } else if (tabName === 'manual') {
                carregarProximosAgendamentos();
            } else if (tabName === 'statistics') {
                carregarEstatisticas();
            }
        }
        
        function carregarEstatisticas() {
            fetch('get_reminder_stats.php')
                .then(res => res.json())
                .then(data => {
                    document.getElementById('stat-total').textContent = data.total || '0';
                    document.getElementById('stat-hoje').textContent = data.hoje || '0';
                    document.getElementById('stat-semana').textContent = data.semana || '0';
                    document.getElementById('stat-erros').textContent = data.erros || '0';
                })
                .catch(err => console.error('Erro ao carregar estatísticas:', err));
        }
        
        function carregarLogs(periodo) {
            fetch(`get_reminder_logs.php?periodo=${periodo}`)
                .then(res => res.json())
                .then(data => {
                    exibirLogs(data);
                })
                .catch(err => {
                    document.getElementById('logs-content').innerHTML = 
                        '<p style="text-align: center; color: #f44336;">Erro ao carregar logs</p>';
                });
        }
        
        function exibirLogs(logs) {
            const container = document.getElementById('logs-content');
            
            if (!logs || logs.length === 0) {
                container.innerHTML = '<p style="text-align: center; color: #999;">Nenhum lembrete enviado no período selecionado.</p>';
                return;
            }
            
            let html = `
                <table class="logs-table">
                    <thead>
                        <tr>
                            <th>Data/Hora</th>
                            <th>Cliente</th>
                            <th>Tipo</th>
                            <th>Via</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
            `;
            
            logs.forEach(log => {
                const data = new Date(log.sent_at).toLocaleString('pt-BR');
                const statusClass = `status-${log.status}`;
                
                html += `
                    <tr>
                        <td>${data}</td>
                        <td>${log.user_name || 'N/A'}</td>
                        <td>${log.reminder_type}</td>
                        <td>${log.sent_via}</td>
                        <td><span class="status-badge ${statusClass}">${log.status}</span></td>
                    </tr>
                `;
            });
            
            html += '</tbody></table>';
            container.innerHTML = html;
        }
        
        function carregarProximosAgendamentos() {
            fetch('get_upcoming_events.php')
                .then(res => res.json())
                .then(data => {
                    exibirAgendamentosManual(data);
                })
                .catch(err => {
                    document.getElementById('upcoming-events').innerHTML = 
                        '<p style="text-align: center; color: #f44336;">Erro ao carregar agendamentos</p>';
                });
        }
        
        function exibirAgendamentosManual(events) {
            const container = document.getElementById('upcoming-events');
            
            if (!events || events.length === 0) {
                container.innerHTML = '<p style="text-align: center; color: #999;">Nenhum agendamento confirmado nos próximos dias.</p>';
                return;
            }
            
            let html = `
                <table class="logs-table">
                    <thead>
                        <tr>
                            <th>Cliente</th>
                            <th>Serviço</th>
                            <th>Data/Hora</th>
                            <th>Ação</th>
                        </tr>
                    </thead>
                    <tbody>
            `;
            
            events.forEach(event => {
                const data = new Date(event.start).toLocaleString('pt-BR');
                
                html += `
                    <tr>
                        <td>${event.user_name}</td>
                        <td>${event.title}</td>
                        <td>${data}</td>
                        <td>
                            <button class="btn btn-primary" 
                                    onclick="enviarLembreteManual(${event.id})"
                                    style="width: auto; padding: 8px 16px; font-size: 0.85rem;">
                                📤 Enviar Agora
                            </button>
                        </td>
                    </tr>
                `;
            });
            
            html += '</tbody></table>';
            container.innerHTML = html;
        }
        
        function enviarLembreteManual(eventId) {
            if (!confirm('Deseja enviar um lembrete agora para este cliente?')) {
                return;
            }
            
            const formData = new URLSearchParams();
            formData.append('event_id', eventId);
            formData.append('reminder_type', 'now');
            
            fetch('send_reminder.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: formData.toString()
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    alert('✅ ' + data.msg);
                    carregarProximosAgendamentos(); // Recarregar lista
                } else {
                    alert('❌ ' + (data.msg || 'Erro ao enviar lembrete'));
                }
            })
            .catch(err => {
                console.error('Erro:', err);
                alert('❌ Erro ao enviar lembrete');
            });
        }
        
        // Carregar estatísticas ao abrir a página
        carregarEstatisticas();
    </script>
</body>
</html>
