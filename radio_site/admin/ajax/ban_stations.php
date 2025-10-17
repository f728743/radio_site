<?php
require_once __DIR__ . '/../../classes/DuplicateStationManager.php';

header('Content-Type: application/json');

// Включаем подробное логирование
error_log("=== BAN STATIONS REQUEST ===");
error_log("POST data: " . print_r($_POST, true));
error_log("Headers: " . print_r(getallheaders(), true));

if ($_POST['action'] === 'ban_stations') {
    $stationUUIDs = isset($_POST['station_uuids']) ? (array)$_POST['station_uuids'] : [];
    $reason_id = $_POST['reason_id'] ?? 7;
    
    error_log("Received UUIDs: " . print_r($stationUUIDs, true));
    error_log("Reason: " . $reason_id);
    
    try {
        $manager = new DuplicateStationManager();
        $bannedCount = $manager->banStations($stationUUIDs, $reason_id);
        
        error_log("Ban successful, count: " . $bannedCount);
        
        echo json_encode([
            'success' => true,
            'banned_count' => $bannedCount
        ]);
        
    } catch(Exception $e) {
        error_log("Exception in ban_stations: " . $e->getMessage());
        echo json_encode([
            'success' => false, 
            'error' => 'Exception: ' . $e->getMessage()
        ]);
    }
    exit;
}

echo json_encode(['success' => false, 'error' => 'Invalid action']);
?>