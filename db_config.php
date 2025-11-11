<?php
// db_config.php
$host = 'localhost';
$database = 'yanbindatabase1';
$table = 'members';  

function connectDB() {
    global $host, $database;
    try {
        $conn = new PDO("mysql:host=$host;dbname=$database", 'root', ''); // Adjust if needed
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $conn;
    } catch(PDOException $e) {
        die("Connection failed: " . $e->getMessage());
    }
}

function createTable() {
    global $table;
    $conn = connectDB();
    $sql = "CREATE TABLE IF NOT EXISTS `$table` (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        email VARCHAR(100) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        role ENUM('member', 'admin') DEFAULT 'member'
    )";
    $conn->exec($sql);
}
?>