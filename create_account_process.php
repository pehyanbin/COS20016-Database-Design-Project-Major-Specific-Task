<?php
session_start();
require_once 'db_config.php';

// Create table if needed
createTable();

// Get form data using filter_input for better security
// Note: We get raw values and validate them; prepared statements protect against SQL injection
$username = filter_input(INPUT_POST, 'username', FILTER_UNSAFE_RAW);
$email = filter_input(INPUT_POST, 'email', FILTER_UNSAFE_RAW);
$password = filter_input(INPUT_POST, 'password', FILTER_UNSAFE_RAW);
$confirm_password = filter_input(INPUT_POST, 'confirm_password', FILTER_UNSAFE_RAW);

// Check if fields are empty
if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
    header('Location: create_account.php?error=Please fill in all fields');
    exit();
}

// Trim whitespace from inputs
$username = trim($username);
$email = trim($email);

// Sanitize and validate username: alphanumeric and underscore only, 3-30 characters
$username = preg_replace('/[^a-zA-Z0-9_]/', '', $username); // Remove any invalid characters
if (strlen($username) < 3 || strlen($username) > 30) {
    header('Location: create_account.php?error=Username must be 3-30 characters and contain only letters, numbers, and underscores');
    exit();
}

// Sanitize and validate email
$email = filter_var($email, FILTER_SANITIZE_EMAIL);
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: create_account.php?error=Invalid email format');
    exit();
}

// Check email length (max 100 characters as per database)
if (strlen($email) > 100) {
    header('Location: create_account.php?error=Email is too long (maximum 100 characters)');
    exit();
}

// Validate password length (minimum 6 characters)
if (strlen($password) < 6) {
    header('Location: create_account.php?error=Password must be at least 6 characters');
    exit();
}

// Check password maximum length (prevent DoS attacks - bcrypt limit is 72 bytes)
if (strlen($password) > 72) {
    header('Location: create_account.php?error=Password is too long (maximum 72 characters)');
    exit();
}

// Check if passwords match
if ($password !== $confirm_password) {
    header('Location: create_account.php?error=Passwords do not match');
    exit();
}

// Connect to database
$conn = connectDB();
global $table;

// Check if username already exists
$stmt = $conn->prepare("SELECT id FROM `$table` WHERE username = ?");
$stmt->execute([$username]);
if ($stmt->fetch()) {
    header('Location: create_account.php?error=Username already exists');
    exit();
}

// Check if email already exists
$stmt = $conn->prepare("SELECT id FROM `$table` WHERE email = ?");
$stmt->execute([$email]);
if ($stmt->fetch()) {
    header('Location: create_account.php?error=Email already exists');
    exit();
}

// Hash the password
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Insert new user into database
$stmt = $conn->prepare("INSERT INTO `$table` (username, email, password) VALUES (?, ?, ?)");
$stmt->execute([$username, $email, $hashed_password]);

// Success!
header('Location: create_account.php?success=Account created successfully! You can now login.');
exit();
?>
