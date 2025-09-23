<?php
require 'config.php';

// Buscar agendamentos
$stmt = $pdo->query("SELECT * FROM appointments ORDER BY date, time");
$appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <title>Agenda Manicure</title>
    <link rel="stylesheet" href="css/style.css">
</head>

<body>
    <!-- Header -->
    <div class="header">
        <div class="logo">💅</div>
        <h1>Agenda Digital</h1>
        <p class="note">Controle fácil dos seus horários</p>
    </div>

    <!-- Conteúdo -->
    <div class="container">
        <?php if (count($appointments) > 0): ?>
            <?php foreach ($appointments as $a): ?>
                <div class="card">
                    <h3><?= htmlspecialchars($a['client']) ?></h3>
                    <p><strong>Data:</strong> <?= date("d/m/Y", strtotime($a['date'])) ?></p>
                    <p><strong>Hora:</strong> <?= date("H:i", strtotime($a['time'])) ?></p>
                    <p><strong>Serviço:</strong> <?= htmlspecialchars($a['service']) ?></p>

                    <div class="actions">
                        <a href="edit.php?id=<?= $a['id'] ?>" class="edit">✏ Editar</a>
                        <a href="delete.php?id=<?= $a['id'] ?>" class="delete"
                            onclick="return confirm('Tem certeza que deseja excluir este agendamento?')">🗑 Excluir</a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p style="text-align:center; color:#777;">Nenhum agendamento encontrado.</p>
        <?php endif; ?>
    </div>

    <!-- Botão adicionar flutuante -->
    <a href="add.php" class="add-btn">+ Novo Agendamento</a>
</body>

</html>