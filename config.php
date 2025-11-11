<?php
require_once "init_session.php";

$host = "localhost";
$dbname = "yanbindatabase1";

try {
    if (isset($_SESSION['username']) && isset($_SESSION['password'])) {
        $username = $_SESSION['username'];
        $password = $_SESSION['password'];
        $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } else {
        header("Location: login.php");
        exit;
    }
} catch(PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>