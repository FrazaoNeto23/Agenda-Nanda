<?php
require 'config.php';
$errors = [];
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username=?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        header("Location: index.php");
        exit;
    } else {
        $errors[] = "Usuário ou senha incorretos.";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <title>Login</title>
    <link rel="stylesheet" href="styles.css">
</head>

<body>
    <div class="header">
        <div class="logo">💅</div>
        <h1>Login</h1>
        <p class="note">Entre para gerenciar ou ver seus agendamentos</p>
    </div>
    <form method="POST">
        <?php if ($errors): ?>
            <div style="color:red;"><?php foreach ($errors as $e)
                echo "<p>$e</p>"; ?></div><?php endif; ?>
        <input class="input" type="text" name="username" placeholder="Nome de usuário" required>
        <input class="input" type="password" name="password" placeholder="Senha" required>
        <button class="btn" type="submit">Entrar</button>
    </form>
    <a href="register.php" class="back-link">Não tem conta? Cadastre-se</a>
</body>

</html>