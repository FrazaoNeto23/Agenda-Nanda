<?php
require_once __DIR__ . '/config.php';
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['tipo'] !== 'dono') {
  header('Location: login.php');
  exit;
}

// CRUD simples
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $nome = trim($_POST['nome'] ?? '');
  $valor = $_POST['valor'] ?? null;
  $descricao = trim($_POST['descricao'] ?? '');
  if (isset($_POST['id']) && $_POST['id']) {
    $stmt = $pdo->prepare('UPDATE services SET nome=?, valor=?, descricao=? WHERE id=?');
    $stmt->execute([$nome, $valor ?: null, $descricao, (int) $_POST['id']]);
  } else {
    $stmt = $pdo->prepare('INSERT INTO services (nome, valor, descricao) VALUES (?, ?, ?)');
    $stmt->execute([$nome, $valor ?: null, $descricao]);
  }
  header('Location: services.php');
  exit;
}

if (isset($_GET['delete'])) {
  $pdo->prepare('DELETE FROM services WHERE id = ?')->execute([(int) $_GET['delete']]);
  header('Location: services.php');
  exit;
}

$services = $pdo->query('SELECT * FROM services ORDER BY nome ASC')->fetchAll();
?>
<!doctype html>
<html lang="pt-BR">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Serviços</title>
  <link rel="stylesheet" href="styles.css?e=<?php echo rand(0, 10000) ?>">
</head>

<body>
  <div class="container">
    <div class="header">
      <h1>Gerenciar Serviços</h1>
      <nav><a href="painel_dona.php">Voltar</a><a href="logout.php">Sair</a></nav>
    </div>

    <div class="card">
      <h3>Criar / Editar serviço</h3>
      <form method="post">
        <input type="hidden" name="id" id="svc_id">
        <label>Nome</label><input type="text" name="nome" id="svc_nome" required>
        <label>Valor (opcional)</label><input type="text" name="valor" id="svc_valor">
        <label>Descrição</label><textarea name="descricao" id="svc_desc" rows="3"></textarea>
        <div style="margin-top:10px"><button class="btn-primary" type="submit">Salvar</button></div>
      </form>
    </div>

    <div class="card">
      <h3>Lista de serviços</h3>
      <table class="table">
        <thead>
          <tr>
            <th>Nome</th>
            <th>Valor</th>
            <th>Descrição</th>
            <th>Ações</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!$services)
            echo '<tr><td colspan="4">Nenhum serviço.</td></tr>'; ?>
          <?php foreach ($services as $s): ?>
            <tr>
              <td><?= htmlspecialchars($s['nome']) ?></td>
              <td><?= $s['valor'] ? 'R$ ' . number_format($s['valor'], 2, ',', '.') : '-' ?></td>
              <td><?= htmlspecialchars($s['descricao']) ?></td>
              <td class="actions">
                <a href="#"
                  onclick="editService(<?= $s['id'] ?>,'<?= addslashes($s['nome']) ?>','<?= $s['valor'] ?>','<?= addslashes($s['descricao']) ?>');return false;">Editar</a>
                <a href="services.php?delete=<?= $s['id'] ?>" onclick="return confirm('Excluir serviço?')">Excluir</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

  </div>
  <script>
    function editService(id, nome, valor, desc) {
      document.getElementById('svc_id').value = id;
      document.getElementById('svc_nome').value = nome;
      document.getElementById('svc_valor').value = valor;
      document.getElementById('svc_desc').value = desc;
      window.scrollTo(0, 0);
    }
  </script>
</body>

</html>