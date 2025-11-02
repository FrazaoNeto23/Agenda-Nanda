<?php
require 'config.php';
checkLogin();
requireDonoOrFuncionario();

$user_name = $_SESSION['name'] ?? '';

// Período de análise (padrão: mês atual)
$periodo_inicio = $_GET['periodo_inicio'] ?? date('Y-m-01');
$periodo_fim = $_GET['periodo_fim'] ?? date('Y-m-t');

// Estatísticas gerais
try {
    // Total de agendamentos no período
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'concluido' THEN 1 ELSE 0 END) as concluidos,
            SUM(CASE WHEN status = 'cancelado' THEN 1 ELSE 0 END) as cancelados,
            SUM(CASE WHEN status = 'pendente' THEN 1 ELSE 0 END) as pendentes,
            SUM(CASE WHEN status = 'agendado' THEN 1 ELSE 0 END) as agendados
        FROM events
        WHERE DATE(start) BETWEEN :inicio AND :fim
    ");
    $stmt->execute([':inicio' => $periodo_inicio, ':fim' => $periodo_fim]);
    $stats = $stmt->fetch();

    // Faturamento
    $stmt = $pdo->prepare("
        SELECT 
            SUM(s.price) as faturamento_total,
            AVG(s.price) as ticket_medio,
            COUNT(DISTINCT e.user_id) as clientes_unicos
        FROM events e
        INNER JOIN services s ON e.service_id = s.id
        WHERE e.status = 'concluido'
        AND DATE(e.start) BETWEEN :inicio AND :fim
    ");
    $stmt->execute([':inicio' => $periodo_inicio, ':fim' => $periodo_fim]);
    $faturamento = $stmt->fetch();

    // Serviços mais populares
    $stmt = $pdo->prepare("
        SELECT 
            s.name,
            COUNT(e.id) as total,
            SUM(CASE WHEN e.status = 'concluido' THEN s.price ELSE 0 END) as receita
        FROM events e
        INNER JOIN services s ON e.service_id = s.id
        WHERE DATE(e.start) BETWEEN :inicio AND :fim
        GROUP BY s.id, s.name
        ORDER BY total DESC
        LIMIT 10
    ");
    $stmt->execute([':inicio' => $periodo_inicio, ':fim' => $periodo_fim]);
    $servicos_populares = $stmt->fetchAll();

    // Horários de pico
    $stmt = $pdo->prepare("
        SELECT 
            HOUR(start) as hora,
            COUNT(*) as total
        FROM events
        WHERE DATE(start) BETWEEN :inicio AND :fim
        AND status IN ('agendado', 'concluido')
        GROUP BY HOUR(start)
        ORDER BY total DESC
        LIMIT 5
    ");
    $stmt->execute([':inicio' => $periodo_inicio, ':fim' => $periodo_fim]);
    $horarios_pico = $stmt->fetchAll();

    // Agendamentos por dia da semana
    $stmt = $pdo->prepare("
        SELECT 
            DAYOFWEEK(start) as dia_semana,
            COUNT(*) as total
        FROM events
        WHERE DATE(start) BETWEEN :inicio AND :fim
        AND status IN ('agendado', 'concluido')
        GROUP BY DAYOFWEEK(start)
        ORDER BY dia_semana
    ");
    $stmt->execute([':inicio' => $periodo_inicio, ':fim' => $periodo_fim]);
    $por_dia_semana = $stmt->fetchAll();

    // Top clientes
    $stmt = $pdo->prepare("
        SELECT 
            u.name,
            u.email,
            u.phone,
            COUNT(e.id) as total_agendamentos,
            SUM(CASE WHEN e.status = 'concluido' THEN s.price ELSE 0 END) as total_gasto
        FROM users u
        INNER JOIN events e ON u.id = e.user_id
        LEFT JOIN services s ON e.service_id = s.id
        WHERE DATE(e.start) BETWEEN :inicio AND :fim
        GROUP BY u.id
        ORDER BY total_agendamentos DESC
        LIMIT 10
    ");
    $stmt->execute([':inicio' => $periodo_inicio, ':fim' => $periodo_fim]);
    $top_clientes = $stmt->fetchAll();

    // Taxa de cancelamento
    $taxa_cancelamento = $stats['total'] > 0 
        ? round(($stats['cancelados'] / $stats['total']) * 100, 1) 
        : 0;

    // Taxa de conversão (pendente -> agendado/concluído)
    $taxa_conversao = ($stats['pendentes'] + $stats['agendados'] + $stats['concluidos']) > 0
        ? round((($stats['agendados'] + $stats['concluidos']) / ($stats['pendentes'] + $stats['agendados'] + $stats['concluidos'])) * 100, 1)
        : 0;

} catch (PDOException $e) {
    error_log("Erro ao buscar estatísticas: " . $e->getMessage());
    $error = "Erro ao carregar estatísticas";
}

$dias_semana = ['', 'Domingo', 'Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado'];
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo SITE_NAME; ?></title>
    <link href="styles.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">💅</div>
            <div>
                <h1>📊 Dashboard</h1>
                <p class="subtitle">Olá, <?php echo htmlspecialchars($user_name); ?>!</p>
            </div>
            <div style="margin-left: auto; display: flex; gap: 10px; flex-wrap: wrap;">
                <a href="index.php" class="btn btn-secondary">📅 Calendário</a>
                <a href="services_manage.php" class="btn btn-secondary">⚙️ Serviços</a>
                <a href="logout.php" class="btn btn-secondary">Sair</a>
            </div>
        </div>

        <!-- Filtro de Período -->
        <div class="card" style="margin-bottom: 24px;">
            <form method="GET" style="display: flex; gap: 15px; align-items: end; flex-wrap: wrap;">
                <div class="form-group" style="margin: 0; flex: 1; min-width: 200px;">
                    <label for="periodo_inicio">Data Início</label>
                    <input type="date" id="periodo_inicio" name="periodo_inicio" class="input" 
                           value="<?php echo htmlspecialchars($periodo_inicio); ?>">
                </div>
                <div class="form-group" style="margin: 0; flex: 1; min-width: 200px;">
                    <label for="periodo_fim">Data Fim</label>
                    <input type="date" id="periodo_fim" name="periodo_fim" class="input" 
                           value="<?php echo htmlspecialchars($periodo_fim); ?>">
                </div>
                <button type="submit" class="btn btn-primary" style="width: auto;">Filtrar</button>
                <a href="dashboard.php" class="btn btn-secondary" style="width: auto;">Limpar</a>
            </form>
        </div>

        <!-- Cards de Estatísticas -->
        <div class="stats-grid">
            <div class="stat-card stat-primary">
                <div class="stat-icon">📅</div>
                <div class="stat-content">
                    <h3><?php echo number_format($stats['total']); ?></h3>
                    <p>Total de Agendamentos</p>
                </div>
            </div>

            <div class="stat-card stat-success">
                <div class="stat-icon">✅</div>
                <div class="stat-content">
                    <h3><?php echo number_format($stats['concluidos']); ?></h3>
                    <p>Concluídos</p>
                </div>
            </div>

            <div class="stat-card stat-warning">
                <div class="stat-icon">⏳</div>
                <div class="stat-content">
                    <h3><?php echo number_format($stats['pendentes']); ?></h3>
                    <p>Pendentes</p>
                </div>
            </div>

            <div class="stat-card stat-danger">
                <div class="stat-icon">❌</div>
                <div class="stat-content">
                    <h3><?php echo number_format($stats['cancelados']); ?></h3>
                    <p>Cancelados</p>
                </div>
            </div>

            <div class="stat-card stat-gold">
                <div class="stat-icon">💰</div>
                <div class="stat-content">
                    <h3><?php echo formatCurrency($faturamento['faturamento_total'] ?? 0); ?></h3>
                    <p>Faturamento Total</p>
                </div>
            </div>

            <div class="stat-card stat-info">
                <div class="stat-icon">🎫</div>
                <div class="stat-content">
                    <h3><?php echo formatCurrency($faturamento['ticket_medio'] ?? 0); ?></h3>
                    <p>Ticket Médio</p>
                </div>
            </div>

            <div class="stat-card stat-purple">
                <div class="stat-icon">👥</div>
                <div class="stat-content">
                    <h3><?php echo number_format($faturamento['clientes_unicos'] ?? 0); ?></h3>
                    <p>Clientes Únicos</p>
                </div>
            </div>

            <div class="stat-card stat-orange">
                <div class="stat-icon">📊</div>
                <div class="stat-content">
                    <h3><?php echo $taxa_conversao; ?>%</h3>
                    <p>Taxa de Conversão</p>
                </div>
            </div>
        </div>

        <!-- Gráficos -->
        <div class="charts-grid">
            <!-- Gráfico de Status -->
            <div class="card">
                <h3>📊 Distribuição por Status</h3>
                <canvas id="chartStatus"></canvas>
            </div>

            <!-- Gráfico de Serviços Populares -->
            <div class="card">
                <h3>⭐ Serviços Mais Populares</h3>
                <canvas id="chartServicos"></canvas>
            </div>

            <!-- Gráfico de Horários de Pico -->
            <div class="card">
                <h3>🕐 Horários de Maior Movimento</h3>
                <canvas id="chartHorarios"></canvas>
            </div>

            <!-- Gráfico por Dia da Semana -->
            <div class="card">
                <h3>📅 Agendamentos por Dia da Semana</h3>
                <canvas id="chartDiaSemana"></canvas>
            </div>
        </div>

        <!-- Tabelas -->
        <div class="tables-grid">
            <!-- Top Clientes -->
            <div class="card">
                <h3>👑 Top 10 Clientes</h3>
                <div class="table-responsive">
                    <table class="dashboard-table">
                        <thead>
                            <tr>
                                <th>Cliente</th>
                                <th>Agendamentos</th>
                                <th>Total Gasto</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($top_clientes) > 0): ?>
                                <?php foreach ($top_clientes as $cliente): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($cliente['name']); ?></strong><br>
                                            <small><?php echo htmlspecialchars($cliente['email']); ?></small>
                                        </td>
                                        <td><?php echo $cliente['total_agendamentos']; ?></td>
                                        <td><?php echo formatCurrency($cliente['total_gasto']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3" style="text-align: center; color: #999;">Nenhum cliente no período</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Serviços Populares (Tabela) -->
            <div class="card">
                <h3>💅 Serviços Mais Solicitados</h3>
                <div class="table-responsive">
                    <table class="dashboard-table">
                        <thead>
                            <tr>
                                <th>Serviço</th>
                                <th>Quantidade</th>
                                <th>Receita</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($servicos_populares) > 0): ?>
                                <?php foreach ($servicos_populares as $servico): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($servico['name']); ?></td>
                                        <td><?php echo $servico['total']; ?></td>
                                        <td><?php echo formatCurrency($servico['receita']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3" style="text-align: center; color: #999;">Nenhum serviço no período</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

    <style>
        .card {
            background: white;
            padding: 24px;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 24px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            display: flex;
            align-items: center;
            gap: 16px;
            transition: var(--transition);
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.12);
        }

        .stat-icon {
            font-size: 2.5rem;
            line-height: 1;
        }

        .stat-content h3 {
            font-size: 1.8rem;
            font-weight: 700;
            margin: 0 0 4px 0;
            color: var(--brown);
        }

        .stat-content p {
            font-size: 0.9rem;
            color: #666;
            margin: 0;
        }

        .stat-primary { border-left: 4px solid #2196f3; }
        .stat-success { border-left: 4px solid #4caf50; }
        .stat-warning { border-left: 4px solid #ff9800; }
        .stat-danger { border-left: 4px solid #f44336; }
        .stat-gold { border-left: 4px solid var(--gold); }
        .stat-info { border-left: 4px solid #00bcd4; }
        .stat-purple { border-left: 4px solid #9c27b0; }
        .stat-orange { border-left: 4px solid #ff5722; }

        .charts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 20px;
            margin-bottom: 24px;
        }

        .tables-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 20px;
        }

        .card h3 {
            margin-bottom: 20px;
            color: var(--brown);
        }

        .table-responsive {
            overflow-x: auto;
        }

        .dashboard-table {
            width: 100%;
            border-collapse: collapse;
        }

        .dashboard-table th {
            background: var(--beige-light);
            padding: 12px;
            text-align: left;
            font-weight: 600;
            color: var(--brown);
        }

        .dashboard-table td {
            padding: 12px;
            border-bottom: 1px solid var(--beige-light);
        }

        .dashboard-table tr:hover {
            background: #fafafa;
        }

        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .charts-grid,
            .tables-grid {
                grid-template-columns: 1fr;
            }

            .stat-card {
                flex-direction: column;
                text-align: center;
            }
        }
    </style>

    <script>
        // Configurações globais dos gráficos
        Chart.defaults.font.family = 'Poppins, sans-serif';

        // Gráfico de Status
        new Chart(document.getElementById('chartStatus'), {
            type: 'doughnut',
            data: {
                labels: ['Concluídos', 'Agendados', 'Pendentes', 'Cancelados'],
                datasets: [{
                    data: [
                        <?php echo $stats['concluidos']; ?>,
                        <?php echo $stats['agendados']; ?>,
                        <?php echo $stats['pendentes']; ?>,
                        <?php echo $stats['cancelados']; ?>
                    ],
                    backgroundColor: ['#4caf50', '#c89b4b', '#ff9800', '#f44336']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });

        // Gráfico de Serviços Populares
        new Chart(document.getElementById('chartServicos'), {
            type: 'bar',
            data: {
                labels: [<?php foreach($servicos_populares as $s) echo '"'.addslashes($s['name']).'",'; ?>],
                datasets: [{
                    label: 'Quantidade',
                    data: [<?php foreach($servicos_populares as $s) echo $s['total'].','; ?>],
                    backgroundColor: '#c89b4b'
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });

        // Gráfico de Horários
        new Chart(document.getElementById('chartHorarios'), {
            type: 'line',
            data: {
                labels: [<?php foreach($horarios_pico as $h) echo $h['hora'].'h,'; ?>],
                datasets: [{
                    label: 'Agendamentos',
                    data: [<?php foreach($horarios_pico as $h) echo $h['total'].','; ?>],
                    borderColor: '#5a3a1c',
                    backgroundColor: 'rgba(200, 155, 75, 0.1)',
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });

        // Gráfico por Dia da Semana
        const diasSemana = <?php echo json_encode(array_column($por_dia_semana, 'dia_semana')); ?>;
        const diasNomes = ['', 'Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'];
        const labels = diasSemana.map(d => diasNomes[d]);

        new Chart(document.getElementById('chartDiaSemana'), {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Agendamentos',
                    data: [<?php foreach($por_dia_semana as $d) echo $d['total'].','; ?>],
                    backgroundColor: '#2196f3'
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });
    </script>
</body>
</html>
