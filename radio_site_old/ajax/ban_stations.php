<?php
require_once __DIR__ . '/../classes/RadioStationManager.php';

header('Content-Type: application/json');

if ($_POST['action'] === 'ban_stations') {
    // Исправленная обработка массива
    $stationUUIDs = isset($_POST['station_uuids']) ? (array)$_POST['station_uuids'] : [];
    $reason_id = $_POST['reason_id'] ?? 7;
    
    // Логирование для отладки
    error_log("Received UUIDs: " . print_r($stationUUIDs, true));
    error_log("Reason: " . $reason_id);
    
    $manager = new RadioStationManager();
    $bannedCount = $manager->banStations($stationUUIDs, $reason_id);
    
    echo json_encode([
        'success' => true,
        'banned_count' => $bannedCount
    ]);
    exit;
}

echo json_encode(['success' => false, 'error' => 'Invalid action']);
?>