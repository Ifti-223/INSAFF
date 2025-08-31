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

// Log logout action
$stmt = $conn->prepare("SELECT email FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
if ($stmt->execute()) {
    $user = $stmt->get_result()->fetch_assoc();
    logActivity($conn, $userId, 'logout', 'user', $userId, $user['email'] ?? 'Unknown', "User logged out");
}
$stmt->close();

session_destroy();
header("Location: login.php");
exit;
?>