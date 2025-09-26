<?php
require 'config.php';
checkLogin();
$user_role = $_SESSION['role'] ?? '';
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
  <meta charset="UTF-8">
  <title>Painel Agenda</title>
  <link href="styles.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/main.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/main.min.js"></script>
  <script src="calendar.js"></script>
</head>

<body>
  <div class="container">
    <div class="header">
      <div class="logo">💅</div>
      <h1>Agenda Manicure</h1>
      <div style="margin-left:auto;">
        <a href="logout.php" class="btn">Sair</a>
      </div>
    </div>

    <?php if ($user_role === 'dono'): ?>
      <h2>Painel do Dono</h2>
      <p>Veja todos os agendamentos e altere status.</p>
    <?php elseif ($user_role === 'cliente'): ?>
      <h2>Painel do Cliente</h2>
      <p>Escolha serviço, data e hora.</p>
    <?php endif; ?>

    <div id="calendar"></div>
  </div>
</body>

</html>