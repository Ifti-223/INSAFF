<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
include 'db.php';

if (isset($_GET['link'])) {
    $link = $_GET['link'];
    error_log("Accessing share link: $link");
    $stmt = $conn->prepare("SELECT item_id, password, permission FROM shares WHERE link = ?");
    $stmt->bind_param("s", $link);
    if (!$stmt->execute()) {
        error_log("Error querying shares: " . $stmt->error);
        die("Error: Database query failed");
    }
    $share = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($share) {
        $itemId = $share['item_id'];
        $permission = $share['permission'];

        // Check if password is required and provided
        if ($share['password'] && (!isset($_POST['password']) || !password_verify($_POST['password'], $share['password']))) {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $error = "Invalid password.";
            }
        } else {
            // Assume share is for a file
            $stmt = $conn->prepare("SELECT id, filename, path FROM files WHERE id = ? AND deleted_at IS NULL");
            $stmt->bind_param("i", $itemId);
            if (!$stmt->execute()) {
                error_log("Error querying file id=$itemId: " . $stmt->error);
                die("Error: Database query failed");
            }
            $file = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($file) {
                error_log("Found file: id=$itemId, path={$file['path']}");
                if ($permission === 'view') {
                    if (file_exists($file['path'])) {
                        header('Content-Type: application/octet-stream');
                        header('Content-Disposition: attachment; filename="' . $file['filename'] . '"');
                        readfile($file['path']);
                        exit;
                    } else {
                        error_log("File not found on filesystem: {$file['path']}");
                        $error = "Error: File not found on server.";
                    }
                } elseif ($permission === 'edit') {
                    header("Location: edit.php?file_id=" . $file['id']);
                    exit;
                } else {
                    $error = "Error: Invalid permission type.";
                }
            } else {
                error_log("File not found or deleted: id=$itemId");
                $error = "Error: File not found or has been deleted.";
            }
        }
    } else {
        error_log("Invalid share link: $link");
        $error = "Error: Invalid or expired share link.";
    }
} else {
    $error = "Error: No share link provided.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Access Shared File</title>
    <link rel="stylesheet" href="dashboard.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .header {
            background-color: transparent;
            color: white;
            padding: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .button {
            background-color: #007bff;
            color: white;
            padding: 8px 16px;
            text-decoration: none;
            border-radius: 4px;
            margin-left: 10px;
        }
        .container {
            max-width: 600px;
            margin: 20px auto;
            padding: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .error {
            color: red;
            text-align: center;
        }
        form {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        label {
            font-weight: bold;
        }
        input[type="password"] {
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        button {
            background-color: #007bff;
            color: white;
            padding: 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        button:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
<div class="header">
    <img src="logo2.png" alt="Logo" class="logo" style="width: 200px; height: auto;">
    <div>
        <?php if (isset($_SESSION['user_id'])): ?>
            <a href="dashboard.php" class="button">Dashboard</a>
            <a href="logout.php" class="button">Logout</a>
        <?php endif; ?>
    </div>
</div>
<div class="container">
    <h2>Access Shared File</h2>
    <?php if (isset($error)): ?>
        <p class="error"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>
    <?php if (!isset($file)): ?>
        <form method="post">
            <label for="password">Password (if required):</label>
            <input type="password" name="password" id="password">
            <button type="submit">Access</button>
        </form>
    <?php endif; ?>
</div>
</body>
</html>