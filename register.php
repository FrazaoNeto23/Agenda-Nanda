<?php
require 'config.php';
$errors = [];
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $role = $_POST['role'] ?? 'cliente';
    if (empty($username) || empty($password)) {
        $errors[] = "Preencha todos os campos.";
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username=?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            $errors[] = "Usuário já existe.";
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username,password,role) VALUES (?,?,?)");
            $stmt->execute([$username, $hash, $role]);
            header("Location: login.php");
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <title>Cadastro</title>
    <link rel="stylesheet" href="styles.css">
</head>

<body>
    <div class="header">
        <div class="logo">💅</div>
        <h1>Cadastro de Usuário</h1>
        <p class="note">Crie sua conta</p>
    </div>
    <form method="POST">
        <?php if ($errors): ?>
            <div style="color:red;"><?php foreach ($errors as $e)
                echo "<p>$e</p>"; ?></div><?php endif; ?>
        <input class="input" type="text" name="username" placeholder="Nome de usuário" required>
        <input class="input" type="password" name="password" placeholder="Senha" required>
        <select class="input" name="role">
            <option value="cliente">Cliente</option>
            <option value="dono">Dono/Admin</option>
        </select>
        <button class="btn" type="submit">Cadastrar</button>
    </form>
    <a href="login.php" class="back-link">Já tem conta? Faça login</a>
</body>

</html>