<?php
require __DIR__ . '/../src/config.php';

$errors = [];
$success = "";
$token = $_GET['token'] ?? "";

// 1. Verify token exists
if (!$token) {
    die("Invalid reset link.");
}

// 2. Look up token in database
$stmt = $pdo->prepare("
    SELECT pr.user_id, pr.expires_at, u.username 
    FROM password_resets pr
    JOIN users u ON pr.user_id = u.id
    WHERE pr.token = ?
    LIMIT 1
");
$stmt->execute([$token]);
$reset = $stmt->fetch();

if (!$reset) {
    die("Invalid or expired reset link.");
}

// 3. Check expiration
if (strtotime($reset['expires_at']) < time()) {
    die("This reset link has expired.");
}

// If user submits new password
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? "";
    $confirm = $_POST['confirm'] ?? "";

    if (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters.";
    }
    if ($password !== $confirm) {
        $errors[] = "Passwords do not match.";
    }

    if (empty($errors)) {
        // 4. Hash the password
        $hashed = password_hash($password, PASSWORD_DEFAULT);

        // 5. Update user password
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([$hashed, $reset['user_id']]);

        // 6. Delete token so it can't be used twice
        $pdo->prepare("DELETE FROM password_resets WHERE token = ?")
            ->execute([$token]);

        $success = "Your password has been reset successfully! <br><a href='login.php'>Login here</a>";
    }
}

?>

<!DOCTYPE html>
<html>

<head>
    <title>Reset Password</title>
    <link rel="stylesheet" href="style/global.css">
</head>

<body style="display:flex; align-items:center; justify-content:center; height:100vh;">

    <div class="card-container" style="width:100%; max-width:400px; margin:0;">
        <h2 style="text-align:center;">Reset Password</h2>

        <?php
        foreach ($errors as $err) {
            echo "<p class='error-msg'>$err</p>";
        }

        if ($success) {
            echo "<p style='color:green; background:rgba(40, 167, 69, 0.1); padding:10px; border-radius:5px; text-align:center;'>$success</p>";
        } else {
            ?>
            <form method="POST">
                <label style="font-weight:bold; margin-bottom:5px; display:block;">New Password:</label>
                <input type="password" name="password" required>

                <label style="font-weight:bold; margin-bottom:5px; display:block;">Confirm Password:</label>
                <input type="password" name="confirm" required>

                <button type="submit" class="btn-primary" style="margin-top:10px;">Reset Password</button>
            </form>
        <?php } ?>
    </div>

</body>

</html>