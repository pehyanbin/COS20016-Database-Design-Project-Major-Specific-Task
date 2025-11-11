<!DOCTYPE html>
<html>
<head>
    <title>Login</title> 
    <link rel="stylesheet" href="style.css">
</head>
<body>
<h2>Login</h2>
<form method="post" action="">
    <label>Username:</label>
    <input type="text" name="username" required><br>
    <label>Password:</label>
    <input type="password" name="password" required><br>
    <button type="submit">Login</button>
</form>
<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    $host = "localhost";
    $dbname = "yanbindatabase1";
    
    try {
        // Create database connection with user-provided credentials
        $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $stmt = $conn->prepare("SELECT * FROM members WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            if (password_verify($password, $user['password_hash'])) {
                session_start();
                $_SESSION['username'] = $username;
                $_SESSION['password'] = $password; // Store for DB connections
                $_SESSION['role'] = $user['role'];
                
                header("Location: dashboard.php");
                exit;
            } else {
                $error = "Invalid username or password.";
            }
        } else {
            $error = "Invalid username or password.";
        }
    } catch(PDOException $e) {
        $error = "Database connection failed: " . $e->getMessage();
    }
}
if(isset($error)) echo "<p class='error'>$error</p>"; ?>
</body>
</html>