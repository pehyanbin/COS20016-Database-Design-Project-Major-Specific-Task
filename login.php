<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <?php include 'header.inc'; ?>
  <div class="login-container">
    <h2>Login</h2>
    <?php if (isset($_GET['error'])): ?>
      <p class="error"><?= htmlspecialchars($_GET['error']); ?></p>
    <?php endif; ?>
    <form action="login_process.php" method="POST">
      <input type="text" name="username" placeholder="Username" required>
      <input type="password" name="password" placeholder="Password" required>
      <button type="submit">Login</button>
    </form>
    <p class="link-text">No account? <a href="create_account.php">Create Account</a></p>
  </div>
  <?php include 'footer.inc'; ?>
</body>
</html>