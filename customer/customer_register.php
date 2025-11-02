<?php
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
    $cust_id = $_POST['cust_id'];
    $username = $_POST['username'];
    $password = $_POST['password'];

    // 1. Check if the Customer ID exists in the main CUSTOMER table
    $stmt_check_cust = $conn->prepare("SELECT first_name FROM CUSTOMER WHERE cust_id = ?");
    $stmt_check_cust->bind_param("i", $cust_id);
    $stmt_check_cust->execute();
    $result_cust = $stmt_check_cust->get_result();
    
    if ($result_cust->num_rows == 0) {
        $message = "<div style='color: red;'>❌ Registration failed: Invalid Customer ID. Please check your Customer ID.</div>";
    } else {
        $row_cust = $result_cust->fetch_assoc();
        $first_name = $row_cust['first_name'];
        
        // 2. Hash the password for secure storage
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // 3. Insert into CUST_LOGIN table
        $stmt_register = $conn->prepare("INSERT INTO CUST_LOGIN (cust_id, username, password_hash) VALUES (?, ?, ?)");
        $stmt_register->bind_param("iss", $cust_id, $username, $hashed_password);

        try {
            if ($stmt_register->execute()) {
                $message = "<div style='color: green;'>✅ **Registration successful!** Welcome, {$first_name}. You can now log in. <a href='customer_login.php'>Go to Login</a></div>";
            } else {
                $message = "<div style='color: red;'>❌ Registration failed: Username or Customer ID already linked.</div>";
            }
        } catch (mysqli_sql_exception $e) {
             // Catch specific error for duplicate key (username or cust_id)
            $message = "<div style='color: red;'>❌ Registration failed: Username is already taken or your Customer ID is already registered.</div>";
        }
        $stmt_register->close();
    }
    $stmt_check_cust->close();
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Customer Registration</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #e6f7ff; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; }
        .register-container { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2); width: 350px; }
        h2 { color: #007bff; text-align: center; margin-bottom: 20px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; color: #555; }
        input[type="text"], input[type="password"] { width: 100%; padding: 10px; margin-bottom: 15px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        button { width: 100%; padding: 12px; background-color: #28a745; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; transition: background-color 0.3s; }
        button:hover { background-color: #218838; }
        .message { margin-top: 15px; text-align: center; padding: 10px; border-radius: 4px; }
        .link-area { text-align: center; margin-top: 20px; font-size: 14px; } /* Combined links area */
        .link-area a { margin: 0 10px; }
    </style>
</head>
<body>

<div class="register-container">
    <h2>🏦 Customer Registration</h2>
    <?php echo $message; ?>
    <form method="POST" action="customer_register.php">
        <label for="cust_id">Customer ID:</label>
        <input type="text" id="cust_id" name="cust_id" required>
        
        <label for="username">Choose Username:</label>
        <input type="text" id="username" name="username" required>
        
        <label for="password">Choose Password:</label>
        <input type="password" id="password" name="password" required>
        
        <button type="submit">Register Account</button>
    </form>
    
    <div class="link-area">
        <a href="index.html">← Back to Portal</a> | 
        Already have login credentials? <a href="customer_login.php">Log In Here</a>
    </div>
</div>

</body>
</html>