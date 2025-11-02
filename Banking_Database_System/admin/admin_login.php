<?php
// MUST BE THE VERY FIRST LINE
session_start();

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Hardcoded Admin Credentials
    $valid_username = "admin";
    $valid_password = "password"; 

    if ($username === $valid_username && $password === $valid_password) {
        // Success! Set the session variable for security check on the dashboard
        $_SESSION['admin_user'] = $username;
        
        // Redirect to the Admin Dashboard (without any GET parameters needed)
        header("Location: admin_dashboard.php");
        exit();
    } else {
        $message = "<div style='color: red; text-align: center;'>❌ Invalid username or password.</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Login Portal</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f0f4f8; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; }
        .login-container { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2); width: 350px; }
        h2 { color: #dc3545; text-align: center; margin-bottom: 20px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; color: #555; }
        input[type="text"], input[type="password"] { width: 100%; padding: 10px; margin-bottom: 15px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        button { width: 100%; padding: 12px; background-color: #dc3545; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; transition: background-color 0.3s; }
        button:hover { background-color: #c82333; }
        .message { margin-top: 15px; text-align: center; padding: 10px; border-radius: 4px; }
        .portal-link { text-align: center; margin-top: 20px; font-size: 14px; }
    </style>
</head>
<body>

<div class="login-container">
    <h2>🔑 Administrator Login</h2>
    <?php echo $message; ?>
    <form method="POST" action="admin_login.php">
        <label for="username">Username:</label>
        <input type="text" id="username" name="username" required>
        
        <label for="password">Password:</label>
        <input type="password" id="password" name="password" required>
        
        <button type="submit">Log In</button>
    </form>
    
    <div class="portal-link">
        <a href="index.html">← Back to Portal Selection</a>
    </div>
</div>

</body>
</html>