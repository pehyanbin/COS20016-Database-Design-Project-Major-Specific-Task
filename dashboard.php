<?php
require "config.php";

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

$role = $_SESSION['role'];
?>

<!DOCTYPE html>
<html>
<head>
    <title>Dashboard - Archery System</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<h2>Welcome to Archery System, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h2>
<p>Your role: <strong><?php echo htmlspecialchars($role); ?></strong></p>

<?php
if ($role === 'admin') {
    echo "<p>You have full administrative privileges.</p>";
    echo "<a href='manage_users.php'>Manage Users</a><br>";
}
echo "<a href='database_operations.php'>Database Operations</a><br>";
?>

<p><a href="logout.php">Logout</a></p>
</body>
</html>