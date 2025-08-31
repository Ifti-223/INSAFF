<?php
include 'db.php';
if (!isset($_SESSION['user_id'])) header("Location: login.php");
$userId = $_SESSION['user_id'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = $_POST['type'];
    $itemId = intval($_POST['id']);
    $permission = $_POST['permission'];
    $password = $_POST['password'] ? password_hash($_POST['password'], PASSWORD_DEFAULT) : NULL;
    $link = bin2hex(random_bytes(16));
    $stmt = $conn->prepare("INSERT INTO shares (user_id, type, item_id, link, permission, password) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isisss", $userId, $type, $itemId, $link, $permission, $password);
    $stmt->execute();
    $stmt->close();
    
    // Fetch item name based on type
    $itemName = 'Unknown';
    if ($type === 'file') {
        $stmt = $conn->prepare("SELECT filename FROM files WHERE id = ? AND user_id = ? AND deleted_at IS NULL");
        $stmt->bind_param("ii", $itemId, $userId);
        if ($stmt->execute()) {
            $file = $stmt->get_result()->fetch_assoc();
            $itemName = $file['filename'] ?? 'Unknown';
        }
        $stmt->close();
    } elseif ($type === 'folder') {
        $stmt = $conn->prepare("SELECT folder_name FROM folders WHERE id = ? AND user_id = ? AND deleted_at IS NULL");
        $stmt->bind_param("ii", $itemId, $userId);
        if ($stmt->execute()) {
            $folder = $stmt->get_result()->fetch_assoc();
            $itemName = $folder['folder_name'] ?? 'Unknown';
        }
        $stmt->close();
    }
    logActivity($conn, $userId, 'share', $type, $itemId, $itemName, "'" . $itemName . "' shared");
    echo "<script>alert('Share link: access.php?link=$link'); window.location.href='dashboard.php';</script>";
    exit;
}
?>