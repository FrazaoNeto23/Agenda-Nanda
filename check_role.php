<?php
require 'config.php';
checkLogin();

header('Content-Type: application/json');

echo json_encode([
    'role' => $_SESSION['role'] ?? 'cliente',
    'user_id' => $_SESSION['user_id'] ?? null,
    'name' => $_SESSION['name'] ?? null
]);