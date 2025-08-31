<?php
include 'db.php';
if (!isset($_SESSION['user_id'])) header("Location: login.php");
$userId = $_SESSION['user_id'];
$currentFolderId = isset($_GET['folder_id']) ? intval($_GET['folder_id']) : NULL;
$message = "";
function getFolders($conn, $userId, $parentId = NULL, $prefix = '') {
  $folders = [];
  $query = $parentId ? "SELECT id, folder_name FROM folders WHERE user_id = ? AND parent_folder_id = ? AND deleted_at IS NULL" : "SELECT id, folder_name FROM folders WHERE user_id = ? AND parent_folder_id IS NULL AND deleted_at IS NULL";
  $stmt = $conn->prepare($query);
  if ($parentId) {
    $stmt->bind_param("ii", $userId, $parentId);
  } else {
    $stmt->bind_param("i", $userId);
  }
  $stmt->execute();
  $result = $stmt->get_result();
  while ($folder = $result->fetch_assoc()) {
    $folders[] = ['id' => $folder['id'], 'name' => $prefix . $folder['folder_name']];
    $subfolders = getFolders($conn, $userId, $folder['id'], $prefix . '─ ');
    $folders = array_merge($folders, $subfolders);
  }
  $stmt->close();
  return $folders;
}
$allFolders = getFolders($conn, $userId);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['filename'];
    $folderId = !empty($_POST['folder_id']) ? intval($_POST['folder_id']) : NULL;
    $file = $_FILES['file'];
    // Check if file name exists
    $query = $folderId
        ? "SELECT id, path FROM files WHERE user_id = ? AND filename = ? AND folder_id = ? AND deleted_at IS NULL"
        : "SELECT id, path FROM files WHERE user_id = ? AND filename = ? AND folder_id IS NULL AND deleted_at IS NULL";
    $stmt = $conn->prepare($query);
    if ($folderId) {
        $stmt->bind_param("isi", $userId, $name, $folderId);
    } else {
        $stmt->bind_param("is", $userId, $name);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $existing = $result->fetch_assoc();
    $stmt->close();
    if ($file['error'] === 0) {
        $target = "Uploads/" . time() . "_" . basename($file['name']); // Unique path
        move_uploaded_file($file['tmp_name'], $target);
        if ($existing) {
            // Add old to versions
            $fileId = $existing['id'];
            $oldPath = $existing['path'];
            $stmt = $conn->prepare("SELECT COALESCE(MAX(version), 0) + 1 AS new_version FROM versions WHERE file_id = ?");
            $stmt->bind_param("i", $fileId);
            $stmt->execute();
            $version = $stmt->get_result()->fetch_assoc()['new_version'];
            $stmt->close();
            $stmt = $conn->prepare("INSERT INTO versions (file_id, version, path) VALUES (?, ?, ?)");
            $stmt->bind_param("iis", $fileId, $version, $oldPath);
            $stmt->execute();
            logActivity($conn, $userId, 'upload', 'file', $fileId, $filename, 'Uploaded to folder_id=' . ($folderId ?? 'NULL'));
            $stmt->close();
            // Update current
            $stmt = $conn->prepare("UPDATE files SET path = ? WHERE id = ?");
            $stmt->bind_param("si", $target, $fileId);
            $stmt->execute();
            $stmt->close();
            $message = "✅ File updated with new version!";
        } else {
            // New file
            $query = $folderId
                ? "INSERT INTO files (user_id, folder_id, filename, path) VALUES (?, ?, ?, ?)"
                : "INSERT INTO files (user_id, filename, path) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($query);
            if ($folderId) {
                $stmt->bind_param("iiss", $userId, $folderId, $name, $target);
            } else {
                $stmt->bind_param("iss", $userId, $name, $target);
            }
            $stmt->execute();
            $fileId = $conn->insert_id;
            $filename = basename($_FILES['file']['name']);
            logActivity($conn, $userId, 'upload', 'file', $fileId, $filename, 'Uploaded into the system');
            $stmt->close();
            $message = "✅ File uploaded successfully!";
        }
    } else {
        $message = "❌ File upload failed!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Upload File</title>
  <link rel="stylesheet" href="upload.css">
</head>
<body>
<h2 style="color: white; text-align: center">Upload a New File</h2>
<?php if ($message): ?>
  <div class="message"><?= $message ?></div>
<?php endif; ?>
<form method="POST" enctype="multipart/form-data" style="background: white; padding: 20px; border-radius: 6px; box-shadow: 0 0 0px rgba(0,0,0,0.1); text-align: center; justify-content: center; max-width: 400px; margin: auto;">
  <label for="filename">File Name:</label>
  <input type="text" name="filename" id="filename" required><br>
 
  <label for="folder_id">Select Folder:</label>
  <select name="folder_id" id="folder_id">
    <option value="">Root</option>
    <?php foreach ($allFolders as $folder): ?>
      <?php $selected = ($folder['id'] == $currentFolderId) ? 'selected' : ''; ?>
      <option value='<?php echo $folder['id']; ?>' <?php echo $selected; ?>><?php echo htmlspecialchars($folder['name']); ?></option>
    <?php endforeach; ?>
  </select><br>
 
  <label for="file">Choose File:</label>
  <input type="file" name="file" id="file" required><br>
 
  <button type="submit">Upload</button>
</form>
<div class="dashboard-btn-container">
  <a href="dashboard.php<?php echo $currentFolderId ? '?folder_id=' . $currentFolderId : ''; ?>">
    <button class="dashboard-btn">← Go to Dashboard</button>
  </a>
</div>
</body>
</html>