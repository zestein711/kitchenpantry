<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();
$path_subfolder = __DIR__ . '/../src/config.php';
$path_root = __DIR__ . '/src/config.php';
if (file_exists($path_subfolder)) {
    require $path_subfolder;
} elseif (file_exists($path_root)) {
    require $path_root;
}

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
$user_id = $_SESSION['user_id'];
$message = "";
$error = "";

// Get User
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $fn = trim($_POST['first_name']);
        $ln = trim($_POST['last_name']);
        $un = trim($_POST['username']);
        $em = trim($_POST['email']);
        $dm = isset($_POST['dark_mode']) ? 1 : 0;

        $sql = "UPDATE users SET first_name=?, last_name=?, username=?, email=?, dark_mode=? WHERE id=?";
        if ($pdo->prepare($sql)->execute([$fn, $ln, $un, $em, $dm, $user_id])) {
            $_SESSION['first_name'] = $fn;
            $user['dark_mode'] = $dm; // Update local for display
            $user['first_name'] = $fn;
            $user['last_name'] = $ln;
            $user['username'] = $un;
            $user['email'] = $em;
            $message = "Profile updated!";
        } else {
            $error = "Update failed.";
        }
    }

    if (isset($_POST['update_password'])) {
        $p1 = $_POST['pass1'];
        $p2 = $_POST['pass2'];
        if ($p1 === $p2 && !empty($p1)) {
            $h = password_hash($p1, PASSWORD_DEFAULT);
            $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([$h, $user_id]);
            $message = "Password changed.";
        } else {
            $error = "Passwords do not match.";
        }
    }
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Settings</title>
    <link rel="stylesheet" href="style/global.css">
</head>

<body class="<?php echo ($user['dark_mode']) ? 'dark-mode' : ''; ?>">
    <header class="app-header">
        <h1>Kitchen Pantry</h1>
        <div class="nav-links"><a href="recipes.php">Back to Browser</a></div>
    </header>

    <div class="card-container">
        <h2>Settings</h2>
        <?php if ($message)
            echo "<p style='color:green'>$message</p>"; ?>
        <?php if ($error)
            echo "<p style='color:red'>$error</p>"; ?>

        <form method="post">
            <h3>Profile</h3>
            <div style="display:flex; gap:15px;">
                <div style="flex:1"><label>First Name</label><input type="text" name="first_name"
                        value="<?php echo htmlspecialchars($user['first_name']); ?>"></div>
                <div style="flex:1"><label>Last Name</label><input type="text" name="last_name"
                        value="<?php echo htmlspecialchars($user['last_name']); ?>"></div>
            </div>
            <label>Username</label><input type="text" name="username"
                value="<?php echo htmlspecialchars($user['username']); ?>">
            <label>Email</label><input type="email" name="email"
                value="<?php echo htmlspecialchars($user['email']); ?>">

            <label style="display:flex; align-items:center; gap:10px; cursor:pointer; margin:15px 0;">
                <input type="checkbox" name="dark_mode" value="1" <?php echo ($user['dark_mode']) ? 'checked' : ''; ?>
                    style="width:auto; margin:0;"> Enable Dark Mode 🌙
            </label>
            <button type="submit" name="update_profile" class="btn-primary">Save Changes</button>
        </form>

        <hr style="margin:2rem 0; border:0; border-top:1px solid var(--border-color);">

        <form method="post">
            <h3>Change Password</h3>
            <input type="password" name="pass1" placeholder="New Password">
            <input type="password" name="pass2" placeholder="Confirm Password">
            <button type="submit" name="update_password" class="btn-primary"
                style="background:var(--accent-color);">Update Password</button>
        </form>
    </div>
</body>

</html>