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
      $_SESSION['name'] = $user['name'];
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
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login - Agenda Manicure</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
  <link href="styles.css" rel="stylesheet">
</head>

<body>
  <div class="auth-container">
    <div class="auth-card">
      <div style="display: flex; justify-content: center; margin-bottom: 20px;">
        <div class="logo">💅</div>
      </div>
      <h2>Login</h2>
      <p style="margin-bottom: 30px;">Acesse sua agenda</p>

      <?php if ($error)
        echo "<p class='error-msg'>$error</p>"; ?>

      <form method="POST">
        <input type="email" name="email" placeholder="Seu email" class="input" required>
        <input type="password" name="password" placeholder="Sua senha" class="input" required>
        <button type="submit" class="btn">Entrar</button>
      </form>

      <p>Não tem conta? <a href="register.php">Cadastre-se aqui</a></p>
    </div>
  </div>
</body>

</html>