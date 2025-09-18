<?php
require_once __DIR__ . '/config.php';
session_start();
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = trim($_POST['email'] ?? '');
  $senha = $_POST['senha'] ?? '';
  if (!$email || !$senha)
    $errors[] = 'Preencha email e senha.';
  if (!$errors) {
    $stmt = $pdo->prepare('SELECT id, senha_hash, tipo, nome FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $u = $stmt->fetch();
    if ($u && password_verify($senha, $u['senha_hash'])) {
      $_SESSION['user_id'] = $u['id'];
      $_SESSION['tipo'] = $u['tipo'];
      $_SESSION['user_name'] = $u['nome'];
      if ($u['tipo'] === 'dono')
        header('Location: painel_dona.php');
      else
        header('Location: painel_cliente.php');
      exit;
    } else {
      $errors[] = 'Credenciais inválidas.';
    }
  }
}
?>
<!doctype html>
<html lang="pt-BR">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Login</title>
  <link rel="stylesheet" href="styles.css?e=<?php echo rand(0, 10000) ?>">
</head>

<body>
  <div class="container">
    <div class="header">
      <h1>Login</h1>
      <nav><a href="register.php">Cadastrar</a></nav>
    </div>
    <?php if ($errors): ?>
      <div class="notice"><?php foreach ($errors as $e)
        echo '<div class="small-muted">' . htmlspecialchars($e) . '</div>'; ?>
      </div>
    <?php endif; ?>
    <form method="post">
      <label>Email</label><input type="email" name="email" required>
      <label>Senha</label><input type="password" name="senha" required>
      <div style="margin-top:12px"><button class="btn-primary" type="submit">Entrar</button></div>
    </form>
  </div>
</body>

</html>