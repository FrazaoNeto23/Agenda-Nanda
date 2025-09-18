<?php
require_once __DIR__ . '/config.php';
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['tipo'] !== 'dono') {
  header('Location: login.php');
  exit;
}
$msg = $_GET['msg'] ?? '';

// actions: change status, delete
if (isset($_GET['action']) && isset($_GET['id'])) {
  $id = (int) $_GET['id'];
  if ($_GET['action'] === 'delete') {
    $pdo->prepare('DELETE FROM appointments WHERE id = ?')->execute([$id]);
    header('Location: painel_dona.php');
    exit;
  }
  if ($_GET['action'] === 'status' && isset($_GET['status'])) {
    $status = $_GET['status'];
    $allowed = ['agendado', 'confirmado', 'cancelado', 'concluido'];
    if (in_array($status, $allowed)) {
      $pdo->prepare('UPDATE appointments SET status = ? WHERE id = ?')->execute([$status, $id]);
    }
    header('Location: painel_dona.php');
    exit;
  }
}

$appts = $pdo->query('SELECT a.*, s.nome AS service_name, u.nome AS client_nome FROM appointments a LEFT JOIN services s ON a.service_id = s.id LEFT JOIN users u ON a.user_id = u.id ORDER BY a.date ASC, a.time ASC')->fetchAll();
$users = $pdo->query('SELECT id, nome, email, tipo, criado_em FROM users ORDER BY criado_em DESC')->fetchAll();
?>
<!doctype html>
<html lang="pt-BR">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Painel Dona</title>
  <link rel="stylesheet" href="styles.css?e=<?php echo rand(0, 10000) ?>">
</head>

<body>
  <div class="container">
    <div class="header">
      <h1>Painel da Dona</h1>
      <nav><a href="services.php">Gerenciar Serviços</a><a href="logout.php">Sair</a></nav>
    </div>
    <?php if ($msg): ?>
      <div class="notice"><?= htmlspecialchars($msg) ?></div><?php endif; ?>

    <div class="card">
      <h3>Agendamentos</h3>
      <table class="table">
        <thead>
          <tr>
            <th>Data</th>
            <th>Hora</th>
            <th>Cliente</th>
            <th>Serviço</th>
            <th>Status</th>
            <th>Ações</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!$appts)
            echo '<tr><td colspan="6">Nenhum agendamento.</td></tr>'; ?>
          <?php foreach ($appts as $a): ?>
            <tr>
              <td><?= htmlspecialchars($a['date']) ?></td>
              <td><?= htmlspecialchars(substr($a['time'], 0, 5)) ?></td>
              <td><?= htmlspecialchars($a['client_nome'] ?? $a['client_name']) ?></td>
              <td><?= htmlspecialchars($a['service_name'] ?? '—') ?></td>
              <td><span class="status-<?= htmlspecialchars($a['status']) ?>"><?= htmlspecialchars($a['status']) ?></span>
              </td>
              <td class="actions">
                <a href="view_appointment.php?id=<?= $a['id'] ?>">Ver</a>
                <a href="painel_dona.php?action=delete&id=<?= $a['id'] ?>"
                  onclick="return confirm('Excluir?')">Excluir</a>
                <select
                  onchange="if(confirm('Alterar status?')) window.location='painel_dona.php?action=status&id=<?= $a['id'] ?>&status='+this.value">
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

    <div class="card">
      <h3>Clientes cadastrados</h3>
      <table class="table">
        <thead>
          <tr>
            <th>Nome</th>
            <th>Email</th>
            <th>Tipo</th>
            <th>Desde</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!$users)
            echo '<tr><td colspan="4">Nenhum usuário.</td></tr>'; ?>
          <?php foreach ($users as $u): ?>
            <tr>
              <td><?= htmlspecialchars($u['nome']) ?></td>
              <td><?= htmlspecialchars($u['email']) ?></td>
              <td><?= htmlspecialchars($u['tipo']) ?></td>
              <td><?= htmlspecialchars($u['criado_em']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

  </div>
</body>

</html>