<?php
include 'db.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = $_POST['email'];
  $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
  $stmt->bind_param("s", $email);
  $stmt->execute();
  if ($stmt->get_result()->num_rows > 0) {
    echo "<script>alert('Email already registered!'); window.location.href='register.php';</script>";
    exit;
  }
  $stmt->close();
  $pass = password_hash($_POST['password'], PASSWORD_DEFAULT);
  $stmt = $conn->prepare("INSERT INTO users (email, password) VALUES (?, ?)");
  $stmt->bind_param("ss", $email, $pass);
  $stmt->execute();
  $userId = $conn->insert_id;
  logActivity($conn, $userId, 'registration', 'user', $userId, $email, 'User registered');
  $stmt->close();
  echo "<script>alert('Registration successful! Please log in.'); window.location.href='login.php';</script>";
  exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Register</title>
  <link rel="stylesheet" href="auth.css">
</head>
<body>
<div class="auth-container">
  <img src="logo1.png" alt="Logo" class="logo">
  <div class="auth-box">
    <h2>Create Account</h2>
    <form method="POST">
      <input type="email" name="email" placeholder="Email" required style="width: 90%;"><br>
      <input type="password" name="password" placeholder="Password" required style="width: 90%;"><br>
      <button type="submit">Register</button>
    </form>
    <p class="switch">Already a member? <a href="login.php">Login here</a></p>
  </div>
</div>
</body>
</html>