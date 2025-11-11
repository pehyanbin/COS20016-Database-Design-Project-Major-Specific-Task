<header>
    <h1>Archery Database System</h1>
    <nav>
        <ul>
            <?php if (!isset($_SESSION)) session_start(); ?>
            <?php if (!isset($_SESSION['user_id'])): ?>
                <li><a href="login.php">Log In</a></li>
            <?php else: ?>
                <li><a href="success.php">Dashboard</a></li>
                <li><a href="score_entry.php">Score Entry</a></li>
                <li><a href="showscore.php">Show Score</a></li>
                <?php if ($_SESSION['role'] === 'admin'): ?>
                    <li><a href="manage_users.php">Manage Users</a></li>
                    <li><a href="database_operations.php">DB Console</a></li>
                <?php endif; ?>
                <li><a href="logout.php">Logout</a></li>
            <?php endif; ?>
        </ul>
    </nav>
</header>