<?php
require 'config.php';
checkLogin();
requireDono();

$user_name = $_SESSION['name'] ?? '';
$msg = '';
$msgType = '';

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add' || $action === 'edit') {
        $id = intval($_POST['id'] ?? 0);
        $name = sanitizeInput($_POST['name'] ?? '');
        $description = sanitizeInput($_POST['description'] ?? '');
        $price = floatval($_POST['price'] ?? 0);
        $duration = intval($_POST['duration'] ?? 60);
        $category = sanitizeInput($_POST['category'] ?? 'geral');
        $points_reward = intval($_POST['points_reward'] ?? 0);
        $active = isset($_POST['active']) ? 1 : 0;

        if (!$name || $price <= 0 || $duration <= 0) {
            $msg = 'Preencha todos os campos obrigatórios corretamente';
            $msgType = 'error';
        } else {
            try {
                if ($action === 'add') {
                    $stmt = $pdo->prepare("
                        INSERT INTO services (name, description, price, duration, category, points_reward, active)
                        VALUES (?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([$name, $description, $price, $duration, $category, $points_reward, $active]);
                    $msg = 'Serviço adicionado com sucesso!';
                    $msgType = 'success';
                    logAuditoria('servico_criado', "Serviço criado: {$name}");
                } else {
                    $stmt = $pdo->prepare("
                        UPDATE services 
                        SET name = ?, description = ?, price = ?, duration = ?, 
                            category = ?, points_reward = ?, active = ?
                        WHERE id = ?
                    ");
                    $stmt->execute([$name, $description, $price, $duration, $category, $points_reward, $active, $id]);
                    $msg = 'Serviço atualizado com sucesso!';
                    $msgType = 'success';
                    logAuditoria('servico_editado', "Serviço editado: {$name}");
                }
            } catch (PDOException $e) {
                error_log("Erro ao salvar serviço: " . $e->getMessage());
                $msg = 'Erro ao salvar serviço';
                $msgType = 'error';
            }
        }
    } elseif ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        
        try {
            // Verificar se há agendamentos usando este serviço
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM events WHERE service_id = ? AND status IN ('pendente', 'agendado')");
            $stmt->execute([$id]);
            $count = $stmt->fetchColumn();

            if ($count > 0) {
                $msg = "Não é possível excluir. Existem {$count} agendamentos usando este serviço.";
                $msgType = 'warning';
            } else {
                // Soft delete - apenas desativar
                $stmt = $pdo->prepare("UPDATE services SET active = 0 WHERE id = ?");
                $stmt->execute([$id]);
                $msg = 'Serviço desativado com sucesso!';
                $msgType = 'success';
                logAuditoria('servico_desativado', "Serviço ID {$id} desativado");
            }
        } catch (PDOException $e) {
            error_log("Erro ao excluir serviço: " . $e->getMessage());
            $msg = 'Erro ao excluir serviço';
            $msgType = 'error';
        }
    } elseif ($action === 'toggle_active') {
        $id = intval($_POST['id'] ?? 0);
        
        try {
            $stmt = $pdo->prepare("UPDATE services SET active = NOT active WHERE id = ?");
            $stmt->execute([$id]);
            $msg = 'Status atualizado!';
            $msgType = 'success';
        } catch (PDOException $e) {
            $msg = 'Erro ao atualizar status';
            $msgType = 'error';
        }
    }
}

// Buscar todos os serviços
$filter = $_GET['filter'] ?? 'all';
$searchQuery = "SELECT * FROM services";
if ($filter === 'active') {
    $searchQuery .= " WHERE active = 1";
} elseif ($filter === 'inactive') {
    $searchQuery .= " WHERE active = 0";
}
$searchQuery .= " ORDER BY name ASC";

