<?php
function getDatabaseConnection() {
    $host = 'localhost';
    $dbname = 'cx10577_simradio';
    $username = 'cx10577';
    $password = 'f728743';
    
    try {
        $db = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $db;
    } catch(PDOException $e) {
        die("Connection failed: " . $e->getMessage());
    }
}
?>
