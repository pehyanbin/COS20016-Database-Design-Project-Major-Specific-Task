
<?php
require "config.php";

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_user'])) {
        $username = $_POST['username'];
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $role = $_POST['role'];
        
        $stmt = $conn->prepare("INSERT INTO members (username, password_hash, role) VALUES (?, ?, ?)");
        $stmt->execute([$username, $password, $role]);
        $message = "User added successfully!";
    }
    
    if (isset($_POST['delete_user'])) {
        $user_id = $_POST['user_id'];
        $stmt = $conn->prepare("DELETE FROM members WHERE id = ?");
        $stmt->execute([$user_id]);
        $message = "User deleted successfully!";
    }
}

$stmt = $conn->query("SELECT id, username, role FROM members");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Users</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<h2>User Management</h2>

<?php if (isset($message)): ?>
    <p class="success"><?php echo $message; ?></p>
<?php endif; ?>

<div class="section">
    <h3>Add New User</h3>
    <form method="post">
        <input type="text" name="username" placeholder="Username" required>
        <input type="password" name="password" placeholder="Password" required>
        <select name="role" required>
            <option value="user">User</option>
            <option value="auditor">Auditor</option>
            <option value="admin">Admin</option>
        </select>
        <button type="submit" name="add_user">Add User</button>
    </form>
</div>

<div class="section">
    <h3>Existing Users</h3>
    <table border="1">
        <tr>
            <th>ID</th>
            <th>Username</th>
            <th>Role</th>
            <th>Action</th>
        </tr>
        <?php foreach ($users as $user): ?>
        <tr>
            <td><?php echo $user['id']; ?></td>
            <td><?php echo htmlspecialchars($user['username']); ?></td>
            <td><?php echo htmlspecialchars($user['role']); ?></td>
            <td>
                <form method="post" style="display:inline;">
                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                    <button type="submit" name="delete_user" onclick="return confirm('Are you sure?')">Delete</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>

<p><a href="dashboard.php">Back to Dashboard</a></p>
</body>
</html>
