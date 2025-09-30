<?php
require 'config.php';
checkLogin();
$user_role = $_SESSION['role'] ?? '';
$user_name = $_SESSION['name'] ?? 'Usuário';

// Buscar serviços para o select
$stmt = $pdo->query("SELECT * FROM services ORDER BY name");
$services = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Painel - Agenda Manicure</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
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
      <h2>👑 Painel do Dono</h2>
      <p>Gerencie todos os agendamentos e altere o status conforme necessário.</p>
    <?php elseif ($user_role === 'cliente'): ?>
      <h2>👤 Painel do Cliente</h2>
      <p>Escolha o serviço, data e hora para fazer seu agendamento.</p>
    <?php endif; ?>

    <div id="calendar"></div>
  </div>

  <!-- Modal de Agendamento -->
  <div id="modal-agenda" class="modal">
    <div class="modal-content">
      <span class="close">&times;</span>
      <h2>📅 Novo Agendamento</h2>
      <form id="form-agenda">
        <select id="agenda-service" class="input" required>
          <option value="">Selecione o serviço</option>
        </select>
        <input type="date" id="agenda-date" class="input" required>
        <input type="time" id="agenda-time" class="input" placeholder="Hora início" required>
        <input type="time" id="agenda-end-time" class="input" placeholder="Hora fim (opcional)">
        <button type="submit" class="btn">Confirmar Agendamento</button>
      </form>
    </div>
  </div>
</body>

</html>