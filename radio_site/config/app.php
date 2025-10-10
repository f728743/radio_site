<?php
// Автоматическое определение базового URL
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$host = $_SERVER['HTTP_HOST'];
$script_path = dirname($_SERVER['SCRIPT_NAME']);

// Формируем базовый URL
$base_url = rtrim($protocol . '://' . $host . $script_path, '/');

// Если мы в корне, убираем последний слэш
if ($base_url === $protocol . '://' . $host) {
    $base_url = $protocol . '://' . $host;
}

define('BASE_URL', $base_url);
define('ADMIN_URL', BASE_URL . '/admin');
define('API_URL', BASE_URL . '/api');

// Настройки приложения
define('ITEMS_PER_PAGE', 300);

// Подключение к базе данных
require_once __DIR__ . '/database.php';

// Включение вывода ошибок для разработки
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Установка временной зоны
date_default_timezone_set('Europe/Moscow');

// Функция для отладки
function debug($data) {
    echo '<pre>';
    print_r($data);
    echo '</pre>';
}
?>