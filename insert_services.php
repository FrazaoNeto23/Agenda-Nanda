<?php
require 'config.php';

// Verifica se já existem serviços
$stmt = $pdo->query("SELECT COUNT(*) FROM services");
$count = $stmt->fetchColumn();

if ($count == 0) {
    $services = [
        ['Manicure Simples', 35.00],
        ['Pedicure Simples', 40.00],
        ['Manicure + Pedicure', 70.00],
        ['Unhas em Gel', 80.00],
        ['Esmaltação em Gel', 50.00],
        ['Blindagem de Unhas', 60.00],
        ['Spa dos Pés', 90.00],
        ['Design de Unhas', 100.00]
    ];

    $stmt = $pdo->prepare("INSERT INTO services (name, price) VALUES (?, ?)");

    foreach ($services as $service) {
        $stmt->execute($service);
    }

    echo "✅ Serviços inseridos com sucesso!<br>";
    echo "<a href='index.php'>Ir para o painel</a>";
} else {
    echo "ℹ️ Já existem {$count} serviços cadastrados.<br>";
    echo "<a href='index.php'>Ir para o painel</a>";
}