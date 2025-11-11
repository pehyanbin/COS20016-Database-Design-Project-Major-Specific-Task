<?php
session_start();
require_once 'db_config.php';

// Create table if needed
createTable();

// Get form data using filter_input for better security
// Note: We get raw values and validate them; prepared statements protect against SQL injection
$username = filter_input(INPUT_POST, 'username', FILTER_UNSAFE_RAW);
$password = filter_input(INPUT_POST, 'password', FILTER_UNSAFE_RAW);

// Check if fields are empty
if (empty($username) || empty($password)) {
    header('Location: login.php?error=Please fill in all fields');
    exit();
}

// Trim whitespace from username
$username = trim($username);

// Sanitize username: remove any invalid characters (alphanumeric and underscore only)
$username = preg_replace('/[^a-zA-Z0-9_]/', '', $username);

// Validate username format: 3-30 characters
if (strlen($username) < 3 || strlen($username) > 30) {
    header('Location: login.php?error=Invalid username format');
    exit();
}

// Check password length (prevent DoS attacks - bcrypt limit is 72 bytes)
if (strlen($password) > 72) {
    header('Location: login.php?error=Invalid username or password');
    exit();
}

// Connect to database
$conn = connectDB();
global $table;

// Check if user exists
$stmt = $conn->prepare("SELECT * FROM `$table` WHERE username = ?");
$stmt->execute([$username]);
$user = $stmt->fetch();

// Check password
if ($user && password_verify($password, $user['password'])) {
    // Login successful - save user info in session
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['email'] = $user['email'];
    
    // Go to success page
    header('Location: success.php');
    exit();
} else {
    // Login failed
    header('Location: login.php?error=Invalid username or password');
    exit();
}
?>
