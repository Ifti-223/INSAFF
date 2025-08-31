<?php
include 'db.php';
if (!isset($_SESSION['user_id']) && !isset($_GET['shared'])) header("Location: login.php");
$fileId = intval($_GET['file_id']);
$stmt = $conn->prepare("SELECT path, filename FROM files WHERE id = ?");
$stmt->bind_param("i", $fileId);
$stmt->execute();
$file = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$file || pathinfo($file['filename'], PATHINFO_EXTENSION) !== 'txt') {
    echo "Invalid file";
    exit;
}
$content = file_get_contents($file['path']);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newContent = $_POST['content'];
    file_put_contents($file['path'], $newContent);
    echo "Saved";
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Edit <?php echo htmlspecialchars($file['filename']); ?></title>
</head>
<body>
<textarea id="editor" style="width:100%; height:500px;"><?php echo htmlspecialchars($content); ?></textarea>
<button onclick="save()">Save</button>
<script>
  let ws = new WebSocket("ws://127.0.0.1:8080");
  const room = <?php echo $fileId; ?>;
  ws.onopen = () => {
    ws.send(JSON.stringify({action: 'join', room: room}));
  };
  ws.onmessage = (e) => {
    const data = JSON.parse(e.data);
    if (data.action === 'update') {
      const editor = document.getElementById('editor');
      if (editor.value !== data.content) {
        editor.value = data.content;
      }
    }
  };
  document.getElementById('editor').addEventListener('input', () => {
    ws.send(JSON.stringify({action: 'update', room: room, content: document.getElementById('editor').value}));
  });
  function save() {
    fetch(window.location.href, {
      method: 'POST',
      headers: {'Content-Type': 'application/x-www-form-urlencoded'},
      body: 'content=' + encodeURIComponent(document.getElementById('editor').value)
    }).then(() => alert('Saved'));
  }
</script>
</body>
</html>