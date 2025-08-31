<?php
include 'db.php';
if (!isset($_SESSION['user_id'])) header("Location: login.php");
$userId = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = $_POST['type'];
    $id = intval($_POST['id']);
    $newName = $_POST['new_name'];
    $folderId = !empty($_POST['folder_id']) ? intval($_POST['folder_id']) : NULL;

    if ($type === 'folder') {
        // Validate unique folder name
        $query = $folderId
            ? "SELECT id FROM folders WHERE user_id = ? AND folder_name = ? AND parent_folder_id = ? AND id != ?"
            : "SELECT id FROM folders WHERE user_id = ? AND folder_name = ? AND parent_folder_id IS NULL AND id != ?";
        $stmt = $conn->prepare($query);
        if ($folderId) {
            $stmt->bind_param("isii", $userId, $newName, $folderId, $id);
        } else {
            $stmt->bind_param("isi", $userId, $newName, $id);
        }
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            echo "<script>alert('Folder name already exists!'); window.location.href='dashboard.php?folder_id=$folderId';</script>";
            exit;
        }
        $stmt->close();

        // Update folder name
        $stmt = $conn->prepare("UPDATE folders SET folder_name = ? WHERE id = ? AND user_id = ?");
        $stmt->bind_param("sii", $newName, $id, $userId);
        $stmt->execute();
        $stmt->close();
    } else {
        // Validate unique file name
        $query = $folderId
            ? "SELECT id FROM files WHERE user_id = ? AND filename = ? AND folder_id = ? AND id != ?"
            : "SELECT id FROM files WHERE user_id = ? AND filename = ? AND folder_id IS NULL AND id != ?";
        $stmt = $conn->prepare($query);
        if ($folderId) {
            $stmt->bind_param("isii", $userId, $newName, $folderId, $id);
        } else {
            $stmt->bind_param("isi", $userId, $newName, $id);
        }
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            echo "<script>alert('File name already exists!'); window.location.href='dashboard.php?folder_id=$folderId';</script>";
            exit;
        }
        $stmt->close();

        // Update file name
        $stmt = $conn->prepare("UPDATE files SET filename = ? WHERE id = ? AND user_id = ?");
        $stmt->bind_param("sii", $newName, $id, $userId);
        $stmt->execute();
        $stmt->close();
    }
}

header("Location: dashboard.php" . ($folderId ? "?folder_id=$folderId" : ""));
exit;
?>