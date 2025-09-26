<?php
require 'config.php';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $name = $_POST['name'] ?? '';
  $email = $_POST['email'] ?? '';
  $password = $_POST['password'] ?? '';
  $role = $_POST['role'] ?? '';

  if (!$name || !$email || !$password || !$role) {
    $error = "Preencha todos os campos";
  } else {
    $stmt = $pdo->prepare("INSERT INTO users (name,email,password,role) VALUES (?,?,?,?)");
    try {
      $stmt->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT), $role]);
      header("Location: login.php");
      exit;
    } catch (PDOException $e) {
      $error = "Email já cadastrado!";
    }
  }
}
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
  <meta charset="UTF-8">
  <title>Cadastro</title>
  <link href="styles.css" rel="stylesheet">
</head>

<body>
  <div class="auth-container">
    <div class="auth-card">
      <h2>Cadastro</h2>
      <?php if ($error)
        echo "<p class='error-msg'>$error</p>"; ?>
      <form method="POST">
        <input type="text" name="name" placeholder="Nome" class="input" required>
        <input type="email" name="email" placeholder="Email" class="input" required>
        <input type="password" name="password" placeholder="Senha" class="input" required>
        <select name="role" class="input" required>
          <option value="">Selecione o perfil</option>
          <option value="cliente">Cliente</option>
          <option value="dono">Dono</option>
        </select>
        <button type="submit" class="btn">Cadastrar</button>
      </form>
      <p>Já tem conta? <a href="login.php">Entrar</a></p>
    </div>
  </div>
</body>

</html>