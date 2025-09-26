<?php
require 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome']);
    $email = trim($_POST['email']);
    $senha = $_POST['senha'];
    $senha2 = $_POST['senha2'];

    if ($senha !== $senha2) {
        $erro = "As senhas não conferem!";
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email=?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $erro = "Este email já está cadastrado!";
        } else {
            $hash = password_hash($senha, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("INSERT INTO users (nome,email,senha,role) VALUES (?,?,?,'cliente')");
            $stmt->execute([$nome,$email,$hash]);
            header("Location: login.php?msg=Cadastro realizado com sucesso, faça login!");
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Cadastro - Agenda Manicure</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="container">
  <h1>Cadastro de Cliente</h1>
  <?php if (!empty($erro)): ?>
    <p style="color:red;"><?= $erro ?></p>
  <?php endif; ?>
  <form method="post">
    <div class="form-row">
      <input type="text" name="nome" placeholder="Nome completo" required class="input">
      <input type="email" name="email" placeholder="E-mail" required class="input">
    </div>
    <div class="form-row">
      <input type="password" name="senha" placeholder="Senha" required class="input">
      <input type="password" name="senha2" placeholder="Confirmar senha" required class="input">
    </div>
    <button type="submit" class="btn">Cadastrar</button>
  </form>
  <p class="note">Já possui conta? <a href="login.php">Faça login</a></p>
</div>
</body>
</html>
