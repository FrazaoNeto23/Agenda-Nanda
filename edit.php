<?php
require 'config.php';

// Buscar dados do agendamento
if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $stmt = $pdo->prepare("SELECT * FROM appointments WHERE id = ?");
    $stmt->execute([$id]);
    $appointment = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$appointment) {
        die("Agendamento não encontrado!");
    }
}

// Atualizar agendamento
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $client = $_POST['client'];
    $date = $_POST['date'];
    $time = $_POST['time'];
    $service = $_POST['service'];

    $stmt = $pdo->prepare("UPDATE appointments SET client=?, date=?, time=?, service=? WHERE id=?");
    $stmt->execute([$client, $date, $time, $service, $id]);

    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <title>Editar Agendamento</title>
    <link rel="stylesheet" href="styles.css">
</head>

<body>
    <div class="container">
        <div class="header">
            <div class="logo">💅</div>
            <div>
                <h1>Editar Agendamento</h1>
                <p class="note">Atualize os dados do cliente</p>
            </div>
        </div>

        <form method="POST">
            <div class="form-row">
                <input class="input" type="text" name="client" value="<?= htmlspecialchars($appointment['client']) ?>"
                    required>
                <input class="input" type="date" name="date" value="<?= $appointment['date'] ?>" required>
                <input class="input" type="time" name="time" value="<?= $appointment['time'] ?>" required>

                <!-- select com serviços -->
                <select class="input" name="service" required>
                    <option value="Manicure Simples" <?= $appointment['service'] == "Manicure Simples" ? "selected" : "" ?>>Manicure Simples</option>
                    <option value="Pedicure" <?= $appointment['service'] == "Pedicure" ? "selected" : "" ?>>Pedicure
                    </option>
                    <option value="Unha em Gel" <?= $appointment['service'] == "Unha em Gel" ? "selected" : "" ?>>Unha em
                        Gel</option>
                    <option value="Unha de Fibra" <?= $appointment['service'] == "Unha de Fibra" ? "selected" : "" ?>>Unha
                        de Fibra</option>
                    <option value="Spa das Mãos" <?= $appointment['service'] == "Spa das Mãos" ? "selected" : "" ?>>Spa das
                        Mãos</option>
                    <option value="Spa dos Pés" <?= $appointment['service'] == "Spa dos Pés" ? "selected" : "" ?>>Spa dos
                        Pés</option>
                </select>

                <button class="btn" type="submit">Salvar Alterações</button>
            </div>
        </form>
        <p><a href="index.php" class="btn">⬅ Voltar</a></p>
    </div>
</body>

</html>