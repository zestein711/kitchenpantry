<?php
// THIS MUST BE THE VERY FIRST LINE
session_start();

// 1. If user is already logged in, send them to the pantry
if (isset($_SESSION['user_id'])) {
    header('Location: index.php'); // Redirect to main app page
    exit;
}

// 2. Include the database config
// (Uses .. to go "up" out of public and into src)
require __DIR__ . '/../src/config.php'; 

$errors = [];
$u = ''; // To store username for pre-filling the form

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 3. Get data from the form
    $u = trim($_POST['username'] ?? '');
    $p = $_POST['password'] ?? '';

    // 4. Simple validation
    if(empty($u)) { $errors[] = 'Username is required.'; }
    if(empty($p)) { $errors[] = 'Password is required.'; }

    if(!$errors) {
        try {
            // 5. Find the user in the database
            $stmt = $pdo->prepare('SELECT * FROM users WHERE username = ?');
            $stmt->execute([$u]);
            $user = $stmt->fetch(); // Get the user's row

            // 6. Check if user exists AND password is correct
            if ($user && password_verify($p, $user['password'])) {
                
                // Password is correct! Start the session.
                session_regenerate_id(true); // Security: Prevents session fixation
                
                // 7. Store user data in the session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['first_name'] = $user['first_name'];
                
                // 8. Redirect to the main application page
                header('Location: index.php');
                exit;
                
            } else {
                // Generic error for security
                $errors[] = 'Invalid username or password.';
            }

        } catch (PDOException $ex) {
            $errors[] = "Database error: " . $ex->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../login.css"> <script src= "https://kit.fontawesome.com/3c108498cb.js" crossorigin="anonymous"></script>
</head>
<body>
  <div class="container" id="login-container">
    
    <form action="login.php" id="loginForm" method="post">
      <h2>Login</h2>
      
      <?php 
        if (!empty($errors)):
            foreach($errors as $err):
      ?>
        <div style="color: red; background: #ffebee; border: 1px solid #d9534f; padding: 10px; border-radius: 5px; margin-bottom: 10px;">
          <?php echo htmlspecialchars($err); ?>
        </div>
      <?php 
            endforeach;
        endif; 
      ?>

      <input type="text" name="username" placeholder="Username" required value="<?php echo htmlspecialchars($u); ?>" />
      <input type="password" name="password" placeholder="Password" required />
      
      <button type="submit">Login</button>
      <p><a href="/kitchenpantry/public/forgotPassword.html">Forgot Password?</a></p>
      <p><a href="/kitchenpantry/public/register.php">Create Account</a></p>
    </form>
  </div>
    <script src="/kitchenpantry/login.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>