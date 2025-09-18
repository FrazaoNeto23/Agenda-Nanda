<?php
require_once __DIR__ . '/config.php';
$pdo = connectDB();
$feedback = isset($_GET['msg']) ? htmlspecialchars($_GET['msg']) : '';
?>
<!doctype html>
<html lang="pt-BR">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Agendar Manicure</title>
  <link rel="stylesheet" href="styles.css">
</head>

<body>
  <div class="container">
    <div class="header">
      <h1>Agende seu horário - Manicure</h1>
      <a href="appointments.php">Painel / Agenda</a>
    </div>

    <?php if ($feedback): ?>
      <div class="small-muted"><?php echo $feedback; ?></div>
    <?php endif; ?>

    <form action="save_appointment.php" method="post">
      <label for="client_name">Nome</label>
      <input id="client_name" name="client_name" type="text" required>

      <label for="phone">Telefone (opcional)</label>
      <input id="phone" name="phone" type="text">

      <label for="service">Serviço</label>
      <select id="service" name="service" required>
        <option value="Manicure">Manicure</option>
        <option value="Pedicure">Pedicure</option>
        <option value="Alongamento">Alongamento</option>
        <option value="Escultura">Escultura</option>
        <option value="Outros">Outros</option>
      </select>

      <label for="date">Data</label>
      <input id="date" name="date" type="date" required>

      <label for="time">Hora</label>
      <input id="time" name="time" type="time" required>

      <label for="notes">Observações (opcional)</label>
      <textarea id="notes" name="notes" rows="3"></textarea>

      <div style="margin-top:12px">
        <button class="btn-primary" type="submit">Enviar agendamento</button>
      </div>
    </form>
  </div>
</body>

</html>