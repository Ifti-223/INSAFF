<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
include 'db.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
$userId = $_SESSION['user_id'];

$error = null;
$logs = [];
$message = isset($_GET['message']) ? htmlspecialchars($_GET['message']) : '';

// Handle clear logs request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clear_logs'])) {
    $stmt = $conn->prepare("DELETE FROM activity_log WHERE user_id = ?");
   media: $stmt->bind_param("i", $userId);
    if ($stmt->execute()) {
        logActivity($conn, $userId, 'clear_logs', 'activity_log', 0, 'Activity Log', 'Cleared all activity logs');
        $message = "Activity log cleared successfully";
    } else {
        error_log("Error clearing activity log: " . $stmt->error);
        $error = "Error: Failed to clear activity log";
    }
    $stmt->close();
}

// Fetch logs
$stmt = $conn->prepare("SELECT action, item_type, item_id, item_name, details, created_at 
                        FROM activity_log 
                        WHERE user_id = ? 
                        ORDER BY created_at DESC");
$stmt->bind_param("i", $userId);
if (!$stmt->execute()) {
    error_log("Error querying activity log: " . $stmt->error);
    $error = "Error: Database query failed";
} else {
    $logs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Activity Log</title>
    <link rel="stylesheet" href="dashboard.css">
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f4; margin: 0; padding: 0; }
        .header { background-color: transparent; color: white; padding: 10px; display: flex; justify-content: space-between; align-items: center; }
        .button { background-color: #007bff; color: white; padding: 8px 16px; text-decoration: none; border-radius: 4px; margin-left: 10px; }
        .container { max-width: 800px; margin: 20px auto; padding: 20px; background: white; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        .error, .message { color: red; text-align: center; }
        .message { color: green; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 8px; border: 1px solid #ddd; text-align: left; }
        th { background-color: #f2f2f2; }
        a, input[type="submit"] { color: #007bff; text-decoration: none; }
        a:hover, input[type="submit"]:hover { text-decoration: underline; }
        input[type="submit"] { background-color: #dc3545; color: white; border: none; cursor: pointer; }
    </style>
</head>
<body>
<div class="header">
    <img src="logo2.png" alt="Logo" class="logo" style="width: 200px; height: auto;">
    <div>
        <a href="dashboard.php" class="button">Dashboard</a>
        <a href="trash.php" class="button">Trash</a>
        <a href="logout.php" class="button">Logout</a>
    </div>
</div>
<div class="container">
    <h2>Activity Log</h2>
    <form method="post" style="margin-bottom: 20px;">
        <input type="submit" name="clear_logs" value="Clear Activity Log" class="button" onclick="return confirm('Are you sure you want to clear all activity logs?');">
    </form>
    <?php if (isset($error)): ?>
        <p class="error"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>
    <?php if ($message): ?>
        <p class="message"><?php echo $message; ?></p>
    <?php endif; ?>
    <?php if (empty($logs)): ?>
        <p>No activities recorded.</p>
    <?php else: ?>
        <table>
            <tr>
                <th>Action</th>
                <th>Details</th>
                <th>Time</th>
            </tr>
            <?php foreach ($logs as $log): ?>
                <tr>
                    <td><?php echo htmlspecialchars($log['action'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($log['details'] !== null ? $log['details'] : ''); ?></td>
                    <td><?php echo htmlspecialchars($log['created_at'] !== null ? $log['created_at'] : ''); ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
    <p><a href="dashboard.php">Back to Dashboard</a></p>
</div>
</body>
</html>