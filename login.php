<?php
require 'config.php';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = $_POST['email'] ?? '';
  $password = $_POST['password'] ?? '';

  if (!$email || !$password) {
    $error = "Preencha todos os campos";
  } else {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email=?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    if ($user && password_verify($password, $user['password'])) {
      $_SESSION['user_id'] = $user['id'];
      $_SESSION['role'] = $user['role'];
      header("Location: index.php");
      exit;
    } else {
      $error = "Email ou senha incorretos";
    }
  }
}
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
  <meta charset="UTF-8">
  <title>Login</title>
  <link href="styles.css" rel="stylesheet">
</head>

<body>
  <div class="auth-container">
    <div class="auth-card">
      <h2>Login</h2>
      <?php if ($error)
        echo "<p class='error-msg'>$error</p>"; ?>
      <form method="POST">
        <input type="email" name="email" placeholder="Email" class="input" required>
        <input type="password" name="password" placeholder="Senha" class="input" required>
        <button type="submit" class="btn">Entrar</button>
      </form>
      <p>Não tem conta? <a href="register.php">Cadastrar</a></p>
    </div>
  </div>
</body>

</html>