$services = $pdo->query($searchQuery)->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Serviços - <?php echo SITE_NAME; ?></title>
    <link href="styles.css" rel="stylesheet">
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">💅</div>
            <div>
                <h1>⚙️ Gerenciar Serviços</h1>
                <p class="subtitle">Olá, <?php echo htmlspecialchars($user_name); ?>!</p>
            </div>
            <div style="margin-left: auto; display: flex; gap: 10px; flex-wrap: wrap;">
                <a href="dashboard.php" class="btn btn-secondary">📊 Dashboard</a>
                <a href="index.php" class="btn btn-secondary">📅 Calendário</a>
                <a href="logout.php" class="btn btn-secondary">Sair</a>
            </div>
        </div>

        <?php if ($msg): ?>
            <div class="alert alert-<?php echo $msgType; ?>">
                <?php echo htmlspecialchars($msg); ?>
            </div>
        <?php endif; ?>

        <!-- Botões de Ação -->
        <div class="card" style="margin-bottom: 24px;">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
                <div style="display: flex; gap: 10px;">
                    <button onclick="openModal('add')" class="btn btn-primary">
                        ➕ Adicionar Serviço
                    </button>
                    <a href="?filter=all" class="btn btn-secondary <?php echo $filter === 'all' ? 'active' : ''; ?>">
                        Todos
                    </a>
                    <a href="?filter=active" class="btn btn-secondary <?php echo $filter === 'active' ? 'active' : ''; ?>">
                        Ativos
                    </a>
                    <a href="?filter=inactive" class="btn btn-secondary <?php echo $filter === 'inactive' ? 'active' : ''; ?>">
                        Inativos
                    </a>
                </div>
                <div>
                    <strong><?php echo count($services); ?></strong> serviços encontrados
                </div>
            </div>
        </div>

        <!-- Lista de Serviços -->
        <div class="services-grid">
            <?php foreach ($services as $service): ?>
                <div class="service-card <?php echo $service['active'] ? '' : 'inactive'; ?>">
                    <?php if (!$service['active']): ?>
                        <div class="inactive-badge">Inativo</div>
                    <?php endif; ?>
                    
                    <div class="service-header">
                        <h3><?php echo htmlspecialchars($service['name']); ?></h3>
                        <span class="service-category"><?php echo htmlspecialchars($service['category']); ?></span>
                    </div>

                    <p class="service-description">
                        <?php echo htmlspecialchars($service['description']) ?: 'Sem descrição'; ?>
                    </p>

                    <div class="service-details">
                        <div class="detail-item">
                            <strong>💰 Preço:</strong>
                            <?php echo formatCurrency($service['price']); ?>
                        </div>
                        <div class="detail-item">
                            <strong>⏱️ Duração:</strong>
                            <?php echo $service['duration']; ?> min
                        </div>
                        <div class="detail-item">
                            <strong>🎁 Pontos:</strong>
                            <?php echo $service['points_reward']; ?> pts
                        </div>
                    </div>

                    <div class="service-actions">
                        <button onclick='editService(<?php echo json_encode($service); ?>)' class="btn-icon btn-edit" title="Editar">
                            ✏️
                        </button>
                        <form method="POST" style="display: inline;" onsubmit="return confirm('Alterar status deste serviço?');">
                            <input type="hidden" name="action" value="toggle_active">
                            <input type="hidden" name="id" value="<?php echo $service['id']; ?>">
                            <button type="submit" class="btn-icon btn-toggle" title="Ativar/Desativar">
                                <?php echo $service['active'] ? '👁️' : '🚫'; ?>
                            </button>
                        </form>
                        <form method="POST" style="display: inline;" onsubmit="return confirm('Tem certeza? Esta ação irá desativar o serviço.');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?php echo $service['id']; ?>">
                            <button type="submit" class="btn-icon btn-delete" title="Desativar">
                                🗑️
                            </button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>

            <?php if (count($services) === 0): ?>
                <div style="grid-column: 1/-1; text-align: center; padding: 60px 20px; color: #999;">
                    <div style="font-size: 4rem; margin-bottom: 20px;">🔍</div>
                    <h3>Nenhum serviço encontrado</h3>
                    <p>Adicione seu primeiro serviço usando o botão acima</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal de Adicionar/Editar -->
    <div id="modal-service" class="modal" style="display: none;">
        <div class="modal-content" style="max-width: 600px;">
            <span class="close" onclick="closeModal()">&times;</span>
            <h2 id="modal-title">➕ Adicionar Serviço</h2>
            
            <form method="POST" id="form-service">
                <input type="hidden" name="action" id="form-action" value="add">
                <input type="hidden" name="id" id="form-id" value="0">

                <div class="form-group">
                    <label for="name">Nome do Serviço *</label>
                    <input type="text" id="name" name="name" class="input" required>
                </div>

                <div class="form-group">
                    <label for="description">Descrição</label>
                    <textarea id="description" name="description" class="input" rows="3"></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="price">Preço (R$) *</label>
                        <input type="number" id="price" name="price" class="input" step="0.01" min="0" required>
                    </div>

                    <div class="form-group">
                        <label for="duration">Duração (min) *</label>
                        <input type="number" id="duration" name="duration" class="input" min="1" required value="60">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="category">Categoria</label>
                        <select id="category" name="category" class="input">
                            <option value="geral">Geral</option>
                            <option value="manicure">Manicure</option>
                            <option value="pedicure">Pedicure</option>
                            <option value="combo">Combo</option>
                            <option value="tratamento">Tratamento</option>
                            <option value="spa">Spa</option>
                            <option value="design">Design</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="points_reward">Pontos de Fidelidade</label>
                        <input type="number" id="points_reward" name="points_reward" class="input" min="0" value="0">
                    </div>
                </div>

                <div class="form-group">
                    <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                        <input type="checkbox" id="active" name="active" checked>
                        Serviço ativo
                    </label>
                </div>

                <div style="display: flex; gap: 10px; margin-top: 24px;">
                    <button type="submit" class="btn btn-primary" style="flex: 1;">Salvar</button>
                    <button type="button" onclick="closeModal()" class="btn btn-secondary" style="flex: 1;">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    <style>
        .card {
            background: white;
            padding: 24px;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
        }

        .services-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 20px;
        }

        .service-card {
            background: white;
            padding: 24px;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            transition: var(--transition);
            position: relative;
            border-left: 4px solid var(--gold);
        }

        .service-card.inactive {
            opacity: 0.6;
            border-left-color: #999;
        }

        .service-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.12);
        }

        .inactive-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background: #999;
            color: white;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .service-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 12px;
            gap: 10px;
        }

        .service-header h3 {
            color: var(--brown);
            font-size: 1.2rem;
            margin: 0;
            flex: 1;
        }

        .service-category {
            background: var(--beige-light);
            color: var(--brown);
            padding: 4px 10px;
            border-radius: 8px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .service-description {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 16px;
            line-height: 1.5;
        }

        .service-details {
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin-bottom: 16px;
            padding: 12px;
            background: var(--beige-light);
            border-radius: 8px;
        }

        .detail-item {
            font-size: 0.9rem;
            color: #333;
        }

        .detail-item strong {
            color: var(--brown);
        }

        .service-actions {
            display: flex;
            gap: 8px;
            padding-top: 16px;
            border-top: 1px solid var(--beige-light);
        }

        .btn-icon {
            background: none;
            border: 2px solid var(--beige);
            padding: 8px 16px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1.2rem;
            transition: var(--transition);
            flex: 1;
        }

        .btn-icon:hover {
            transform: scale(1.1);
        }

        .btn-edit:hover {
            background: #2196f3;
            border-color: #2196f3;
        }

        .btn-toggle:hover {
            background: #ff9800;
            border-color: #ff9800;
        }

        .btn-delete:hover {
            background: #f44336;
            border-color: #f44336;
        }

        .alert {
            padding: 16px 20px;
            border-radius: 12px;
            margin-bottom: 24px;
            font-weight: 500;
        }

        .alert-success {
            background: #e8f5e9;
            color: #2e7d32;
            border-left: 4px solid #4caf50;
        }

        .alert-error {
            background: #ffebee;
            color: #c62828;
            border-left: 4px solid #f44336;
        }

        .alert-warning {
            background: #fff3e0;
            color: #e65100;
            border-left: 4px solid #ff9800;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        @media (max-width: 768px) {
            .services-grid {
                grid-template-columns: 1fr;
            }

            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <script>
        function openModal(action) {
            const modal = document.getElementById('modal-service');
            const form = document.getElementById('form-service');
            const title = document.getElementById('modal-title');
            
            form.reset();
            document.getElementById('form-action').value = action;
            document.getElementById('form-id').value = '0';
            
            if (action === 'add') {
                title.textContent = '➕ Adicionar Serviço';
            }
            
            modal.style.display = 'block';
        }

        function editService(service) {
            const modal = document.getElementById('modal-service');
            const title = document.getElementById('modal-title');
            
            document.getElementById('form-action').value = 'edit';
            document.getElementById('form-id').value = service.id;
            document.getElementById('name').value = service.name;
            document.getElementById('description').value = service.description || '';
            document.getElementById('price').value = service.price;
            document.getElementById('duration').value = service.duration;
            document.getElementById('category').value = service.category;
            document.getElementById('points_reward').value = service.points_reward;
            document.getElementById('active').checked = service.active == 1;
            
            title.textContent = '✏️ Editar Serviço';
            modal.style.display = 'block';
        }

        function closeModal() {
            document.getElementById('modal-service').style.display = 'none';
        }

        window.onclick = function(event) {
            const modal = document.getElementById('modal-service');
            if (event.target == modal) {
                closeModal();
            }
        }
    </script>
</body>
</html>
