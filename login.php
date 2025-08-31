<?php
include 'db.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = $_POST['email'];
  $pass = $_POST['password'];
  $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
  $stmt->bind_param("s", $email);
  $stmt->execute();
  $res = $stmt->get_result();
  if ($row = $res->fetch_assoc()) {
    if (password_verify($pass, $row['password'])) {
      $_SESSION['user_id'] = $row['id'];
      logActivity($conn, $row['id'], 'login', 'user', $row['id'], $row['email'], 'User logged in');
      header("Location: dashboard.php");
      exit;
    }
  }
  $stmt->close();
  echo "<script>alert('Login failed! Incorrect email or password.');</script>";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Login</title>
  <link rel="stylesheet" href="auth.css">
</head>
<body>
<div class="auth-container">
  <img src="logo1.png" alt="Logo" class="logo">
  <div class="auth-box">
    <h2>Login</h2>
    <form method="POST">
      <input type="email" name="email" placeholder="Email" required><br>
      <input type="password" name="password" placeholder="Password" required><br>
      <button type="submit">Login</button>
    </form>
    <p class="switch">New here? <a href="register.php">Create an account</a></p>
  </div>
</div>
</body>
</html>