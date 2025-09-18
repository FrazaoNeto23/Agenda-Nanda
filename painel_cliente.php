<?php
session_start();
require 'config.php';

// Verifica se o usuário está logado e é cliente
if (!isset($_SESSION['user_id']) || $_SESSION['tipo'] !== 'cliente') {
  header("Location: login.php");
  exit;
}

// Pega informações do cliente
$user_id = $_SESSION['user_id'];

// Tenta buscar os serviços
$services = [];
try {
  $stmt = $pdo->prepare("SELECT * FROM services ORDER BY nome");
  $stmt->execute();
  $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
  // Se a tabela não existir, apenas mostra um aviso
  $services = [];
  $services_error = "⚠ Nenhum serviço encontrado. A tabela 'services' não existe ou está vazia.";
}

// Busca agendamentos do cliente
$stmt = $pdo->prepare("
    SELECT a.id, a.data, a.horario, a.status, s.nome AS servico_nome
    FROM appointments a
    LEFT JOIN services s ON a.servico_id = s.id
    WHERE a.user_id = ?
    ORDER BY a.data, a.horario
");
$stmt->execute([$user_id]);
$appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <meta charset="UTF-8">
  <title>Painel do Cliente</title>
  <link rel="stylesheet" href="styles.css?e=<?php echo rand(0, 10000) ?>">
</head>

<body>
  <h1>Bem-vindo ao seu painel</h1>
  <p><a href="logout.php">Sair</a></p>

  <?php if (!empty($services_error)): ?>
    <p style="color: red;"><?= htmlspecialchars($services_error) ?></p>
  <?php endif; ?>

  <h2>Agendamentos</h2>
  <?php if (count($appointments) > 0): ?>
    <table border="1" cellpadding="8">
      <tr>
        <th>Data</th>
        <th>Horário</th>
        <th>Serviço</th>
        <th>Status</th>
        <th>Ações</th>
      </tr>
      <?php foreach ($appointments as $a): ?>
        <tr>
          <td><?= htmlspecialchars($a['data']) ?></td>
          <td><?= htmlspecialchars($a['horario']) ?></td>
          <td><?= htmlspecialchars($a['servico_nome'] ?? 'Indefinido') ?></td>
          <td><?= htmlspecialchars($a['status']) ?></td>
          <td>
            <?php if ($a['status'] === 'pendente'): ?>
              <a href="cancel.php?id=<?= $a['id'] ?>" onclick="return confirm('Cancelar este agendamento?')">Cancelar</a>
            <?php else: ?>
              -
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
    </table>
  <?php else: ?>
    <p>Você ainda não possui agendamentos.</p>
  <?php endif; ?>

  <p><a href="index.php">➕ Novo Agendamento</a></p>
</body>

</html>