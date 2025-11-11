<header>
	<h1>Archery Database System</h1>
    <nav>
        <ul>
            <?php if (basename($_SERVER['PHP_SELF']) != 'success.php'): ?>
            <li><a href="login.php" title="Login page">Log In</a></li>
            <?php endif; ?>
            <li><a href="score_entry.php" title="Score Entry">Score Entry</a></li>
            <li><a href="showscore.php" title="Show Score">Show Score</a></li>
            <?php if (basename($_SERVER['PHP_SELF']) == 'success.php'): ?>
            <li><a href="logout.php" title="Logout">Logout</a></li>
            <?php endif; ?>
        </ul>	
    </nav>
</header>