<?php
include 'db.php';
if (!isset($_SESSION['user_id'])) header("Location: login.php");
$userId = $_SESSION['user_id'];
if (!isset($_SESSION['clipboard'])) header("Location: dashboard.php");
$clipboard = $_SESSION['clipboard'];
$destinationFolderId = !empty($_POST['destination_folder_id']) ? intval($_POST['destination_folder_id']) : NULL;
function copyFolder($conn, $folderId, $userId, $destFolderId, $newName = null) {
    $stmt = $conn->prepare("SELECT folder_name FROM folders WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $folderId, $userId);
    $stmt->execute();
    $oldName = $stmt->get_result()->fetch_assoc()['folder_name'];
    $stmt->close();
    $folderName = $newName ?? $oldName . ' (copy)';
    // Insert new folder
    $query = $destFolderId ? "INSERT INTO folders (user_id, folder_name, parent_folder_id) VALUES (?, ?, ?)" : "INSERT INTO folders (user_id, folder_name) VALUES (?, ?)";
    $stmt = $conn->prepare($query);
    if ($destFolderId) {
        $stmt->bind_param("isi", $userId, $folderName, $destFolderId);
    } else {
        $stmt->bind_param("is", $userId, $folderName);
    }
    $stmt->execute();
    $newFolderId = $stmt->insert_id;
    $stmt->close();
    // Copy files
    $stmt = $conn->prepare("SELECT filename, path FROM files WHERE folder_id = ? AND user_id = ? AND deleted_at IS NULL");
    $stmt->bind_param("ii", $folderId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($file = $result->fetch_assoc()) {
        $newPath = "Uploads/copy_" . basename($file['path']);
        copy($file['path'], $newPath);
        $insertStmt = $conn->prepare("INSERT INTO files (user_id, folder_id, filename, path) VALUES (?, ?, ?, ?)");
        $insertStmt->bind_param("iiss", $userId, $newFolderId, $file['filename'], $newPath);
        $insertStmt->execute();
        $insertStmt->close();
    }
    $stmt->close();
    // Recurse subfolders
    $stmt = $conn->prepare("SELECT id FROM folders WHERE parent_folder_id = ? AND user_id = ? AND deleted_at IS NULL");
    $stmt->bind_param("ii", $folderId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($subfolder = $result->fetch_assoc()) {
        copyFolder($conn, $subfolder['id'], $userId, $newFolderId);
    }
    $stmt->close();
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = $clipboard['type'];
    $id = $clipboard['id'];
    $action = $clipboard['action'];
    if ($type === 'file') {
        $stmt = $conn->prepare("SELECT filename, path, folder_id FROM files WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $id, $userId);
        $stmt->execute();
        $file = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        $newFolderId = $destinationFolderId;
        $newName = $file['filename'];
        // Check unique
        $checkQuery = $newFolderId ? "SELECT id FROM files WHERE user_id = ? AND filename = ? AND folder_id = ?" : "SELECT id FROM files WHERE user_id = ? AND filename = ? AND folder_id IS NULL";
        $checkStmt = $conn->prepare($checkQuery);
        if ($newFolderId) {
            $checkStmt->bind_param("isi", $userId, $newName, $newFolderId);
        } else {
            $checkStmt->bind_param("is", $userId, $newName);
        }
        $checkStmt->execute();
        if ($checkStmt->get_result()->num_rows > 0) {
            $newName .= ' (copy)';
        }
        $checkStmt->close();
        if ($action === 'copy') {
            $newPath = "Uploads/copy_" . basename($file['path']);
            copy($file['path'], $newPath);
            $query = $newFolderId ? "INSERT INTO files (user_id, folder_id, filename, path) VALUES (?, ?, ?, ?)" : "INSERT INTO files (user_id, filename, path) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($query);
            if ($newFolderId) {
                $stmt->bind_param("iiss", $userId, $newFolderId, $newName, $newPath);
            } else {
                $stmt->bind_param("iss", $userId, $newName, $newPath);
            }
            $stmt->execute();
            $stmt->close();
        } else { // move
            $stmt = $conn->prepare("UPDATE files SET folder_id = ? WHERE id = ? AND user_id = ?");
            $stmt->bind_param("iii", $newFolderId, $id, $userId);
            $stmt->execute();
            $stmt->close();
        }
    } elseif ($type === 'folder') {
        if ($action === 'copy') {
            copyFolder($conn, $id, $userId, $destinationFolderId);
        } else { // move
            $stmt = $conn->prepare("UPDATE folders SET parent_folder_id = ? WHERE id = ? AND user_id = ?");
            $stmt->bind_param("iii", $destinationFolderId, $id, $userId);
            $stmt->execute();
            $stmt->close();
        }
    }
    unset($_SESSION['clipboard']);
}
header("Location: dashboard.php" . ($destinationFolderId ? "?folder_id=$destinationFolderId" : ""));
exit;
?>