<?php
// Принудительно используем HTTPS для iOS compatibility
$base_url = 'https://cx10577.tw1.ru';


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