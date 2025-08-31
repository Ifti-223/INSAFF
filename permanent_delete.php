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
    $errorMessage = "Error: Item not found or already deleted";

    if ($type === 'file') {
        $stmt = $conn->prepare("SELECT filename FROM trash_files WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $id, $userId);
        if ($stmt->execute()) {
            $file = $stmt->get_result()->fetch_assoc();
            if ($file) {
                logActivity($conn, $userId, 'permanent_delete', 'file', $id, $file['filename'] ?? 'Unknown', "'" . ($file['filename'] ?? 'Unknown') .  "' Permanently deleted");
                $stmt2 = $conn->prepare("DELETE FROM trash_files WHERE id = ? AND user_id = ?");
                $stmt2->bind_param("ii", $id, $userId);
                if ($stmt2->execute() && $stmt2->affected_rows > 0) {
                    header("Location: trash.php?message=" . urlencode("File permanently deleted"));
                } else {
                    error_log("Error deleting file id=$id from trash_files: " . $stmt2->error);
                    $errorMessage = "Error: Failed to delete file from database";
                }
                $stmt2->close();
            }
        } else {
            error_log("Error querying file id=$id: " . $stmt->error);
        }
        $stmt->close();
    } elseif ($type === 'folder') {
        $stmt = $conn->prepare("SELECT folder_name FROM folders WHERE id = ? AND user_id = ? AND deleted_at IS NOT NULL");
        $stmt->bind_param("ii", $id, $userId);
        if ($stmt->execute()) {
            $folder = $stmt->get_result()->fetch_assoc();
            if ($folder) {
                logActivity($conn, $userId, 'permanent_delete', 'folder', $id, $folder['folder_name'] ?? 'Unknown', "'" . ($folder['folder_name'] ?? 'Unknown') . "' permanently deleted");
                $stmt2 = $conn->prepare("DELETE FROM folders WHERE id = ? AND user_id = ? AND deleted_at IS NOT NULL");
                $stmt2->bind_param("ii", $id, $userId);
                if ($stmt2->execute() && $stmt2->affected_rows > 0) {
                    $stmt3 = $conn->prepare("DELETE FROM trash_files WHERE folder_id = ? AND user_id = ?");
                    $stmt3->bind_param("ii", $id, $userId);
                    $stmt3->execute();
                    $stmt3->close();
                    header("Location: trash.php?message=" . urlencode("Folder permanently deleted"));
                } else {
                    error_log("Error deleting folder id=$id: " . $stmt2->error);
                    $errorMessage = "Error: Failed to delete folder from database";
                }
                $stmt2->close();
            }
        } else {
            error_log("Error querying folder id=$id: " . $stmt->error);
        }
        $stmt->close();
    } else {
        $errorMessage = "Error: Invalid item type";
    }
    header("Location: trash.php?message=" . urlencode($errorMessage));
} else {
    header("Location: trash.php?message=" . urlencode("Error: Invalid request"));
}
?>