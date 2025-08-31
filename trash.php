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

$files = [];
$folders = [];
$error = null;
$message = isset($_GET['message']) ? htmlspecialchars($_GET['message']) : '';

// Fetch trashed files
$stmt = $conn->prepare("SELECT id, filename, deleted_at FROM trash_files WHERE user_id = ?");
$stmt->bind_param("i", $userId);
if ($stmt->execute()) {
    $files = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} else {
    error_log("Error querying trash_files: " . $stmt->error);
    $error = "Error: Failed to load trashed files";
}
$stmt->close();

// Fetch trashed folders
$stmt = $conn->prepare("SELECT id, folder_name, deleted_at FROM folders WHERE user_id = ? AND deleted_at IS NOT NULL");
$stmt->bind_param("i", $userId);
if ($stmt->execute()) {
    $folders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} else {
    error_log("Error querying folders: " . $stmt->error);
    $error = "Error: Failed to load trashed folders";
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Trash</title>
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
        a { color: #007bff; text-decoration: none; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
<div class="header">
    <img src="logo2.png" alt="Logo" class="logo" style="width: 200px; height: auto;">
    <div>
        <a href="dashboard.php" class="button">Dashboard</a>
        <a href="activity_log.php" class="button">Activity Log</a>
        <a href="logout.php" class="button">Logout</a>
    </div>
</div>
<div class="container">
    <h2>Trash</h2>
    <?php if (isset($error)): ?>
        <p class="error"><?php echo $error; ?></p>
    <?php endif; ?>
    <?php if ($message): ?>
        <p class="message"><?php echo $message; ?></p>
    <?php endif; ?>
    <h3>Files</h3>
    <?php if (empty($files)): ?>
        <p>No files in trash.</p>
    <?php else: ?>
        <table>
            <tr>
                <th>File Name</th>
                <th>Deleted At</th>
                <th>Actions</th>
            </tr>
            <?php foreach ($files as $file): ?>
                <tr>
                    <td><?php echo htmlspecialchars($file['filename']); ?></td>
                    <td><?php echo htmlspecialchars($file['deleted_at']); ?></td>
                    <td>
                        <a href="restore.php?type=file&id=<?php echo $file['id']; ?>" class="button">Restore</a>
                        <a href="permanent_delete.php?type=file&id=<?php echo $file['id']; ?>" class="button">Delete Permanently</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
    <h3>Folders</h3>
    <?php if (empty($folders)): ?>
        <p>No folders in trash.</p>
    <?php else: ?>
        <table>
            <tr>
                <th>Folder Name</th>
                <th>Deleted At</th>
                <th>Actions</th>
            </tr>
            <?php foreach ($folders as $folder): ?>
                <tr>
                    <td><?php echo htmlspecialchars($folder['folder_name']); ?></td>
                    <td><?php echo htmlspecialchars($folder['deleted_at']); ?></td>
                    <td>
                        <a href="restore.php?type=folder&id=<?php echo $folder['id']; ?>" class="button">Restore</a>
                        <a href="permanent_delete.php?type=folder&id=<?php echo $folder['id']; ?>" class="button">Delete Permanently</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
    <p><a href="dashboard.php">Back to Dashboard</a></p>
</div>
</body>
</html>