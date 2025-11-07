<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../../frontend/css/login.css">
    <script src= "https://kit.fontawesome.com/3c108498cb.js" crossorigin="anonymous"></script>
</head>
<body>
  <div class="container" id="login-container">

    <form id="loginForm">
        <h2>Login</h2>
          <input type="text" placeholder="Username" required />
          <input type="password" placeholder="Password" required />
            <button type="submit">Login</button>
          <p><a href="../../backend/php/forgotPassword.php">Forgot Password?</a></p>
          <p><a href="../../backend/php/createAccount.php">Create Account</a></p>
    </form>
      
  </div>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../frontend/js/login.js"></script>
</body>
</html>
