<?php
require 'config.php';
checkLogin();
$user_role = $_SESSION['role'] ?? '';
$user_name = $_SESSION['name'] ?? '';
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Agenda Manicure</title>
  <link href="styles.css" rel="stylesheet">

  <!-- FullCalendar CSS -->
  <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.css" rel="stylesheet">
</head>

<body>
  <div class="container">
    <div class="header">
      <div class="logo">💅</div>
      <div>
        <h1>Agenda Manicure</h1>
        <p class="subtitle">Bem-vinda, <?php echo htmlspecialchars($user_name); ?>!</p>
      </div>
      <div style="margin-left: auto;">
        <a href="logout.php" class="btn btn-secondary">Sair</a>
      </div>
    </div>

    <div class="info-box">
      <?php if ($user_role === 'dono'): ?>
        <h2>👑 Painel do Dono</h2>
        <p>Visualize todos os agendamentos. Clique em um agendamento para marcá-lo como concluído.</p>
      <?php else: ?>
        <h2>✨ Agende seu Horário</h2>
        <p>Clique em um dia no calendário para agendar seu serviço.</p>
      <?php endif; ?>
    </div>

    <div class="calendar-wrapper">
      <div id="calendar"></div>
    </div>

    <!-- Modal de Agendamento -->
    <div id="modal-agenda" class="modal" style="display: none;">
      <div class="modal-content">
        <span class="close">&times;</span>
        <h2>📅 Novo Agendamento</h2>
        <form id="form-agenda">
          <div class="form-group">
            <label for="agenda-service">Serviço *</label>
            <select id="agenda-service" class="input" required>
              <option value="">Carregando...</option>
            </select>
          </div>

          <div class="form-group">
            <label for="agenda-date">Data *</label>
            <input type="date" id="agenda-date" class="input" required>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label for="agenda-time">Horário Início *</label>
              <input type="time" id="agenda-time" class="input" required>
            </div>

            <div class="form-group">
              <label for="agenda-end-time">Horário Fim *</label>
              <input type="time" id="agenda-end-time" class="input" required>
            </div>
          </div>

          <button type="submit" class="btn btn-primary">Confirmar Agendamento</button>
        </form>
      </div>
    </div>

  </div>

  <!-- FullCalendar JS - IMPORTANTE: Carregar ANTES do calendar.js -->
  <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>
  <script src="calendar.js"></script>
</body>

</html>