<?php
$host = "localhost";
$user = "root";
$pwd = "";
$sql_db = "yanbindatabase1";

$conn = @mysqli_connect($host, $user, $pwd, $sql_db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>