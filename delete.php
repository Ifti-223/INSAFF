<?php
include 'db.php';
if (!isset($_SESSION['user_id'])) header("Location: login.php");
$userId = $_SESSION['user_id'];
function setDeletedFolder($conn, $folderId, $userId) {
    // Set files in folder
    $stmt = $conn->prepare("UPDATE files SET deleted_at = CURRENT_TIMESTAMP WHERE folder_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $folderId, $userId);
    $stmt->execute();
    $stmt->close();
    logActivity($conn, $userId, 'delete', $type, $id, $name, 'Moved to trash');
    // Recursively set subfolders
    $stmt = $conn->prepare("SELECT id FROM folders WHERE parent_folder_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $folderId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($subfolder = $result->fetch_assoc()) {
        setDeletedFolder($conn, $subfolder['id'], $userId);
    }
    $stmt->close();
    logActivity($conn, $userId, 'delete', $type, $id, $name, 'Moved to trash');
    // Set the folder itself
    $stmt = $conn->prepare("UPDATE folders SET deleted_at = NOW() WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $folderId, $userId);
    $stmt->execute();
    $stmt->close();
    logActivity($conn, $userId, 'delete', $type, $id, $name, 'Moved to trash');
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['file_id'])) {
        $fileId = intval($_POST['file_id']);
        $stmt = $conn->prepare("SELECT filename, path, folder_id FROM files WHERE id = ? AND user_id = ? AND deleted_at IS NULL");
        $stmt->bind_param("ii", $fileId, $userId);
        if ($stmt->execute()) {
            $file = $stmt->get_result()->fetch_assoc();
            if ($file) {
                $fileContent = file_exists($file['path']) ? file_get_contents($file['path']) : '';
                $stmt2 = $conn->prepare("INSERT INTO trash_files (filename, original_path, user_id, folder_id, file_content) VALUES (?, ?, ?, ?, ?)");
                $stmt2->bind_param("ssiis", $file['filename'], $file['path'], $userId, $file['folder_id'], $fileContent);
                if ($stmt2->execute()) {
                    $stmt3 = $conn->prepare("DELETE FROM files WHERE id = ? AND user_id = ?");
                    $stmt3->bind_param("ii", $fileId, $userId);
                    if ($stmt3->execute() && $stmt3->affected_rows > 0) {
                        if (file_exists($file['path'])) {
                            unlink($file['path']);
                        }
                        logActivity($conn, $userId, 'delete', 'file', $fileId, $file['filename'] ?? 'Unknown',  "'" . ($file['filename'] ?? 'Unknown') .  "' moved to trash.");
                    } else {
                        error_log("Error deleting file id=$fileId from files: " . $stmt3->error);
                    }
                    $stmt3->close();
                } else {
                    error_log("Error inserting file id=$fileId into trash_files: " . $stmt2->error);
                }
                $stmt2->close();
            }
        } else {
            error_log("Error querying file id=$fileId: " . $stmt->error);
        }
        $stmt->close();
    } elseif (isset($_POST['folder_id'])) {
        $folderId = intval($_POST['folder_id']);
        setDeletedFolder($conn, $folderId, $userId);
        logActivity($conn, $userId, 'delete', $type, $id, $name, 'Moved to trash');
    }
}
header("Location: dashboard.php");
exit;
?>