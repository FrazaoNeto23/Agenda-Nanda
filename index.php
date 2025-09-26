<?php
require 'config.php';
checkLogin();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Agenda Manicure</title>
  <link rel="stylesheet" href="css/style.css">
  <link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css' rel='stylesheet' />
  <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js'></script>
</head>
<body>
<div class="container">
  <div class="header">
    <div class="logo">💅</div>
    <h1>Agenda Manicure</h1>
  </div>

  <p><a href="logout.php">Sair</a></p>

  <?php if(isDono()): ?>
    <h3>Painel do Dono</h3>
    <div id="calendar"></div>
    <script src="scripts/calendar.js"></script>
  <?php else: ?>
    <h3>Agendar Serviço</h3>
    <form method="post" action="add.php">
      <div class="form-row">
        <input type="text" name="client_name" placeholder="Seu nome" required class="input">
        <select name="service" required class="input">
          <option value="">Selecione um serviço</option>
          <option>Manicure</option>
          <option>Pedicure</option>
          <option>Unhas de Gel</option>
          <option>Unhas Decoradas</option>
        </select>
      </div>
      <div class="form-row">
        <input type="date" name="date" required class="input">
        <input type="time" name="time" required class="input">
      </div>
      <button type="submit" class="btn">Agendar</button>
    </form>
  <?php endif; ?>
</div>
</body>
</html>
