<?php
include 'db.php';
if (!isset($_SESSION['user_id'])) header("Location: login.php");
$userId = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $folderName = $_POST['folder_name'];
    $parentFolderId = !empty($_POST['parent_folder_id']) ? intval($_POST['parent_folder_id']) : NULL;

    // Validate unique folder name
    $query = $parentFolderId
        ? "SELECT id FROM folders WHERE user_id = ? AND folder_name = ? AND parent_folder_id = ?"
        : "SELECT id FROM folders WHERE user_id = ? AND folder_name = ? AND parent_folder_id IS NULL";
    $stmt = $conn->prepare($query);
    if ($parentFolderId) {
        $stmt->bind_param("isi", $userId, $folderName, $parentFolderId);
    } else {
        $stmt->bind_param("is", $userId, $folderName);
    }
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        echo "<script>alert('Folder name already exists!'); window.location.href='dashboard.php?folder_id=$parentFolderId';</script>";
        exit;
    }
    $stmt->close();

    // Create folder
    $query = $parentFolderId
        ? "INSERT INTO folders (user_id, folder_name, parent_folder_id) VALUES (?, ?, ?)"
        : "INSERT INTO folders (user_id, folder_name) VALUES (?, ?)";
    $stmt = $conn->prepare($query);
    if ($parentFolderId) {
        $stmt->bind_param("isi", $userId, $folderName, $parentFolderId);
    } else {
        $stmt->bind_param("is", $userId, $folderName);
    }
    $stmt->execute();
    $stmt->close();
}

header("Location: dashboard.php" . ($parentFolderId ? "?folder_id=$parentFolderId" : ""));
exit;
?>