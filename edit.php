<?php
require 'config.php';

$id = intval($_GET['id'] ?? 0);
if (!$id) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!check_csrf($_POST['csrf_token'] ?? '')) {
        $_SESSION['flash'] = 'Token inválido.';
        header('Location:index.php');
        exit;
    }
    $client_name = trim($_POST['client_name']);
    $phone = trim($_POST['phone'] ?? '');
    $service = trim($_POST['service']);
    $date = $_POST['date'];
    $time = $_POST['time'];
    $notes = trim($_POST['notes'] ?? '');
    $stmt = $pdo->prepare("UPDATE appointments SET client_name=?, phone=?, service=?, date=?, time=?, notes=? WHERE id=?");
    $stmt->execute([$client_name, $phone, $service, $date, $time, $notes, $id]);
    $_SESSION['flash'] = 'Agendamento atualizado.';
    header('Location:index.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM appointments WHERE id=?");
$stmt->execute([$id]);
$a = $stmt->fetch();
if (!$a) {
    $_SESSION['flash'] = 'Agendamento não encontrado.';
    header('Location:index.php');
    exit;
}

?>

<!doctype html>
<html lang="pt-br">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Editar Agendamento</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
</head>

<body>
    <div class="container">
        <h2>Editar Agendamento</h2>
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <div class="form-row">
                <input class="input form-control" name="client_name" value="<?= htmlspecialchars($a['client_name']) ?>"
                    required>
                <input class="input form-control" name="phone" value="<?= htmlspecialchars($a['phone']) ?>" type="tel">
                <select name="service" class="form-control" required>
                    <option <?= $a['service'] === 'Manicure Simples' ? 'selected' : '' ?>>Manicure Simples</option>
                    <option <?= $a['service'] === 'Manicure + Esmaltação' ? 'selected' : '' ?>>Manicure + Esmaltação
                    </option>
                    <option <?= $a['service'] === 'Unhas de Gel' ? 'selected' : '' ?>>Unhas de Gel</option>
                    <option <?= $a['service'] === 'Alongamento' ? 'selected' : '' ?>>Alongamento</option>
                </select>
            </div>
            <div class="form-row">
                <input type="date" name="date" class="form-control" value="<?= htmlspecialchars($a['date']) ?>" required>
                <input type="time" name="time" class="form-control"
                    value="<?= htmlspecialchars(substr($a['time'], 0, 5)) ?>" required>
            </div>
            <div class="form-row">
                <textarea name="notes" class="form-control"><?= htmlspecialchars($a['notes']) ?></textarea>
            </div>
            <div style="margin-top:10px">
                <button class="btn">Salvar</button>
                <a href="index.php" class="btn btn-outline-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</body>

</html>