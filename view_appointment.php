<?php
require_once __DIR__ . '/config.php';
$pdo = connectDB();
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$stmt = $pdo->prepare('SELECT * FROM appointments WHERE id = ?');
$stmt->execute([$id]);
$a = $stmt->fetch();
if(!$a){
    die('Agendamento não encontrado.');
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Detalhes do Agendamento</title>
  <link rel="stylesheet" href="styles.css">
</head>
<body>
  <div class="container">
    <div class="header">
      <h1>Detalhes</h1>
      <a href="appointments.php">Voltar</a>
    </div>

    <p><strong>Nome:</strong> <?= htmlspecialchars($a['client_name']) ?></p>
    <p><strong>Telefone:</strong> <?= htmlspecialchars($a['phone']) ?></p>
    <p><strong>Serviço:</strong> <?= htmlspecialchars($a['service']) ?></p>
    <p><strong>Data:</strong> <?= htmlspecialchars($a['date']) ?></p>
    <p><strong>Hora:</strong> <?= htmlspecialchars(substr($a['time'],0,5)) ?></p>
    <p><strong>Status:</strong> <?= htmlspecialchars($a['status']) ?></p>
    <p><strong>Observações:</strong><br><?= nl2br(htmlspecialchars($a['notes'])) ?></p>
  </div>
</body>
</html>