<?php
session_start();
require_once 'db_config.php';
require 'settings.php';
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
$role = $_SESSION['role'];
$message = ''; $results = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['query'])) {
    $query = trim($_POST['query']);
    if ($role === 'admin') {
        try {
            $stmt = $conn->prepare($query);
            if (!$stmt) throw new Exception("Invalid query");
            $stmt->execute();
            if (stripos($query, 'SELECT') === 0 || stripos($query, 'SHOW') === 0) {
                $res = $stmt->get_result();
                $results = $res->fetch_all(MYSQLI_ASSOC);
                $message = "Found " . count($results) . " rows.";
            } else {
                $message = "Affected: " . $stmt->affected_rows;
            }
        } catch(Exception $e) { $message = "Error: " . $e->getMessage(); }
    } else {
        $first = strtoupper(explode(' ', $query)[0]);
        if (!in_array($first, ['SELECT','INSERT','UPDATE','DESCRIBE','EXPLAIN'])) {
            $message = "Error: Only SELECT, INSERT, UPDATE allowed.";
        } elseif (stripos($query, 'members') !== false) {
            $message = "Error: Access to members table denied.";
        } else {
            try {
                $stmt = $conn->prepare($query);
                if (!$stmt) throw new Exception("Invalid");
                $stmt->execute();
                if ($first === 'SELECT') {
                    $res = $stmt->get_result();
                    $results = $res->fetch_all(MYSQLI_ASSOC);
                    $message = "Found " . count($results) . " rows.";
                } else {
                    $message = "Affected: " . $stmt->affected_rows;
                }
            } catch(Exception $e) { $message = "Error: " . $e->getMessage(); }
        }
    }
}
?>
<!DOCTYPE html>
<html><head><title>DB Console</title><link rel="stylesheet" href="style.css"></head><body>
<?php include 'header.inc'; ?>
<div class="container">
    <h2>Database Console</h2>
    <p><strong>Role:</strong> <?= ucfirst($role) ?></p>
    <?php if ($role !== 'admin'): ?><p class="error">Limited to SELECT, INSERT, UPDATE. No access to <code>members</code>.</p><?php endif; ?>
    <form method="post">
        <textarea name="query" rows="3" cols="100" required><?= htmlspecialchars($_POST['query'] ?? 'SELECT * FROM Archers LIMIT 5') ?></textarea><br>
        <button type="submit">Execute</button>
    </form>
    <?php if ($message): ?><p class="<?= strpos($message,'Error')?'error':'success' ?>"><?= $message ?></p><?php endif; ?>
    <?php if ($results): ?>
    <table><tr><?php foreach(array_keys($results[0]) as $c): ?><th><?= htmlspecialchars($c) ?></th><?php endforeach; ?></tr>
    <?php foreach($results as $r): ?><tr><?php foreach($r as $v): ?><td><?= htmlspecialchars($v ?? 'NULL') ?></td><?php endforeach; ?></tr><?php endforeach; ?>
    </table>
    <?php endif; ?>
    <p><a href="success.php">Back</a></p>
</div>
<?php include 'footer.inc'; ?>
</body></html>