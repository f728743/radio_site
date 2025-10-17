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
    $query = $_GET['q'] ?? '';
    $type = $_GET['type'] ?? 'name';
    $page = max(1, intval($_GET['page'] ?? 1));
    $limit = min(100, max(1, intval($_GET['limit'] ?? 50)));
    
    if (empty($query)) {
        http_response_code(400);
        echo json_encode([
            'success' => false, 
            'error' => 'Query parameter "q" is required',
            'received_params' => $_GET
        ]);
        exit;
    }
    
    // Проверяем допустимые типы поиска
    $allowed_types = ['name', 'tag', 'language'];
    if (!in_array($type, $allowed_types)) {
        http_response_code(400);
        echo json_encode([
            'success' => false, 
            'error' => 'Invalid type. Allowed: ' . implode(', ', $allowed_types)
        ]);
        exit;
    }
    
    try {
        $manager = new RadioStationManager();
        
        // Поиск реальных радиостанций
        $realRadio = $manager->searchActiveStations($query, $type, $page, $limit);
        
        // Поиск SIM радиостанций (только для типов name и tag)
        $simRadio = [];
        if ($type === 'name' || $type === 'tag') {
            $simRadio = $manager->searchSimStations($query, $type);
        }
        
        echo json_encode([
            'success' => true,
            'real_radio' => $realRadio,
            'sim_radio' => $simRadio,
            'query' => $query,
            'type' => $type
        ]);
        
    } catch(Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false, 
            'error' => 'Internal server error: ' . $e->getMessage()
        ]);
        error_log("API Search Error: " . $e->getMessage());
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
}
?>