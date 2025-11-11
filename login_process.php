<?php
session_start();
require_once 'db_config.php';
createTable();

$username = filter_input(INPUT_POST, 'username', FILTER_UNSAFE_RAW);
$password = filter_input(INPUT_POST, 'password', FILTER_UNSAFE_RAW);

if (empty($username) || empty($password)) {
    header('Location: login.php?error=Fill all fields');
    exit();
}

$username = trim(preg_replace('/[^a-zA-Z0-9_]/', '', $username));
if (strlen($username) < 3 || strlen($username) > 30 || strlen($password) > 72) {
    header('Location: login.php?error=Invalid input');
    exit();
}

$conn = connectDB();
global $table;

$stmt = $conn->prepare("SELECT * FROM `$table` WHERE username = ?");
$stmt->execute([$username]);
$user = $stmt->fetch();

if ($user && password_verify($password, $user['password'])) {
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['role'] = $user['role'];
    header('Location: success.php');
    exit();
} else {
    header('Location: login.php?error=Invalid credentials');
    exit();
}
?>