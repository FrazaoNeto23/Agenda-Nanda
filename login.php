<?php
require 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = trim($_POST['email']);
  $senha = $_POST['senha'];

  $stmt = $pdo->prepare("SELECT * FROM users WHERE email=? LIMIT 1");
  $stmt->execute([$email]);
  $user = $stmt->fetch();

  if ($user && password_verify($senha, $user['senha'])) {
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['role'] = $user['role'];
    header("Location: index.php");
    exit;
  } else {
    $erro = "E-mail ou senha incorretos!";
  }
}
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
  <meta charset="UTF-8">
  <title>Login - Agenda Manicure</title>
  <link rel="stylesheet" href="styles.css">
</head>

<body>
  <div class="container">
    <h1>Login</h1>
    <?php if (!empty($_GET['msg'])): ?>
      <p style="color:green;"><?= htmlspecialchars($_GET['msg']) ?></p>
    <?php endif; ?>
    <?php if (!empty($erro)): ?>
      <p style="color:red;"><?= $erro ?></p>
    <?php endif; ?>
    <form method="post">
      <div class="form-row">
        <input type="email" name="email" placeholder="E-mail" required class="input">
        <input type="password" name="senha" placeholder="Senha" required class="input">
      </div>
      <button type="submit" class="btn">Entrar</button>
    </form>
    <p class="note">Ainda não possui conta? <a href="register.php">Cadastre-se aqui</a></p>
  </div>
</body>

</html>