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
$mode = $_POST['mode'] ?? 'username';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    /* -----------------------------------------------------------
       MODE: USERNAME
    ----------------------------------------------------------- */
    if ($mode === 'username') {

        $username = trim($_POST['username'] ?? '');

        if (strlen($username) < 3) {
            $errors[] = "Please enter a valid username.";
        } else {

            $stmt = $pdo->prepare("SELECT id, email FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if (!$user) {
                $errors[] = "No account found with that username.";
            } else {

                // Generate reset token
                $token = bin2hex(random_bytes(32));
                $expires = date("Y-m-d H:i:s", time() + 3600);

                $stmt = $pdo->prepare("
                    INSERT INTO password_resets (user_id, token, expires_at)
                    VALUES (?, ?, ?)
                ");
                $stmt->execute([$user['id'], $token, $expires]);

                // Build reset link
                $resetLink = "https://localhost/kitchenpantry/public/reset_password.php?token={$token}";

                $subject = "Password Reset Request (Forward to User)";
                $message = "A password reset was requested.\n\n"
                    . "User Email: {$user['email']}\n"
                    . "Username: {$username}\n\n"
                    . "Reset Link (send this to the user):\n{$resetLink}\n\n"
                    . "Requested at: " . date("Y-m-d H:i:s");

                // Send email
                $mail = new PHPMailer(true);

                try {
                    $mail->isSMTP();
                    $mail->Host = 'smtp.gmail.com';
                    $mail->SMTPAuth = true;
                    $mail->Username = SMTP_USER;
                    $mail->Password = SMTP_PASS;
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port = 587;

                    $mail->setFrom(SMTP_USER, 'Kitchen Pantry System');
                    $mail->addAddress("zojosb@gmail.com");
                    $mail->isHTML(false);
                    $mail->Subject = $subject;
                    $mail->Body = $message;
                    $mail->send();

                    $success = "A reset request has been logged. The admin will forward the reset link.";

                } catch (Exception $e) {
                    $errors[] = "Mailer Error: " . $mail->ErrorInfo;
                }
            }
        }
    }

    /* -----------------------------------------------------------
       MODE: EMAIL
    ----------------------------------------------------------- */
    if ($mode === 'email') {

        $email = trim($_POST['email'] ?? '');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Please enter a valid email.";
        } else {

            $stmt = $pdo->prepare("SELECT id, username, email FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if (!$user) {
                $errors[] = "No account found with that email.";
            } else {

                // Create token
                $token = bin2hex(random_bytes(32));
                $expires = date("Y-m-d H:i:s", time() + 3600);

                $stmt = $pdo->prepare("
                    INSERT INTO password_resets (user_id, token, expires_at)
                    VALUES (?, ?, ?)
                ");
                $stmt->execute([$user['id'], $token, $expires]);

                // Reset link
                $resetLink = "https://localhost/kitchenpantry/public/reset_password.php?token={$token}";

                $subject = "Password Reset Request (Forward to User)";
                $message = "A password reset was requested.\n\n"
                    . "User Email: {$user['email']}\n"
                    . "Username: {$user['username']}\n\n"
                    . "Reset Link (send this to the user):\n{$resetLink}\n\n"
                    . "Requested at: " . date("Y-m-d H:i:s");

                // Email admin
                $mail = new PHPMailer(true);

                try {
                    $mail->isSMTP();
                    $mail->Host = 'smtp.gmail.com';
                    $mail->SMTPAuth = true;
                    $mail->Username = SMTP_USER;
                    $mail->Password = SMTP_PASS;
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port = 587;

                    $mail->setFrom(SMTP_USER, 'Kitchen Pantry System');
                    $mail->addAddress("zojosb@gmail.com");
                    $mail->isHTML(false);
                    $mail->Subject = $subject;
                    $mail->Body = $message;
                    $mail->send();

                    $success = "A reset request has been logged. The admin will forward the reset link.";

                } catch (Exception $e) {
                    $errors[] = "Mailer Error: " . $mail->ErrorInfo;
                }
            }
        }
    }
}

?>

<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="style/global.css">
    <link rel="stylesheet" href="style/forms.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <title>Forgot Password</title>
</head>
<body>

<?php
foreach ($errors as $err) {
    echo "<p style='color:red;'>" . htmlspecialchars($err) . "</p>";
}
if ($success) {
    echo "<p style='color:green;'>" . htmlspecialchars($success) . "</p>";
}
?>

<form method="POST">
    <h1>Forgot Password</h1>

    <?php if ($mode === 'username'): ?>
        <label>Username:
            <input type="text" name="username" value="<?php echo htmlspecialchars($username); ?>">
        </label><br>

        <input type="hidden" name="mode" value="username">
        <button type="submit">Send Reset Link</button>

        <p style="text-align:center;">
            Don't know your username?
            <button type="submit" name="mode" value="email" style="border:none;background:none;color:blue;cursor:pointer;">
                Use Email Instead
            </button>
        </p>

    <?php else: ?>
        <label>Email:
            <input type="email" name="email" value="<?php echo htmlspecialchars($email); ?>">
        </label><br>

        <input type="hidden" name="mode" value="email">
        <button type="submit">Send Reset Link</button>

        <p style="text-align:center;">
            Remember your username?
            <button type="submit" name="mode" value="username" style="border:none;background:none;color:blue;cursor:pointer;">
                Use Username Instead
            </button>
        </p>
    <?php endif; ?>

    <p style="text-align:center;"><a href="index.php">Back to Login</a></p>
</form>

</body>
</html>

