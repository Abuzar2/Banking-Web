<?php
session_start();
$servername = "localhost";
$username = "root";   
$password = ""; 
$dbname = "banking_sys";
$port = 3307;

$conn = new mysqli($servername, $username, $password, $dbname, $port);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username_input = $_POST['username'];
    $password_input = $_POST['password'];

    // 1. Fetch user data and hashed password
    $sql = "
        SELECT 
            CL.password_hash, 
            C.cust_id, 
            C.first_name 
        FROM 
            CUST_LOGIN CL
        JOIN 
            CUSTOMER C ON CL.cust_id = C.cust_id
        WHERE 
            CL.username = ?
    ";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username_input);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();
        $hashed_password = $row['password_hash'];

        // 2. Verify the password
        if (password_verify($password_input, $hashed_password)) {
            // Success! Set session variables and redirect
            $_SESSION['cust_id'] = $row['cust_id'];
            $_SESSION['cust_name'] = $row['first_name'];
            
            header("Location: customer_dashboard.php");
            exit();
        } else {
            $message = "<div style='color: red;'>❌ Login failed: Invalid password.</div>";
        }
    } else {
        $message = "<div style='color: red;'>❌ Login failed: Username not found.</div>";
    }
    $stmt->close();
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Customer Login</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #e6f7ff; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; }
        .login-container { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2); width: 350px; }
        h2 { color: #007bff; text-align: center; margin-bottom: 20px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; color: #555; }
        input[type="text"], input[type="password"] { width: 100%; padding: 10px; margin-bottom: 15px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        button { width: 100%; padding: 12px; background-color: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; transition: background-color 0.3s; }
        button:hover { background-color: #0056b3; }
        .message { margin-top: 15px; text-align: center; padding: 10px; border-radius: 4px; }
        .link-area { text-align: center; margin-top: 20px; font-size: 14px; } /* Combined links area */
        .link-area a { margin: 0 10px; }
    </style>
</head>
<body>

<div class="login-container">
    <h2>🔑 Customer Login</h2>
    <?php echo $message; ?>
    <form method="POST" action="customer_login.php">
        <label for="username">Username:</label>
        <input type="text" id="username" name="username" required>
        
        <label for="password">Password:</label>
        <input type="password" id="password" name="password" required>
        
        <button type="submit">Log In</button>
    </form>
    
    <div class="link-area">
        <a href="index.html">← Back to Portal</a> | 
        Don't have an account? <a href="customer_register.php">Register Here</a>
    </div>
</div>

</body>
</html>