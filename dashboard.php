<?php
include 'db.php';
if (!isset($_SESSION['user_id'])) header("Location: login.php");
$userId = $_SESSION['user_id'];
// Handle folder navigation
$currentFolderId = isset($_GET['folder_id']) ? intval($_GET['folder_id']) : NULL;
$breadcrumb = [];
if ($currentFolderId) {
    $stmt = $conn->prepare("SELECT id, folder_name, parent_folder_id FROM folders WHERE id = ? AND user_id = ? AND deleted_at IS NULL");
    $stmt->bind_param("ii", $currentFolderId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $breadcrumb[] = ['id' => $row['id'], 'name' => $row['folder_name']];
        $parentId = $row['parent_folder_id'];
        while ($parentId) {
            $stmt->bind_param("ii", $parentId, $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($parentRow = $result->fetch_assoc()) {
                array_unshift($breadcrumb, ['id' => $parentRow['id'], 'name' => $parentRow['folder_name']]);
                $parentId = $parentRow['parent_folder_id'];
            } else {
                break;
            }
        }
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Your Files</title>
  <link rel="stylesheet" href="dashboard.css">
</head>
<body>
<div class="header">
  <img src="logo2.png" alt="Logo" class="logo" style="width: 200px; height: auto;">
  <div>
    <a href='upload.php?folder_id=<?php echo $currentFolderId ? $currentFolderId : ""; ?>' class="button">Upload File</a>
    <a href='trash.php' class="button">Trash</a>
    <a href="activity_log.php" class="button">Activity Log</a>
    <a href='logout.php' class="button">Logout</a>
  </div>
</div>
<div class="breadcrumb">
  <a href="dashboard.php">Root</a>
  <?php foreach ($breadcrumb as $crumb): ?>
    / <a href="dashboard.php?folder_id=<?php echo $crumb['id']; ?>"><?php echo htmlspecialchars($crumb['name']); ?></a>
  <?php endforeach; ?>
</div>
<h2>Your Files and Folders</h2>
<div class="search-form">
  <form method="GET" action="search.php">
    <input type="text" name="query" placeholder="Search..." required>
    <select name="type">
      <option value="all">All</option>
      <option value="file">Files</option>
      <option value="folder">Folders</option>
    </select>
    <button type="submit">Search</button>
  </form>
</div>
<div class="create-folder">
  <form method="POST" action="create_folder.php">
    <input type="hidden" name="parent_folder_id" value="<?php echo $currentFolderId ? $currentFolderId : ''; ?>">
    <input type="text" name="folder_name" placeholder="New Folder Name" required>
    <button type="submit">Create Folder</button>
  </form>
</div>
<?php if (isset($_SESSION['clipboard'])): ?>
  <div class="paste-button">
    <form method="POST" action="paste.php">
      <input type="hidden" name="destination_folder_id" value="<?php echo $currentFolderId ? $currentFolderId : ''; ?>">
      <button type="submit">Paste</button>
    </form>
  </div>
<?php endif; ?>
<div class="grid-container">
  <?php
  // Fetch folders
  $folderQuery = $currentFolderId
      ? "SELECT * FROM folders WHERE user_id = ? AND parent_folder_id = ? AND deleted_at IS NULL"
      : "SELECT * FROM folders WHERE user_id = ? AND parent_folder_id IS NULL AND deleted_at IS NULL";
  $stmt = $conn->prepare($folderQuery);
  if ($currentFolderId) {
      $stmt->bind_param("ii", $userId, $currentFolderId);
  } else {
      $stmt->bind_param("i", $userId);
  }
  $stmt->execute();
  $folders = $stmt->get_result();
  while ($folder = $folders->fetch_assoc()) {
      $folderId = $folder['id'];
      $folderName = htmlspecialchars($folder['folder_name']);
      echo "
        <div class='folder-card'>
          <div class='folder-icon'>📁</div>
          <span class='folder-name'><a href='dashboard.php?folder_id=$folderId'>$folderName</a></span>
          <button class='dropdown-btn' onclick='toggleDropdown(this)'>Options</button>
          <ul class='dropdown-menu'>
            <li onclick=\"renameItem('folder', $folderId, '$folderName')\">Rename</li>
            <li onclick=\"shareItem('folder', $folderId)\">Share</li>
            <li onclick=\"clipboardItem('folder', $folderId, 'copy')\">Copy</li>
            <li onclick=\"clipboardItem('folder', $folderId, 'cut')\">Cut</li>
            <li onclick=\"deleteFolder($folderId)\">Delete</li>
          </ul>
        </div>
      ";
  }
  $stmt->close();
  // Fetch files
  $fileQuery = $currentFolderId
      ? "SELECT * FROM files WHERE user_id = ? AND folder_id = ? AND deleted_at IS NULL"
      : "SELECT * FROM files WHERE user_id = ? AND folder_id IS NULL AND deleted_at IS NULL";
  $stmt = $conn->prepare($fileQuery);
  if ($currentFolderId) {
      $stmt->bind_param("ii", $userId, $currentFolderId);
  } else {
      $stmt->bind_param("i", $userId);
  }
  $stmt->execute();
  $files = $stmt->get_result();
  while ($row = $files->fetch_assoc()) {
      $fileId = $row['id'];
      $filePath = $row['path'];
      $fileName = htmlspecialchars($row['filename']);
      $isText = pathinfo($fileName, PATHINFO_EXTENSION) === 'txt';
      echo "
        <div class='file-card'>
          <div class='file-icon'>📄</div>
          <span class='filename'>$fileName</span>
          <button class='dropdown-btn' onclick='toggleDropdown(this)'>Options</button>
          <ul class='dropdown-menu'>
            <li onclick=\"window.open('$filePath', '_blank')\">Open</li>
            " . ($isText ? "<li onclick=\"window.location.href='edit.php?file_id=$fileId'\">Edit</li>" : "") . "
            <li onclick=\"renameItem('file', $fileId, '$fileName')\">Rename</li>
            <li onclick=\"shareItem('file', $fileId)\">Share</li>
            <li onclick=\"clipboardItem('file', $fileId, 'copy')\">Copy</li>
            <li onclick=\"clipboardItem('file', $fileId, 'cut')\">Cut</li>
            <li onclick=\"deleteFile($fileId)\">Delete</li>
          </ul>
        </div>
      ";
  }
  $stmt->close();
  ?>
</div>
<!-- Hidden forms -->
<form method="POST" action="delete.php" id="deleteFileForm" style="display:none;">
  <input type="hidden" name="file_id" id="fileToDelete">
</form>
<form method="POST" action="delete.php" id="deleteFolderForm" style="display:none;">
  <input type="hidden" name="folder_id" id="folderToDelete">
</form>
<form method="POST" action="rename.php" id="renameForm" style="display:none;">
  <input type="hidden" name="type" id="renameType">
  <input type="hidden" name="id" id="renameId">
  <input type="hidden" name="folder_id" value="<?php echo $currentFolderId ? $currentFolderId : ''; ?>">
</form>
<form method="POST" action="set_clipboard.php" id="clipboardForm" style="display:none;">
  <input type="hidden" name="type" id="clipboardType">
  <input type="hidden" name="id" id="clipboardId">
  <input type="hidden" name="action" id="clipboardAction">
</form>
<form method="POST" action="share.php" id="shareForm" style="display:none;">
  <input type="hidden" name="type" id="shareType">
  <input type="hidden" name="id" id="shareId">
  <input type="hidden" name="permission" value="view"> <!-- Default -->
  <input type="hidden" name="password" value=""> <!-- Optional -->
</form>
<script>
  function toggleDropdown(btn) {
    document.querySelectorAll('.dropdown-menu').forEach(menu => {
      if (menu !== btn.nextElementSibling) menu.style.display = 'none';
    });
    const menu = btn.nextElementSibling;
    menu.style.display = (menu.style.display === 'block') ? 'none' : 'block';
  }
  function deleteFile(id) {
    if (confirm("Are you sure you want to delete this file?")) {
      document.getElementById('fileToDelete').value = id;
      document.getElementById('deleteFileForm').submit();
    }
  }
  function deleteFolder(id) {
    if (confirm("Are you sure you want to delete this folder and all its contents?")) {
      document.getElementById('folderToDelete').value = id;
      document.getElementById('deleteFolderForm').submit();
    }
  }
  function renameItem(type, id, currentName) {
    const newName = prompt(`Enter new name for ${type}:`, currentName);
    if (newName && newName !== currentName) {
      document.getElementById('renameType').value = type;
      document.getElementById('renameId').value = id;
      const form = document.getElementById('renameForm');
      const input = document.createElement('input');
      input.type = 'hidden';
      input.name = 'new_name';
      input.value = newName;
      form.appendChild(input);
      form.submit();
    }
  }
  function clipboardItem(type, id, action) {
    document.getElementById('clipboardType').value = type;
    document.getElementById('clipboardId').value = id;
    document.getElementById('clipboardAction').value = action;
    document.getElementById('clipboardForm').submit();
  }
  function shareItem(type, id) {
    const permission = prompt('Enter permission (view, edit, upload):', 'view');
    const password = prompt('Enter password (optional):', '');
    if (permission) {
      document.getElementById('shareType').value = type;
      document.getElementById('shareId').value = id;
      document.getElementById('shareForm').querySelector('input[name="permission"]').value = permission;
      document.getElementById('shareForm').querySelector('input[name="password"]').value = password;
      document.getElementById('shareForm').submit();
    }
  }
  document.addEventListener('click', function(e) {
    if (!e.target.classList.contains('dropdown-btn')) {
      document.querySelectorAll('.dropdown-menu').forEach(menu => {
        menu.style.display = 'none';
      });
    }
  });
</script>
</body>
</html>