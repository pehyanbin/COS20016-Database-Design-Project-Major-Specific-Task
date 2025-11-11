<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard</title>
  <link rel="stylesheet" href="style.css">
</head>
<body class="success-page">
  <?php include 'header.inc'; ?>
  <div class="container">
    <div class="success-icon">✓</div>
    <h2>Welcome, <?= htmlspecialchars($_SESSION['username']) ?>!</h2>
    <p><strong>Role:</strong> <?= ucfirst($_SESSION['role']) ?></p>
    <?php if ($_SESSION['role'] === 'admin'): ?>
      <p>You have <strong>full admin access</strong>.</p>
      <p><a href="manage_users.php">Manage Users</a> | <a href="database_operations.php">DB Console</a></p>
    <?php else: ?>
      <p><a href="database_operations.php">Run Queries (Limited)</a></p>
    <?php endif; ?>
  </div>
  <?php include 'footer.inc'; ?>
</body>
</html>