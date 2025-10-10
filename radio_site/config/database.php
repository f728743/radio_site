<?php
function getDatabaseConnection() {
    $host = 'localhost';
    $dbname = 'cx10577_simradio';
    $username = 'cx10577_simradio';
    $password = 'f728743';
    
    try {
        // Добавляем параметры для MySQL 8+ аутентификации
        $dsn = "mysql:host=$host;port=3306;dbname=$dbname;charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            // Параметры для MySQL 8+
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
            PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
        ];
        
        $db = new PDO($dsn, $username, $password, $options);
        return $db;
    } catch(PDOException $e) {
        die("Connection failed: " . $e->getMessage());
    }
}
?>