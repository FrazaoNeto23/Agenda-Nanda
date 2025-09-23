<?php
require 'config.php';
checkLogin();

// Verificar role
$dono = isDono();

// Buscar agendamentos
if ($dono) {
    $stmt = $pdo->query("SELECT a.*, u.username FROM appointments a JOIN users u ON a.client_id = u.id ORDER BY date, time");
} else {
    $stmt = $pdo->prepare("SELECT * FROM appointments WHERE client_id = ? ORDER BY date, time");
    $stmt->execute([$_SESSION['user_id']]);
}
$appointments = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <title>Agenda Manicure</title>
    <link rel="stylesheet" href="styles.css">
</head>

<body>
    <!-- Header -->
    <div class="header">
        <div class="logo">💅</div>
        <h1>Bem-vindo, <?= htmlspecialchars($_SESSION['username']) ?></h1>
        <p class="note"><?= $dono ? "Painel do Dono" : "Seus agendamentos" ?></p>
        <a href="logout.php" class="back-link" style="color:white;">Sair</a>
    </div>

    <!-- Conteúdo -->
    <div class="container">
        <?php if (count($appointments) > 0): ?>
            <?php foreach ($appointments as $a): ?>
                <div class="card">
                    <h3><?= $dono ? htmlspecialchars($a['username']) : htmlspecialchars($a['client_name']) ?></h3>
                    <p><strong>Data:</strong> <?= date("d/m/Y", strtotime($a['date'])) ?></p>
                    <p><strong>Hora:</strong> <?= date("H:i", strtotime($a['time'])) ?></p>
                    <p><strong>Serviço:</strong> <?= htmlspecialchars($a['service']) ?></p>
                    <p><strong>Status:</strong> <?= ucfirst($a['status']) ?></p>

                    <div class="actions">
                        <?php if ($dono): ?>
                            <!-- Dono pode editar status -->
                            <?php if ($a['status'] == 'agendado'): ?>
                                <a href="edit_status.php?id=<?= $a['id'] ?>&status=atendido" class="edit">✅ Marcar Atendido</a>
                            <?php else: ?>
                                <a href="edit_status.php?id=<?= $a['id'] ?>&status=agendado" class="edit">🔄 Marcar Agendado</a>
                            <?php endif; ?>
                            <a href="edit.php?id=<?= $a['id'] ?>" class="edit">✏ Editar</a>
                            <a href="delete.php?id=<?= $a['id'] ?>" class="delete"
                                onclick="return confirm('Tem certeza que deseja excluir?')">🗑 Excluir</a>
                        <?php else: ?>
                            <a href="edit.php?id=<?= $a['id'] ?>" class="edit">✏ Editar</a>
                            <a href="delete.php?id=<?= $a['id'] ?>" class="delete"
                                onclick="return confirm('Tem certeza que deseja excluir?')">🗑 Excluir</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p style="text-align:center; color:#777;">Nenhum agendamento encontrado.</p>
        <?php endif; ?>
    </div>

    <!-- Botão adicionar -->
    <a href="add.php" class="add-btn">+ Novo Agendamento</a>
</body>

</html>