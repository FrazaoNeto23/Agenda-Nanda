<?php
require_once __DIR__ . '/config.php';
$pdo = connectDB();

if(isset($_GET['action']) && isset($_GET['id'])){
    $id = (int)$_GET['id'];
    if($_GET['action'] === 'delete'){
        $stmt = $pdo->prepare('DELETE FROM appointments WHERE id = ?');
        $stmt->execute([$id]);
        header('Location: appointments.php'); exit;
    }
    if($_GET['action'] === 'status' && isset($_GET['status'])){
        $status = $_GET['status'];
        $allowed = ['agendado','confirmado','cancelado','concluido'];
        if(in_array($status, $allowed)){
            $stmt = $pdo->prepare('UPDATE appointments SET status = ? WHERE id = ?');
            $stmt->execute([$status, $id]);
        }
        header('Location: appointments.php'); exit;
    }
}

$stmt = $pdo->query('SELECT * FROM appointments ORDER BY date ASC, time ASC');
$appointments = $stmt->fetchAll();
?>
<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Painel - Agenda de Manicure</title>
  <link rel="stylesheet" href="styles.css">
</head>
<body>
  <div class="container">
    <div class="header">
      <h1>Painel / Agenda</h1>
      <a href="index.php">Voltar ao formulário</a>
    </div>

    <table class="table">
      <thead>
        <tr>
          <th>Data</th>
          <th>Hora</th>
          <th>Nome</th>
          <th>Serviço</th>
          <th>Telefone</th>
          <th>Status</th>
          <th>Ações</th>
        </tr>
      </thead>
      <tbody>
        <?php if(!$appointments): ?>
          <tr><td colspan="7">Nenhum agendamento encontrado.</td></tr>
        <?php endif; ?>

        <?php foreach($appointments as $a): ?>
          <tr>
            <td><?= htmlspecialchars($a['date']) ?></td>
            <td><?= htmlspecialchars(substr($a['time'],0,5)) ?></td>
            <td><?= htmlspecialchars($a['client_name']) ?></td>
            <td><?= htmlspecialchars($a['service']) ?></td>
            <td><?= htmlspecialchars($a['phone']) ?></td>
            <td><span class="status-<?= htmlspecialchars($a['status']) ?>"><?= htmlspecialchars($a['status']) ?></span></td>
            <td class="actions">
              <a href="view_appointment.php?id=<?= $a['id'] ?>">Ver</a>
              <a href="appointments.php?action=delete&id=<?= $a['id'] ?>" onclick="return confirm('Excluir agendamento?')">Excluir</a>
              <select onchange="if(confirm('Alterar status?')) window.location='appointments.php?action=status&id=<?= $a['id'] ?>&status='+this.value">
                <option value="">-- Mudar status --</option>
                <option value="agendado">agendado</option>
                <option value="confirmado">confirmado</option>
                <option value="concluido">concluido</option>
                <option value="cancelado">cancelado</option>
              </select>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</body>
</html>