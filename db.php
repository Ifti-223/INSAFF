<?php
session_start();
$conn = new mysqli("localhost", "root", "", "file_repo");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

function logActivity($conn, $userId, $action, $itemType, $itemId, $itemName, $details = '') {
    $itemName = (string)($itemName ?? 'Unknown');
    $details = (string)($details ?? '');
    $stmt = $conn->prepare("INSERT INTO activity_log (user_id, action, item_type, item_id, item_name, details) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ississ", $userId, $action, $itemType, $itemId, $itemName, $details);
    if (!$stmt->execute()) {
        error_log("Error logging activity: " . $stmt->error);
    }
    $stmt->close();
}
?>