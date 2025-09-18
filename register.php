<?php
require_once __DIR__ . '/config.php';
session_start();
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $nome = trim($_POST['nome'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $senha = $_POST['senha'] ?? '';
  $senha2 = $_POST['senha2'] ?? '';
  if (!$nome || !$email || !$senha)
    $errors[] = 'Preencha todos os campos.';
  if ($senha !== $senha2)
    $errors[] = 'As senhas não conferem.';
  if (!$errors) {
    // checar email
    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
      $errors[] = 'Email já cadastrado.';
    } else {
      $hash = password_hash($senha, PASSWORD_DEFAULT);
      $stmt = $pdo->prepare('INSERT INTO users (nome, email, senha_hash) VALUES (?, ?, ?)');
      $stmt->execute([$nome, $email, $hash]);
      $_SESSION['user_id'] = $pdo->lastInsertId();
      $_SESSION['tipo'] = 'cliente';
      header('Location: painel_cliente.php');
      exit;
    }
  }
}
?>
<!doctype html>
<html lang="pt-BR">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Registrar</title>
  <link rel="stylesheet" href="styles.css?e=<?php echo rand(0, 10000) ?>">
</head>

<body>
  <div class="container">
    <div class="header">
      <h1>Cadastro</h1>
      <nav><a href="login.php">Entrar</a></nav>
    </div>
    <?php if ($errors): ?>
      <div class="notice">
        <?php foreach ($errors as $e)
          echo '<div class="small-muted">' . htmlspecialchars($e) . '</div>'; ?>
      </div>
    <?php endif; ?>
    <form method="post">
      <label>Nome</label><input type="text" name="nome" required>
      <label>Email</label><input type="email" name="email" required>
      <div class="form-row">
        <div><label>Senha</label><input type="password" name="senha" required></div>
        <div><label>Repita a senha</label><input type="password" name="senha2" required></div>
      </div>
      <div style="margin-top:12px"><button class="btn-primary" type="submit">Registrar</button></div>
    </form>
  </div>
</body>

</html>