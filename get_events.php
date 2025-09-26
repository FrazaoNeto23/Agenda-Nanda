<?php
require 'config.php';
checkLogin();

$stmt = $pdo->query("SELECT * FROM appointments");
$appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

$events = [];
foreach ($appointments as $appt) {
    $events[] = [
        'id' => $appt['id'],
        'title' => $appt['client_name'] . " - " . $appt['service'],
        'start' => $appt['date'] . "T" . $appt['time'],
        'end' => $appt['end_time'] ? $appt['date'] . "T" . $appt['end_time'] : null,
        'color' => $appt['status'] === 'atendido' ? '#28a745' : '#dc3545'
    ];
}

echo json_encode($events);
