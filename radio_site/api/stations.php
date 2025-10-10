<?php
require_once '../config/app.php';
require_once '../classes/RadioStationManager.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Обработка preflight запросов
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $uuid = $_GET['uuid'] ?? '';
    
    if (empty($uuid)) {
        http_response_code(400);
        echo json_encode([
            'success' => false, 
            'error' => 'Station UUID is required',
            'received_params' => $_GET
        ]);
        exit;
    }
    
    try {
        $manager = new RadioStationManager();
        $station = $manager->getStationByUUID($uuid);
        
        if ($station) {
            echo json_encode([
                'success' => true,
                'data' => $station
            ]);
        } else {
            http_response_code(404);
            echo json_encode([
                'success' => false, 
                'error' => 'Station not found'
            ]);
        }
        
    } catch(Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false, 
            'error' => 'Internal server error: ' . $e->getMessage()
        ]);
        error_log("API Station Error: " . $e->getMessage());
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
}
?>