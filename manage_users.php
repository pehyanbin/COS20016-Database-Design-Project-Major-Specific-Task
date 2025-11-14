<?php
session_start();
require_once 'db_config.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php?error=Access denied');
    exit;
}
$conn = connectDB();
global $table;
$message = '';

if (isset($_POST['add_user'])) {
    $u = trim($_POST['username']); $e = trim($_POST['email']); $p = password_hash($_POST['password'], PASSWORD_DEFAULT); $r = $_POST['role'];
    $stmt = $conn->prepare("INSERT INTO `$table` (username, email, password, role) VALUES (?, ?, ?, ?)");
    try { $stmt->execute([$u, $e, $p, $r]); $message = "User added."; } catch(Exception $e) { $message = "Error: " . $e->getMessage(); }
}
if (isset($_POST['delete_user'])) {
    $id = intval($_POST['user_id']);
    if ($id !== $_SESSION['user_id']) {
        $stmt = $conn->prepare("DELETE FROM `$table` WHERE id = ?");
        $stmt->execute([$id]);
        $message = "User deleted.";
    }
}
$users = $conn->query("SELECT id, username, email, role FROM `$table`")->fetchAll();
?>
<!DOCTYPE html>
<html>
    <head>
        <title>Manage Users</title><link rel="stylesheet" href="style.css">
    </head>
    
    <body>
    <?php include 'header.inc'; ?>
    <div class="container">
        <h2>User Management</h2>
        <?php if ($message): ?><p class="<?= strpos($message,'Error')?'error':'success' ?>"><?= htmlspecialchars($message) ?></p><?php endif; ?>
        <div class="section">
            <h3>Add User</h3>
            <form method="post">
                <input name="username" placeholder="Username" required>
                <input name="email" type="email" placeholder="Email" required>
                <input name="password" type="password" placeholder="Password" required>
                <select name="role"><option value="member">Member</option><option value="admin">Admin</option></select>
                <button name="add_user">Add</button>
            </form>
        </div>
        <div class="section">
            <h3>Users</h3>
            <table><tr><th>ID</th><th>Username</th><th>Email</th><th>Role</th><th>Action</th></tr>
            <?php foreach ($users as $u): ?>
            <tr>
                <td><?= $u['id'] ?></td>
                <td><?= htmlspecialchars($u['username']) ?></td>
                <td><?= htmlspecialchars($u['email']) ?></td>
                <td><?= $u['role'] ?></td>
                <td><?php if ($u['id'] != $_SESSION['user_id']): ?>
                <form method="post" style="display: inline;">
                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                    <button type="submit" name="delete_user" id="delete-user-btn" onclick="return confirm('Are you sure you want to delete this user?')">Delete</button>
                </form>
                <?php endif; ?></td>
            </tr>
            <?php endforeach; ?>
            </table>
        </div>
        <p><a href="success.php">Back</a></p>
    </div>
    <?php include 'footer.inc'; ?>
    </body>
</html>