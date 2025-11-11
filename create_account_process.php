<?php
session_start();
require_once 'db_config.php';
createTable();

$username = filter_input(INPUT_POST, 'username', FILTER_UNSAFE_RAW);
$email = filter_input(INPUT_POST, 'email', FILTER_UNSAFE_RAW);
$password = filter_input(INPUT_POST, 'password', FILTER_UNSAFE_RAW);
$confirm = filter_input(INPUT_POST, 'confirm_password', FILTER_UNSAFE_RAW);

if (empty($username) || empty($email) || empty($password) || empty($confirm)) {
    header('Location: create_account.php?error=Fill all fields');
    exit();
}

$username = trim(preg_replace('/[^a-zA-Z0-9_]/', '', $username));
$email = filter_var(trim($email), FILTER_SANITIZE_EMAIL);

if (strlen($username) < 3 || strlen($username) > 30 || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 100 || strlen($password) < 6 || strlen($password) > 72 || $password !== $confirm) {
    header('Location: create_account.php?error=Invalid input');
    exit();
}

$conn = connectDB();
global $table;

$stmt = $conn->prepare("SELECT id FROM `$table` WHERE username = ? OR email = ?");
$stmt->execute([$username, $email]);
if ($stmt->fetch()) {
    header('Location: create_account.php?error=Username or email exists');
    exit();
}

$hash = password_hash($password, PASSWORD_DEFAULT);
$stmt = $conn->prepare("INSERT INTO `$table` (username, email, password, role) VALUES (?, ?, ?, 'member')");
$stmt->execute([$username, $email, $hash]);

header('Location: create_account.php?success=Account created! Login now.');
exit();
?>