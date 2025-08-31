<?php
include 'db.php';
if (!isset($_SESSION['user_id'])) header("Location: login.php");
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = $_POST['type'];
    $id = intval($_POST['id']);
    $action = $_POST['action'];
    $_SESSION['clipboard'] = ['type' => $type, 'id' => $id, 'action' => $action];
}
header("Location: dashboard.php");
exit;
?>