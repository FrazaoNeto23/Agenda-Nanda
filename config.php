<?php
return [
    'host' => '127.0.0.1',
    'dbname' => 'manicure_agenda',
    'user' => 'root',
    'pass' => '',
    'charset' => 'utf8mb4',
];

function connectDB()
{
    $c = include __DIR__ . '/config.php';
    $dsn = "mysql:host={$c['host']};dbname={$c['dbname']};charset={$c['charset']}";
    try {
        $pdo = new PDO($dsn, $c['user'], $c['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        return $pdo;
    } catch (PDOException $e) {
        die('Falha na conexão com o banco: ' . $e->getMessage());
    }
}
?>