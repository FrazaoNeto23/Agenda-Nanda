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
            <button class="btn" id="themeToggle" style="margin-left:auto;" onclick="toggleDarkMode()">🌙 Alternar
                Tema</button>
        </div>

        <form method="POST">
            <div class="form-row">
                <input class="input" type="text" name="client" value="<?= htmlspecialchars($appointment['client']) ?>"
                    required>
                <input class="input" type="date" name="date" value="<?= $appointment['date'] ?>" required>
                <input class="input" type="time" name="time" value="<?= $appointment['time'] ?>" required>
                <input class="input" type="text" name="service" value="<?= htmlspecialchars($appointment['service']) ?>"
                    required>
                <button class="btn" type="submit">Salvar Alterações</button>
            </div>
        </form>
        <p><a href="index.php" class="btn">⬅ Voltar</a></p>
    </div>

    <script>
        function toggleDarkMode() {
            const body = document.body;
            const button = document.getElementById("themeToggle");

            body.classList.toggle("dark-mode");

            if (body.classList.contains("dark-mode")) {
                localStorage.setItem("theme", "dark");
                button.textContent = "☀️ Alternar Tema";
            } else {
                localStorage.setItem("theme", "light");
                button.textContent = "🌙 Alternar Tema";
            }
        }

        window.onload = function () {
            const button = document.getElementById("themeToggle");
            if (localStorage.getItem("theme") === "dark") {
                document.body.classList.add("dark-mode");
                button.textContent = "☀️ Alternar Tema";
            }
        }
    </script>
</body>

</html>