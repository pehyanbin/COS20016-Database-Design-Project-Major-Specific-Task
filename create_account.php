<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Create Account</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <?php include 'header.inc'; ?>

  <div class="container">
    <h2>Create Account</h2>
    
    <?php if (isset($_GET['error'])): ?>
      <p class="error"><?php echo htmlspecialchars($_GET['error']); ?></p>
    <?php endif; ?>
    
    <?php if (isset($_GET['success'])): ?>
      <p class="success"><?php echo htmlspecialchars($_GET['success']); ?></p>
    <?php endif; ?>
    
    <form action="create_account_process.php" method="POST">
      <input type="text" name="username" placeholder="Enter Username" required>
      <input type="email" name="email" placeholder="Enter Email" required>
      <input type="password" name="password" placeholder="Enter Password" required>
      <input type="password" name="confirm_password" placeholder="Confirm Password" required>
      <button type="submit">Create Account</button>
    </form>
    
    <p class="link-text">
      Already have an account? <a href="login.php">Login</a>
    </p>
  </div>

  <footer>
    <p>&copy; This page is created by Khong Cheng Hao for COS20031 Major Task</p>
  </footer>
</body>
</html>

