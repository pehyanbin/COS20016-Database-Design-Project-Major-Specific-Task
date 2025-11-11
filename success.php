<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login Successful</title>
  <link rel="stylesheet" href="style.css">
</head>
<body class="success-page">
  <?php include 'header.inc'; ?>

  <div class="container">
    <div class="success-icon">✓</div>
    <h2>Login Successful!</h2>
    <p>Welcome! You have successfully logged in to your account.</p>
    
    <?php
    session_start();
    
    if (isset($_SESSION['username'])) {
      echo '<div class="user-info">';
      echo '<p><strong>Username:</strong> ' . htmlspecialchars($_SESSION['username']) . '</p>';
      
      if (isset($_SESSION['email'])) {
        echo '<p><strong>Email:</strong> ' . htmlspecialchars($_SESSION['email']) . '</p>';
      }
      echo '</div>';
    }
    ?>
  </div>

  <footer>
    <p>&copy; This page is created by Khong Cheng Hao for COS20031 Major Task</p>
  </footer>
</body>
</html>

