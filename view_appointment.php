<?php
require_once __DIR__ . '/config.php';
session_start();
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$stmt = $pdo->prepare('SELECT a.*, s.nome AS service_name, u.nome AS user_nome, u.email AS user_email FROM appointments a LEFT JOIN services s ON a.service_id = s.id LEFT JOIN users u ON a.user_id = u.id WHERE a.id = ?');
$stmt->execute([$id]);
$a = $stmt->fetch();
if (!$a) {
  die('Agendamento não encontrado.');
}
?>
<!doctype html>
<html lang="pt-BR">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Detalhes</title>
  <link rel="stylesheet" href="styles.css?e=<?php echo rand(0, 10000) ?>">
</head>

<body>
  <div class="container">
    <div class="header">
      <h1>Detalhes do Agendamento</h1>
      <nav><a href="javascript:history.back()">Voltar</a></nav>
    </div>

    <div class="card">
      <p><strong>Cliente:</strong> <?= htmlspecialchars($a['user_nome'] ?? $a['client_name']) ?>
        (<?= htmlspecialchars($a['user_email'] ?? '') ?>)</p>
      <p><strong>Serviço:</strong> <?= htmlspecialchars($a['service_name'] ?? '—') ?></p>
      <p><strong>Data:</strong> <?= htmlspecialchars($a['date']) ?></p>
      <p><strong>Hora:</strong> <?= htmlspecialchars(substr($a['time'], 0, 5)) ?></p>
      <p><strong>Status:</strong> <?= htmlspecialchars($a['status']) ?></p>
      <p><strong>Observações:</strong><br><?= nl2br(htmlspecialchars($a['notes'])) ?></p>
    </div>

  </div>
</body>

</html>