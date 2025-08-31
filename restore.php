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

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['type'], $_GET['id'])) {
    $type = $_GET['type'];
    $id = (int)$_GET['id'];
    $errorMessage = "Error: Item not found";

    if ($type === 'file') {
        $stmt = $conn->prepare("SELECT filename, original_path, folder_id, file_content FROM trash_files WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $id, $userId);
        if ($stmt->execute()) {
            $file = $stmt->get_result()->fetch_assoc();
            if ($file) {
                // Restore file to Uploads/
                $filePath = $file['original_path'];
                if (file_put_contents($filePath, $file['file_content']) !== false) {
                    // Insert back into files table
                    $stmt2 = $conn->prepare("INSERT INTO files (filename, path, user_id, folder_id) VALUES (?, ?, ?, ?)");
                    $stmt2->bind_param("ssii", $file['filename'], $filePath, $userId, $file['folder_id']);
                    if ($stmt2->execute()) {
                        $newFileId = $conn->insert_id;
                        // Delete from trash_files
                        $stmt3 = $conn->prepare("DELETE FROM trash_files WHERE id = ? AND user_id = ?");
                        $stmt3->bind_param("ii", $id, $userId);
                        if ($stmt3->execute()) {
                            logActivity($conn, $userId, 'restore', 'file', $newFileId, $file['filename'] ?? 'Unknown', "'" . ($file['filename'] ?? 'Unknown') .  "' is restored from trash.");
                            header("Location: trash.php?message=" . urlencode("File restored"));
                        } else {
                            error_log("Error deleting file id=$id from trash_files: " . $stmt3->error);
                            $errorMessage = "Error: Failed to remove file from trash";
                        }
                        $stmt3->close();
                    } else {
                        error_log("Error inserting file id=$id into files: " . $stmt2->error);
                        $errorMessage = "Error: Failed to restore file to database";
                    }
                    $stmt2->close();
                } else {
                    error_log("Error writing file to $filePath");
                    $errorMessage = "Error: Failed to restore file to disk";
                }
            }
        } else {
            error_log("Error querying trash_files id=$id: " . $stmt->error);
        }
        $stmt->close();
    } elseif ($type === 'folder') {
        $stmt = $conn->prepare("SELECT folder_name FROM folders WHERE id = ? AND user_id = ? AND deleted_at IS NOT NULL");
        $stmt->bind_param("ii", $id, $userId);
        if ($stmt->execute()) {
            $folder = $stmt->get_result()->fetch_assoc();
            if ($folder) {
                $stmt2 = $conn->prepare("UPDATE folders SET deleted_at = NULL WHERE id = ? AND user_id = ?");
                $stmt2->bind_param("ii", $id, $userId);
                if ($stmt2->execute() && $stmt2->affected_rows > 0) {
                    logActivity($conn, $userId, 'restore', 'folder', $id, $folder['folder_name'] ?? 'Unknown',  "'" . ($file['filename'] ?? 'Unknown') .  "' is restored from trash.");
                    header("Location: trash.php?message=" . urlencode("Folder restored"));
                } else {
                    error_log("Error restoring folder id=$id: " . $stmt2->error);
                    $errorMessage = "Error: Failed to restore folder";
                }
                $stmt2->close();
            }
        }
        $stmt->close();
    } else {
        $errorMessage = "Error: Invalid item type";
    }
    header("Location: trash.php?message=" . urlencode($errorMessage));
}
?>