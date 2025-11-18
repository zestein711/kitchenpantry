<?php

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
  
  <?php foreach($errors as $e) echo "<p style='color:red;'>$e</p>"; ?>
  
  <form method="POST">
    <h1>Sign Up</h1>
    
    <label>First Name: <input type="text" name="first_name"></label><br>
    <label>Last Name: <input type="text" name="last_name"></label><br>
    <label>Email: <input type="email" name="email"></label><br>
    
    <label>Username: <input type="text" name="username"></label><br>
    <label>Password: <input type="password" name="password"></label><br>
    
    <button type="submit">Register</button>
    <p style='text-align:center;'>Already have an account? <a href="login.php">Log in</a></p>
  </form>
  
</body>
</html>