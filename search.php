<?php
include 'db.php';
if (!isset($_SESSION['user_id'])) header("Location: login.php");
$userId = $_SESSION['user_id'];
$query = isset($_GET['query']) ? $_GET['query'] : '';
$type = isset($_GET['type']) ? $_GET['type'] : 'all';
// Fetch matching items
$results = [];
if ($type === 'all' || $type === 'folder') {
    $stmt = $conn->prepare("SELECT id, folder_name AS name, 'folder' AS type FROM folders WHERE user_id = ? AND folder_name LIKE ? AND deleted_at IS NULL");
    $like = "%$query%";
    $stmt->bind_param("is", $userId, $like);
    $stmt->execute();
    $folders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $results = array_merge($results, $folders);
    $stmt->close();
}
if ($type === 'all' || $type === 'file') {
    $stmt = $conn->prepare("SELECT id, filename AS name, 'file' AS type FROM files WHERE user_id = ? AND filename LIKE ? AND deleted_at IS NULL");
    $like = "%$query%";
    $stmt->bind_param("is", $userId, $like);
    $stmt->execute();
    $files = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $results = array_merge($results, $files);
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Search Results</title>
  <link rel="stylesheet" href="dashboard.css">
</head>
<body>
<div class="header">
  <img src="logo2.png" alt="Logo" class="logo" style="width: 200px; height: auto;">
  <div>
    <a href='dashboard.php' class="button">Dashboard</a>
    <a href='logout.php' class="button">Logout</a>
  </div>
</div>
<h2>Search Results for "<?php echo htmlspecialchars($query); ?>"</h2>
<div class="grid-container">
  <?php foreach ($results as $item): ?>
    <?php if ($item['type'] === 'folder'): ?>
      <div class='folder-card'>
        <div class='folder-icon'>📁</div>
        <span class='folder-name'><a href='dashboard.php?folder_id=<?php echo $item['id']; ?>'><?php echo htmlspecialchars($item['name']); ?></a></span>
      </div>
    <?php else: ?>
      <div class='file-card'>
        <div class='file-icon'>📄</div>
        <span class='filename'><?php echo htmlspecialchars($item['name']); ?></span>
      </div>
    <?php endif; ?>
  <?php endforeach; ?>
</div>
</body>
</html>