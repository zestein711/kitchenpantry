<?php
session_start();
$path_subfolder = __DIR__ . '/../src/config.php';
$path_root = __DIR__ . '/src/config.php';
if (file_exists($path_subfolder)) {
  require $path_subfolder;
} elseif (file_exists($path_root)) {
  require $path_root;
}
if (isset($_SESSION['user_id'])) {
  header('Location: index.php');
  exit;
}

$error = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $u = trim($_POST['username']);
  $p = $_POST['password'];
  $stmt = $pdo->prepare('SELECT * FROM users WHERE username = ?');
  $stmt->execute([$u]);
  $user = $stmt->fetch();

  if ($user && password_verify($p, $user['password'])) {
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['first_name'] = $user['first_name'];
    header('Location: index.php');
    exit;
  } else {
    $error = "Invalid username or password.";
  }
}
?>
<!DOCTYPE html>
<html>

<head>
  <title>Login</title>
  <link rel="stylesheet" href="style/global.css">
</head>

<body style="display:flex; align-items:center; justify-content:center; height:100vh;">
  <div class="card-container" style="width:100%; max-width:400px; margin:0;">
    <h2 style="text-align:center">Kitchen Pantry</h2>
    <?php if ($error)
      echo "<div class='error-msg'>$error</div>"; ?>
    <form method="post">
      <input type="text" name="username" placeholder="Username" required>
      <input type="password" name="password" placeholder="Password" required>
      <button type="submit" class="btn-primary">Log In</button>
    </form>
    <div style="text-align:center; margin-top:15px; font-size:0.9rem;">
      <p><a href="forgot_password.php">Forgot Password?</a></p>
      <p>New here? <a href="register.php">Create an Account</a></p>
    </div>
  </div>
</body>

</html>