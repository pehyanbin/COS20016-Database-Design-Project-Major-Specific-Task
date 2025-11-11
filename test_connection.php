<?php
require "config.php";
echo "<h2>Testing Member Connection</h2>";
try {
    $stmt = $conn->query("SELECT USER() as user, DATABASE() as db");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p>Connected as: " . $result['user'] . "</p>";
    echo "<p>Database: " . $result['db'] . "</p>";
    
    $stmt = $conn->query("SHOW GRANTS");
    $grants = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "<h3>Member User Grants:</h3>";
    echo "<ul>";
    foreach ($grants as $grant) {
        echo "<li>" . htmlspecialchars($grant) . "</li>";
    }
    echo "</ul>";
} catch (Exception $e) {
    echo "<p class='error'>Member connection failed: " . $e->getMessage() . "</p>";
}

echo "<h2>Testing Admin Connection</h2>";
require "config_admin.php";
try {
    $stmt = $admin_conn->query("SELECT USER() as user, DATABASE() as db");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p>Connected as: " . $result['user'] . "</p>";
    echo "<p>Database: " . $result['db'] . "</p>";
    
    $stmt = $admin_conn->query("SHOW GRANTS");
    $grants = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "<h3>Admin User Grants:</h3>";
    echo "<ul>";
    foreach ($grants as $grant) {
        echo "<li>" . htmlspecialchars($grant) . "</li>";
    }
    echo "</ul>";
} catch (Exception $e) {
    echo "<p class='error'>Admin connection failed: " . $e->getMessage() . "</p>";
}
?>