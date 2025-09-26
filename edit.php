<?php
require 'config.php';
checkLogin();

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$id = $_GET['id'];

// Buscar agendamento
$stmt = $pdo->prepare("SELECT * FROM appointments WHERE id=?");
$stmt->execute([$id]);
$appointment = $stmt->fetch();

if (!$appointment) {
    die("Agendamento não encontrado.");
}

// Se não for dono e não for o cliente dono do agendamento, bloqueia
if (!isDono() && $appointment['client_id'] != $_SESSION['user_id']) {
    die("Acesso negado.");
}

// Atualizar
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $client_name = $_POST['client_name'];
    $date = $_POST['date'];
    $time = $_POST['time'];
    $service = $_POST['service'];

    $stmt = $pdo->prepare("UPDATE appointments SET client_name=?, date=?, time=?, service=? WHERE id=?");
    $stmt->execute([$client_name, $date, $time, $service, $id]);

    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <title>Editar Agendamento</title>
    <link rel="stylesheet" href="css/style.css">
</head>

<body>
    <div class="header">
        <div class="logo">💅</div>
        <h1>Editar Agendamento</h1>
        <p class="note">Atualize os dados do cliente</p>
    </div>

    <form method="POST">
        <input class="input" type="text" name="client_name" value="<?= htmlspecialchars($appointment['client_name']) ?>"
            required>
        <input class="input" type="date" name="date" value="<?= $appointment['date'] ?>" required>
        <input class="input" type="time" name="time" value="<?= $appointment['time'] ?>" required>

        <select class="input" name="service" required>
            <option value="Manicure Simples" <?= $appointment['service'] == "Manicure Simples" ? "selected" : "" ?>>Manicure
                Simples</option>
            <option value="Pedicure" <?= $appointment['service'] == "Pedicure" ? "selected" : "" ?>>Pedicure</option>
            <option value="Unha em Gel" <?= $appointment['service'] == "Unha em Gel" ? "selected" : "" ?>>Unha em Gel</option>
            <option value="Unha de Fibra" <?= $appointment['service'] == "Unha de Fibra" ? "selected" : "" ?>>Unha de Fibra
            </option>
            <option value="Spa das Mãos" <?= $appointment['service'] == "Spa das Mãos" ? "selected" : "" ?>>Spa das Mãos
            </option>
            <option value="Spa dos Pés" <?= $appointment['service'] == "Spa dos Pés" ? "selected" : "" ?>>Spa dos Pés</option>
        </select>

        <button class="btn" type="submit">Salvar Alterações</button>
    </form>

    <a href="index.php" class="back-link">⬅ Voltar para Agenda</a>
</body>

</html>