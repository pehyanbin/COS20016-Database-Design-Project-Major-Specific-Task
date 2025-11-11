<?php
// Database connection settings
$host = 'feenix-mariadb.swin.edu.au';
$db_username = 's105557394';
$db_password = '271005';
$database = 's105557394_db';
$table = 'KCH-login';

// Connect to database
function connectDB() {
    global $host, $db_username, $db_password, $database;
    
    try {
        $conn = new PDO("mysql:host=$host;dbname=$database", $db_username, $db_password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $conn;
    } catch(PDOException $e) {
        die("Connection failed: " . $e->getMessage());
    }
}

// Create table if doesn't exist
function createTable() {
    global $table;
    $conn = connectDB();
    
    $sql = "CREATE TABLE IF NOT EXISTS `$table` (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        email VARCHAR(100) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    $conn->exec($sql);
}
?>
