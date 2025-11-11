<?php
require "config.php";

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

$role = $_SESSION['role'];
$message = '';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['query'])) {
        $query = $_POST['query'];
        try {
            if ($role === 'admin') {
                $stmt = $conn->prepare($query);
                $stmt->execute();
                
                if (stripos($query, 'SELECT') === 0 || stripos($query, 'SHOW') === 0 || stripos($query, 'DESCRIBE') === 0 || stripos($query, 'EXPLAIN') === 0) {
                    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    $message = "Query executed successfully! Found " . count($results) . " rows.";
                } else {
                    $message = "Query executed successfully! Affected rows: " . $stmt->rowCount();
                }
            } else {
                $queryType = strtoupper(explode(' ', trim($query))[0]);
                $allowedQueries = ['SELECT', 'INSERT', 'UPDATE', 'DESCRIBE', 'EXPLAIN'];
                
                if (stripos($query, 'members') !== false || stripos($query, 'Members') !== false)  {
                    $message = "Error: Access to the 'members' table is restricted to administrators.";
                }
                else if (in_array($queryType, $allowedQueries)) {
                    $stmt = $conn->prepare($query);
                    $stmt->execute();

                    if ($queryType === 'SELECT' || $queryType === 'DESCRIBE' || $queryType === 'EXPLAIN') {
                        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        $message = "Query executed successfully! Found " . count($results) . " rows.";
                    } else {
                        $message = "Query executed successfully! Affected rows: " . $stmt->rowCount();
                    }
                } else {
                    $message = "Error: Your role only allows SELECT, INSERT, and UPDATE queries.";
                }
            }
        } catch (PDOException $e) {
            $message = "Database Error: " . $e->getMessage();
        }
    }
}



?>

<!DOCTYPE html>
<html>
<head>
    <title>Database Operations - Archery System</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<h2>Database Operations - Archery System</h2>
<p>Your role: <strong><?php echo htmlspecialchars($role); ?></strong></p>
<p>Database: <strong>Archery Database</strong></p>

<?php if ($message): ?>
    <p class="<?php echo (strpos($message, 'Error') !== false || strpos($message, 'failed') !== false) ? 'error' : 'success'; ?>">
        <?php echo $message; ?>
    </p>
<?php endif; ?>

<div class="section">
    <h3>Available Tables</h3>
    
    <!--
    <?php if (!empty($tables)): ?>
        <ul>
            <?php foreach ($tables as $table): ?>
                <li><?php echo htmlspecialchars($table); ?></li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p>No tables found or cannot access table information.</p>
    <?php endif; ?>
    -->

    <ul>
        <li>Archers</li>
        <li>Championships</li>
        <li>Categories</li>
        <li>Competitions</li>
        <li>Division</li>
        <li>End</li>
        <li>EquivalentRounds</li>
        <li>Ranges</li>
        <li>Rounds</li>
        <li>RoundDistance</li>
        <li>Scores</li>


    </ul>
    
</div>

<div class="section">
    <h3>Execute SQL Query</h3>
    <form method="post">
        <textarea name="query" rows="4" cols="50" placeholder="Enter SQL query" required><?php echo isset($_POST['query']) ? htmlspecialchars($_POST['query']) : 'SELECT * FROM Archers LIMIT 5'; ?></textarea><br>
        <button type="submit">Execute Query</button>
    </form>
    
    <?php if ($role !== 'admin'): ?>
    <p><small>Note: As a <?php echo $role; ?>, you can only execute SELECT, INSERT, and UPDATE queries.</small></p>
    <?php endif; ?>
</div>

<?php if (isset($results)): ?>
<div class="section">
    <h3>Query Results (<?php echo count($results); ?> rows)</h3>
    <?php if (!empty($results)): ?>
        <table border="1">
            <tr>
                <?php foreach (array_keys($results[0]) as $column): ?>
                    <th><?php echo htmlspecialchars($column); ?></th>
                <?php endforeach; ?>
            </tr>
            <?php foreach ($results as $row): ?>
            <tr>
                <?php foreach ($row as $value): ?>
                    <td><?php echo htmlspecialchars($value ?? 'NULL'); ?></td>
                <?php endforeach; ?>
            </tr>
            <?php endforeach; ?>
        </table>
    <?php else: ?>
        <p>No results returned.</p>
    <?php endif; ?>
</div>
<?php endif; ?>

<div class="section">
    <h3>Sample Archery Queries to Try</h3>
    <ul>
        <li><code>SELECT * FROM Archers;</code></li>
        <li><code>SELECT * FROM Division;</code></li>
        <li><code>SELECT * FROM Rounds;</code></li>
        <li><code>SELECT a.ArcherFName, a.ArcherLName, d.DivisionName FROM Archers a JOIN Division d ON a.DefaultDivisionID = d.DivisionID;</code></li>
        <li><code>INSERT INTO Archers (ArcherFName, ArcherLName, Gender, DateOfBirth, DefaultDivisionID) VALUES ('John', 'Doe', 'Male', '1990-01-01', 1);</code></li>
        <li><code>UPDATE Archers SET Club = 'Archery Club' WHERE ArchersID = 1;</code></li>
        <li><code>DESCRIBE Archers;</code></li>
        <li><code>EXPLAIN SELECT * FROM Archers WHERE Gender = 'Male';</code></li>
    </ul>
</div>

<p><a href="dashboard.php">Back to Dashboard</a></p>
</body>
</html>