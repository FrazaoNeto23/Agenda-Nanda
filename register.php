<?php
require 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $name = $_POST['name'];
  $email = $_POST['email'];
  $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
  $role = $_POST['role']; // cliente ou dono

  $stmt = $pdo->prepare("INSERT INTO users (name,email,password,role) VALUES (?,?,?,?)");
  $stmt->execute([$name, $email, $password, $role]);
  header("Location: login.php");
}
?>

<form method="POST">
  <input type="text" name="name" class="input" placeholder="Nome" required>
  <input type="email" name="email" class="input" placeholder="Email" required>
  <input type="password" name="password" class="input" placeholder="Senha" required>
  <select name="role" class="input" required>
    <option value="cliente">Cliente</option>
    <option value="dono">Dono</option>
  </select>
  <button type="submit" class="btn">Cadastrar</button>
</form>