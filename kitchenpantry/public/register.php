<?php
require __DIR__ . '/../src/config.php'; // This file now provides the $pdo variable

$errors = [];

// Prepare variables for the form, even on GET request
$fn = '';
$ln = '';
$e = '';
$u = '';

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $fn = trim($_POST['first_name'] ?? '');
    $ln = trim($_POST['last_name'] ?? '');
    $e = trim($_POST['email'] ?? '');
    $u = trim($_POST['username'] ?? '');
    $p = $_POST['password'] ?? '';

    // Validation
    if(empty($fn)) { $errors[] = 'First name is required.'; }
    if(empty($ln)) { $errors[] = 'Last name is required.'; }
    if(!filter_var($e, FILTER_VALIDATE_EMAIL)) { $errors[] = 'A valid email is required.'; }
    if(strlen($u) < 3) { $errors[] = 'Your Username is too short.'; }
    if(strlen($p) < 6) { $errors[] = 'Password must be 6 or more characters.'; }

    
    if(!$errors){
        try{
            // Check 1: Username
            $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ?');
            $stmt->execute([$u]);
            if($stmt->fetch()) {
                $errors[] = 'Username already taken.';
            }

            // Check 2: Email
            $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
            $stmt->execute([$e]);
            if($stmt->fetch()) {
                $errors[] = 'Email already in use.';
            }

            // Only insert if there are still no errors
            if(!$errors) {
                
                $hash = password_hash($p, PASSWORD_DEFAULT);
                
                $stmt = $pdo->prepare('INSERT INTO users (first_name, last_name, username, email, password) VALUES (?, ?, ?, ?, ?)');
                
                // PDO can execute by passing the array directly
                $stmt->execute([$fn, $ln, $u, $e, $hash]);
                
                // Success! Redirect to login page
                header('Location: login.php');
                exit;
            }

        } catch (PDOException $ex) {
            // This will now catch any SQL errors and display them
            $errors[] = "Database error: " . $ex->getMessage();
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
    <title>Register</title>
</head>
<body>
  
  <?php 
    // This will now show validation errors or db errors
    foreach($errors as $err) {
      echo "<p style='color:red;'>$err</p>"; 
    }
  ?>
  
  <form method="POST">
    <h1>Sign Up</h1>
    
    <label>First Name: <input type="text" name="first_name" value="<?php echo htmlspecialchars($fn); ?>"></label><br>
    <label>Last Name: <input type="text" name="last_name" value="<?php echo htmlspecialchars($ln); ?>"></label><br>
    <label>Email: <input type="email" name="email" value="<?php echo htmlspecialchars($e); ?>"></label><br>
    <label>Username: <input type="text" name="username" value="<?php echo htmlspecialchars($u); ?>"></label><br>
    <label>Password: <input type="password" name="password"></label><br>
    
    <button type="submit">Register</button>
    <p style='text-align:center;'>Already have an account? <a href="index.php">Log in</a></p> <!-- change to login.php once the file is created and index.php is the homepage. -->
  </form>
  
</body>
</html>