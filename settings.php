<?php
$host = "feenix-mariadb.swin.edu.au";
$user = "s105557394";
$pwd = "271005";
$sql_db = "s105557394_db";

$conn = @mysqli_connect($host, $user, $pwd, $sql_db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>