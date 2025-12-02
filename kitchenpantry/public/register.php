<?php
$path_subfolder = __DIR__ . '/../src/config.php';
$path_root = __DIR__ . '/src/config.php';
if (file_exists($path_subfolder)) {
  require $path_subfolder;
} elseif (file_exists($path_root)) {
  require $path_root;
}

$error = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $fn = trim($_POST['first_name']);
  $ln = trim($_POST['last_name']);
  $u = trim($_POST['username']);
  $e = trim($_POST['email']);
  $p = $_POST['password'];

  $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
  $stmt->execute([$u, $e]);
  if ($stmt->fetch()) {
    $error = "Username or Email already taken.";
  } else {
    $hash = password_hash($p, PASSWORD_DEFAULT);
    $sql = "INSERT INTO users (first_name, last_name, username, email, password) VALUES (?, ?, ?, ?, ?)";
    $pdo->prepare($sql)->execute([$fn, $ln, $u, $e, $hash]);
    header('Location: login.php');
    exit;
  }
}
?>
<!DOCTYPE html>
<html>

<head>
  <title>Register</title>
  <link rel="stylesheet" href="style/global.css">
</head>

<body style="display:flex; align-items:center; justify-content:center; height:100vh;">
  <div class="card-container" style="width:100%; max-width:400px; margin:0;">
    <h2 style="text-align:center">Sign Up</h2>
    <?php if ($error)
      echo "<div class='error-msg'>$error</div>"; ?>
    <form method="post">
      <input type="text" name="first_name" placeholder="First Name" required>
      <input type="text" name="last_name" placeholder="Last Name" required>
      <input type="text" name="username" placeholder="Username" required>
      <input type="email" name="email" placeholder="Email" required>
      <input type="password" name="password" placeholder="Password" required>
      <button type="submit" class="btn-primary">Register</button>
    </form>
    <p style="text-align:center; margin-top:15px;">
      <a href="login.php">Back to Login</a>
    </p>
  </div>
</body>

</html>