<?php
// public/forgot_password.php

require __DIR__ . '/../src/config.php';

// PHPMailer includes (PHPMailer is in public/phpmailer/)
require __DIR__ . '/phpmailer/src/Exception.php';
require __DIR__ . '/phpmailer/src/PHPMailer.php';
require __DIR__ . '/phpmailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Ensure password_resets table exists
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS password_resets (
            id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT(11) UNSIGNED NOT NULL,
            token VARCHAR(64) NOT NULL,
            expires_at DATETIME NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_user_passwordreset
                FOREIGN KEY (user_id)
                REFERENCES users(id)
                ON DELETE CASCADE
                ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
} catch (PDOException $e) {
    die('ERROR creating table: ' . $e->getMessage());
}

$errors = [];
$success = "";
$username = "";
$email = "";

// Determine mode (username or email)
// We check $_REQUEST to handle both GET (from link) and POST (from form)
$mode = $_REQUEST['mode'] ?? 'username';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    /* -----------------------------------------------------------
       MODE: USERNAME
    ----------------------------------------------------------- */
    if ($mode === 'username') {
        $username = trim($_POST['username'] ?? '');

        if (empty($username)) {
            $errors[] = "Please enter your username.";
        } else {
            // Find user by username
            $stmt = $pdo->prepare("SELECT id, email FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user && !empty($user['email'])) {
                // Send reset link to $user['email']
                $token = bin2hex(random_bytes(32));
                $expires_at = date("Y-m-d H:i:s", strtotime('+1 hour'));

                $stmt = $pdo->prepare("INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)");
                $stmt->execute([$user['id'], $token, $expires_at]);

                // Adjust this URL to match your actual server path!
                $resetLink = "http://localhost/kitchenpantry/public/reset_password.php?token=" . $token;

                // Send Email via PHPMailer
                $mail = new PHPMailer(true);
                try {
                    // Server settings
                    $mail->isSMTP();
                    $mail->Host = 'smtp.gmail.com';
                    $mail->SMTPAuth = true;
                    $mail->Username = 'kitchenpantryapp@gmail.com';
                    $mail->Password = 'dhoc uqdf ojhq bnaq';
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port = 587;

                    // Recipients
                    $mail->setFrom('kitchenpantryapp@gmail.com', 'Kitchen Pantry');
                    $mail->addAddress($user['email']);

                    // Content
                    $mail->isHTML(true);
                    $mail->Subject = 'Password Reset Request';
                    $mail->Body = "Click this link to reset your password: <a href='$resetLink'>$resetLink</a>";

                    $mail->send();
                    $success = "Reset link sent to the email associated with username: " . htmlspecialchars($username);
                } catch (Exception $e) {
                    $errors[] = "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
                }
            } else {
                $errors[] = "Username not found or no email associated.";
            }
        }
    }

    /* -----------------------------------------------------------
       MODE: EMAIL
    ----------------------------------------------------------- */ elseif ($mode === 'email') {
        $email = trim($_POST['email'] ?? '');

        if (empty($email)) {
            $errors[] = "Please enter your email.";
        } else {
            // Find user by email
            $stmt = $pdo->prepare("SELECT id, email FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user) {
                // Send reset link
                $token = bin2hex(random_bytes(32));
                $expires_at = date("Y-m-d H:i:s", strtotime('+1 hour'));

                $stmt = $pdo->prepare("INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)");
                $stmt->execute([$user['id'], $token, $expires_at]);

                $resetLink = "http://localhost/kitchenpantry/public/reset_password.php?token=" . $token;

                // Send Email via PHPMailer
                $mail = new PHPMailer(true);
                try {
                    // Server settings
                    $mail->isSMTP();
                    $mail->Host = 'smtp.gmail.com';
                    $mail->SMTPAuth = true;
                    $mail->Username = 'kitchenpantryapp@gmail.com';
                    $mail->Password = 'dhoc uqdf ojhq bnaq';
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port = 587;

                    // Recipients
                    $mail->setFrom('kitchenpantryapp@gmail.com', 'Kitchen Pantry');
                    $mail->addAddress($email);

                    // Content
                    $mail->isHTML(true);
                    $mail->Subject = 'Password Reset Request';
                    $mail->Body = "Click this link to reset your password: <a href='$resetLink'>$resetLink</a>";

                    $mail->send();
                    $success = "Reset link sent to: " . htmlspecialchars($email);
                } catch (Exception $e) {
                    $errors[] = "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
                }
            } else {
                $errors[] = "No account found with that email.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Forgot Password</title>
    <link rel="stylesheet" href="style/global.css">
</head>

<body style="display:flex; align-items:center; justify-content:center; height:100vh;">

    <div class="card-container" style="width:100%; max-width:400px; margin:0;">
        <h2 style="text-align:center;">Forgot Password</h2>

        <?php
        foreach ($errors as $err) {
            echo "<p class='error-msg'>" . htmlspecialchars($err) . "</p>";
        }
        if ($success) {
            echo "<p style='color:green; background:rgba(40, 167, 69, 0.1); padding:10px; border-radius:5px;'>" . htmlspecialchars($success) . "</p>";
        }
        ?>

        <form method="POST">
            <?php if ($mode === 'username'): ?>
                <label style="font-weight:bold; margin-bottom:5px; display:block;">Username</label>
                <input type="text" name="username" placeholder="Enter Username"
                    value="<?php echo htmlspecialchars($username); ?>">

                <input type="hidden" name="mode" value="username">
                <button type="submit" class="btn-primary">Send Reset Link</button>

                <div style="text-align:center; margin-top:20px; font-size:0.9rem;">
                    <span style="color:var(--text-muted);">Don't know your username?</span><br>
                    <a href="?mode=email" style="color:var(--accent-color); text-decoration:underline;">
                        Use Email Instead
                    </a>
                </div>

            <?php else: ?>
                <label style="font-weight:bold; margin-bottom:5px; display:block;">Email</label>
                <input type="email" name="email" placeholder="Enter Email Address"
                    value="<?php echo htmlspecialchars($email); ?>">

                <input type="hidden" name="mode" value="email">
                <button type="submit" class="btn-primary">Send Reset Link</button>

                <div style="text-align:center; margin-top:20px; font-size:0.9rem;">
                    <span style="color:var(--text-muted);">Remember your username?</span><br>
                    <a href="?mode=username" style="color:var(--accent-color); text-decoration:underline;">
                        Use Username Instead
                    </a>
                </div>
            <?php endif; ?>
        </form>

        <p style="text-align:center; margin-top:15px; border-top:1px solid var(--border-color); padding-top:15px;">
            <a href="login.php" style="font-weight:bold;">Back to Login</a>
        </p>
    </div>

</body>

</html>