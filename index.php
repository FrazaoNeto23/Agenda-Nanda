<?php
require 'config.php';

// buscar agendamentos ordenando por data e hora
$stmt = $pdo->query("SELECT * FROM appointments ORDER BY date ASC, time ASC");
$appointments = $stmt->fetchAll();

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?>

<!doctype html>
<html lang="pt-br">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Agenda - Manicure</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>

<body>
    <div class="container">
        <div class="header">
            <div class="logo">M</div>
            <div>
                <h1>Agenda Manicure</h1>
                <div class="note">Organize seus atendimentos de forma prática</div>
            </div>
        </div>

        <?php if ($flash): ?>
            <div class="alert alert-info mt-3"><?= htmlspecialchars($flash) ?></div>
        <?php endif; ?>

        <h3 class="mt-3">Novo Agendamento</h3>
        <form action="add.php" method="post">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <div class="form-row">
                <input class="input form-control" name="client_name" placeholder="Nome do cliente" required>
                <input class="input form-control" name="phone" placeholder="Telefone (opcional)" type="tel">
                <select name="service" class="form-control" required>
                    <option value="">Selecione o serviço</option>
                    <option>Manicure Simples</option>
                    <option>Manicure + Esmaltação</option>
                    <option>Unhas de Gel</option>
                    <option>Alongamento</option>
                </select>
            </div>
            <div class="form-row">
                <input type="date" name="date" class="form-control" required>
                <input type="time" name="time" class="form-control" required>
            </div>
            <div class="form-row">
                <textarea name="notes" class="form-control" placeholder="Observações (ex: cor, alergias)"></textarea>
            </div>
            <div style="margin-top:10px">
                <button class="btn">Agendar</button>
            </div>
        </form>

        <h3 class="mt-4">Próximos Agendamentos</h3>
        <table class="table">
            <thead>
                <tr>
                    <th>Cliente</th>
                    <th>Serviço</th>
                    <th>Data</th>
                    <th>Hora</th>
                    <th>Telefone</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($appointments)): ?>
                    <tr>
                        <td colspan="6">Nenhum agendamento encontrado.</td>
                    </tr>
                <?php else:
                    foreach ($appointments as $a): ?>
                        <tr>
                            <td><?= htmlspecialchars($a['client_name']) ?></td>
                            <td><?= htmlspecialchars($a['service']) ?></td>
                            <td><?= htmlspecialchars($a['date']) ?></td>
                            <td><?= htmlspecialchars(substr($a['time'], 0, 5)) ?></td>
                            <td><?= htmlspecialchars($a['phone']) ?></td>
                            <td class="actions">
                                <a href="edit.php?id=<?= $a['id'] ?>" class="btn btn-sm btn-outline-primary">Editar</a>
                                <a href="delete.php?id=<?= $a['id'] ?>" onclick="return confirm('Confirma exclusão?')"
                                    class="btn btn-sm btn-outline-danger">Excluir</a>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</body>

</html>