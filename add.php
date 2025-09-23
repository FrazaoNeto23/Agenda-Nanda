<?php
require 'config.php';

// Inserir agendamento
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $client = $_POST['client'];
    $date = $_POST['date'];
    $time = $_POST['time'];
    $service = $_POST['service'];

    $stmt = $pdo->prepare("INSERT INTO appointments (client, date, time, service) VALUES (?, ?, ?, ?)");
    $stmt->execute([$client, $date, $time, $service]);

    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <title>Novo Agendamento</title>
    <link rel="stylesheet" href="css/style.css">
</head>

<body>
    <!-- Header -->
    <div class="header">
        <div class="logo">💅</div>
        <h1>Novo Agendamento</h1>
        <p class="note">Preencha os dados do cliente</p>
    </div>

    <!-- Formulário -->
    <form method="POST">
        <input class="input" type="text" name="client" placeholder="Nome do cliente" required>
        <input class="input" type="date" name="date" required>
        <input class="input" type="time" name="time" required>

        <select class="input" name="service" required>
            <option value="" disabled selected>Selecione um serviço</option>
            <option value="Manicure Simples">Manicure Simples</option>
            <option value="Pedicure">Pedicure</option>
            <option value="Unha em Gel">Unha em Gel</option>
            <option value="Unha de Fibra">Unha de Fibra</option>
            <option value="Spa das Mãos">Spa das Mãos</option>
            <option value="Spa dos Pés">Spa dos Pés</option>
        </select>

        <button class="btn" type="submit">Salvar</button>
    </form>

    <!-- Link voltar -->
    <a href="index.php" class="back-link">⬅ Voltar para Agenda</a>
</body>

</html>