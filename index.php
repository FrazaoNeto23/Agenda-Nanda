<?php
require 'config.php';
checkLogin();
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
  <meta charset="UTF-8">
  <title>Agenda Manicure</title>
  <link rel="stylesheet" href="styles.css">
  <link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.css' rel='stylesheet' />
  <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js'></script>
  <script src="calendar.js" defer></script>
</head>

<body>
  <div class="container">
    <div class="header">
      <div class="logo">💅</div>
      <h1>Agenda Manicure</h1>
    </div>

    <p class="note">Bem-vindo, <?= $_SESSION['role'] === 'dono' ? 'Dona do salão' : 'Cliente' ?>!</p>
    <a href="logout.php" class="btn" style="margin-bottom:20px;">Sair</a>

    <?php if (isDono()): ?>
      <h3>Calendário de Agendamentos</h3>
      <div id="calendar"></div>
    <?php else: ?>
      <h3>Agende seu horário</h3>
      <p>Selecione uma data no calendário e informe o serviço.</p>
      <div id="calendar"></div>
    <?php endif; ?>
  </div>
</body>

</html>