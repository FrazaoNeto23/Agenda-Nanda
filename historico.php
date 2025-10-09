<?php
require 'config.php';
checkLogin();
$user_role = $_SESSION['role'] ?? '';
$user_id = $_SESSION['user_id'] ?? 0;
$user_name = $_SESSION['name'] ?? '';
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Histórico - Agenda Manicure</title>
    <link href="styles.css" rel="stylesheet">
    <style>
        .historico-container {
            background: white;
            padding: 28px;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            margin-top: 24px;
        }

        .filtros {
            display: flex;
            gap: 15px;
            margin-bottom: 24px;
            flex-wrap: wrap;
        }

        .filtros select,
        .filtros input {
            padding: 10px 14px;
            border: 2px solid var(--beige-light);
            border-radius: 8px;
            font-family: 'Poppins', sans-serif;
        }

        .historico-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .historico-table th {
            background: linear-gradient(135deg, var(--gold), var(--brown));
            color: white;
            padding: 14px;
            text-align: left;
            font-weight: 600;
        }

        .historico-table td {
            padding: 12px 14px;
            border-bottom: 1px solid var(--beige-light);
        }

        .historico-table tr:hover {
            background: var(--beige-light);
        }

        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .status-pendente {
            background: #fff3e0;
            color: #e65100;
        }

        .status-agendado {
            background: #fff8e1;
            color: #f57f17;
        }

        .status-concluido {
            background: #e8f5e9;
            color: #2e7d32;
        }

        .status-cancelado {
            background: #ffebee;
            color: #c62828;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }

        .empty-state svg {
            width: 80px;
            height: 80px;
            margin-bottom: 20px;
            opacity: 0.3;
        }

        @media (max-width: 768px) {
            .historico-table {
                font-size: 0.9rem;
            }

            .historico-table th,
            .historico-table td {
                padding: 8px;
            }

            .filtros {
                flex-direction: column;
            }

            .filtros select,
            .filtros input {
                width: 100%;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <div class="logo">💅</div>
            <div>
                <h1>Histórico de Agendamentos</h1>
                <p class="subtitle">Olá, <?php echo htmlspecialchars($user_name); ?>!</p>
            </div>
            <div style="margin-left: auto; display: flex; gap: 10px;">
                <a href="index.php" class="btn btn-secondary">Voltar ao Calendário</a>
                <a href="logout.php" class="btn btn-secondary">Sair</a>
            </div>
        </div>

        <div class="historico-container">
            <h2>📜 Histórico Completo</h2>

            <div class="filtros">
                <select id="filtro-status" onchange="filtrarHistorico()">
                    <option value="">Todos os Status</option>
                    <option value="pendente">Pendentes</option>
                    <option value="agendado">Confirmados</option>
                    <option value="concluido">Concluídos</option>
                    <option value="cancelado">Cancelados</option>
                </select>

                <input type="date" id="filtro-data-inicio" onchange="filtrarHistorico()" placeholder="Data inicial">
                <input type="date" id="filtro-data-fim" onchange="filtrarHistorico()" placeholder="Data final">

                <button class="btn btn-primary" onclick="limparFiltros()" style="width: auto;">Limpar Filtros</button>
            </div>

            <div id="historico-content">
                <div class="empty-state">
                    <p>Carregando histórico...</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        let historicoCompleto = [];

        function carregarHistorico() {
            fetch('get_historico.php')
                .then(res => res.json())
                .then(data => {
                    historicoCompleto = data;
                    exibirHistorico(data);
                })
                .catch(err => {
                    console.error('Erro ao carregar histórico:', err);
                    document.getElementById('historico-content').innerHTML = `
            <div class="empty-state">
              <p style="color: #f44336;">❌ Erro ao carregar histórico</p>
            </div>
          `;
                });
        }

        function exibirHistorico(eventos) {
            const container = document.getElementById('historico-content');

            if (!eventos || eventos.length === 0) {
                container.innerHTML = `
          <div class="empty-state">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <circle cx="12" cy="12" r="10"></circle>
              <line x1="12" y1="8" x2="12" y2="12"></line>
              <line x1="12" y1="16" x2="12.01" y2="16"></line>
            </svg>
            <h3>Nenhum agendamento encontrado</h3>
            <p>Não há agendamentos no histórico com os filtros selecionados.</p>
          </div>
        `;
                return;
            }

            let html = `
        <table class="historico-table">
          <thead>
            <tr>
              <th>Data/Hora</th>
              <th>Serviço</th>
              <?php if ($user_role === 'dono'): ?>
              <th>Cliente</th>
              <?php endif; ?>
              <th>Status</th>
              <th>Ações</th>
            </tr>
          </thead>
          <tbody>
      `;

            eventos.forEach(evento => {
                const statusClass = 'status-' + evento.status;
                const statusTexto = {
                    'pendente': '⏳ Pendente',
                    'agendado': '✅ Confirmado',
                    'concluido': '✔️ Concluído',
                    'cancelado': '❌ Cancelado'
                }[evento.status] || evento.status;

                const dataHora = new Date(evento.start).toLocaleString('pt-BR');

                html += `
          <tr>
            <td>${dataHora}</td>
            <td>${evento.title}</td>
            <?php if ($user_role === 'dono'): ?>
            <td>${evento.user_name || 'N/A'}</td>
            <?php endif; ?>
            <td><span class="status-badge ${statusClass}">${statusTexto}</span></td>
            <td>
              <button class="btn btn-secondary" onclick="verDetalhes(${evento.id})" style="width: auto; padding: 6px 12px; font-size: 0.85rem;">
                Ver Detalhes
              </button>
            </td>
          </tr>
        `;
            });

            html += `
          </tbody>
        </table>
      `;

            container.innerHTML = html;
        }

        function filtrarHistorico() {
            const status = document.getElementById('filtro-status').value;
            const dataInicio = document.getElementById('filtro-data-inicio').value;
            const dataFim = document.getElementById('filtro-data-fim').value;

            let filtrados = historicoCompleto.filter(evento => {
                let passa = true;

                if (status && evento.status !== status) {
                    passa = false;
                }

                if (dataInicio) {
                    const eventoData = new Date(evento.start).toISOString().split('T')[0];
                    if (eventoData < dataInicio) {
                        passa = false;
                    }
                }

                if (dataFim) {
                    const eventoData = new Date(evento.start).toISOString().split('T')[0];
                    if (eventoData > dataFim) {
                        passa = false;
                    }
                }

                return passa;
            });

            exibirHistorico(filtrados);
        }

        function limparFiltros() {
            document.getElementById('filtro-status').value = '';
            document.getElementById('filtro-data-inicio').value = '';
            document.getElementById('filtro-data-fim').value = '';
            exibirHistorico(historicoCompleto);
        }

        function verDetalhes(eventId) {
            fetch(`get_event_details.php?id=${eventId}`)
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        mostrarModalDetalhes(data.event, data.historico);
                    } else {
                        alert('Erro ao carregar detalhes');
                    }
                })
                .catch(err => {
                    console.error('Erro:', err);
                    alert('Erro ao carregar detalhes');
                });
        }

        function mostrarModalDetalhes(evento, historico) {
            const dataHora = new Date(evento.start).toLocaleString('pt-BR');
            const statusTexto = {
                'pendente': '⏳ Pendente',
                'agendado': '✅ Confirmado',
                'concluido': '✔️ Concluído',
                'cancelado': '❌ Cancelado'
            }[evento.status] || evento.status;

            let historicoHtml = '';
            if (historico && historico.length > 0) {
                historicoHtml = '<h4 style="margin-top: 20px;">📋 Histórico de Mudanças:</h4><ul style="margin-top: 10px;">';
                historico.forEach(h => {
                    const dataAcao = new Date(h.created_at).toLocaleString('pt-BR');
                    historicoHtml += `<li style="margin: 8px 0;"><strong>${dataAcao}</strong>: ${h.descricao}</li>`;
                });
                historicoHtml += '</ul>';
            }

            const modal = `
        <div id="modal-detalhes" style="display: block; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6);">
          <div style="background: white; margin: 80px auto; padding: 40px; border-radius: 16px; width: 90%; max-width: 600px; box-shadow: 0 8px 32px rgba(0,0,0,0.2); position: relative;">
            <span onclick="fecharModal()" style="position: absolute; right: 24px; top: 24px; font-size: 2rem; cursor: pointer; color: #5a3a1c;">&times;</span>
            <h2 style="color: #5a3a1c; margin-bottom: 20px;">📋 Detalhes do Agendamento</h2>
            <p><strong>Serviço:</strong> ${evento.title}</p>
            <p><strong>Data/Hora:</strong> ${dataHora}</p>
            <p><strong>Status:</strong> ${statusTexto}</p>
            ${evento.user_name ? `<p><strong>Cliente:</strong> ${evento.user_name}</p>` : ''}
            ${evento.user_email ? `<p><strong>Email:</strong> ${evento.user_email}</p>` : ''}
            ${historicoHtml}
            <button onclick="fecharModal()" class="btn btn-primary" style="margin-top: 20px; width: auto;">Fechar</button>
          </div>
        </div>
      `;

            document.body.insertAdjacentHTML('beforeend', modal);
        }

        function fecharModal() {
            const modal = document.getElementById('modal-detalhes');
            if (modal) modal.remove();
        }

        carregarHistorico();
    </script>
</body>

</html>