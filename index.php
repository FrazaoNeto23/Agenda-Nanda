<?php
require 'config.php';

// Buscar agendamentos
$stmt = $pdo->query("SELECT * FROM appointments ORDER BY date ASC, time ASC");
$appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <title>Agenda Digital - Manicure</title>
    <link rel="stylesheet" href="css/style.css">
</head>

<body>
    <div class="container">
        <div class="header">
            <div class="logo">💅</div>
            <div>
                <h1>Agenda Digital</h1>
                <p class="note">Gerencie seus horários de manicure</p>
            </div>
        </div>

        <h3>Novo Agendamento</h3>
        <form action="add.php" method="POST">
            <div class="form-row">
                <input class="input" type="text" name="client" placeholder="Cliente" required>
                <input class="input" type="date" name="date" required>
                <input class="input" type="time" name="time" required>

                <!-- serviços pré-definidos -->
                <select class="input" name="service" required>
                    <option value="">Selecione o serviço</option>
                    <option value="Manicure Simples">Manicure Simples</option>
                    <option value="Pedicure">Pedicure</option>
                    <option value="Unha em Gel">Unha em Gel</option>
                    <option value="Unha de Fibra">Unha de Fibra</option>
                    <option value="Spa das Mãos">Spa das Mãos</option>
                    <option value="Spa dos Pés">Spa dos Pés</option>
                </select>

                <button class="btn" type="submit">Adicionar</button>
            </div>
        </form>

        <h3>Agendamentos</h3>
        <table class="table">
            <tr>
                <th>Cliente</th>
                <th>Data</th>
                <th>Hora</th>
                <th>Serviço</th>
                <th>Ações</th>
            </tr>
            <?php foreach ($appointments as $row): ?>
                <tr>
                    <td><?= htmlspecialchars($row['client']) ?></td>
                    <td><?= date("d/m/Y", strtotime($row['date'])) ?></td>
                    <td><?= htmlspecialchars($row['time']) ?></td>
                    <td><?= htmlspecialchars($row['service']) ?></td>
                    <td class="actions">
                        <a href="edit.php?id=<?= $row['id'] ?>">Editar</a>
                        <a href="delete.php?id=<?= $row['id'] ?>"
                            onclick="return confirm('Excluir este agendamento?')">Excluir</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
</body>

</html>