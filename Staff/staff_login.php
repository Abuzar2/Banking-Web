<?php
session_start();

// Database Connection Details
$servername = "localhost";
$username = "root";   
$password = ""; 
$dbname = "banking_sys";
$port = 3307;

$conn = new mysqli($servername, $username, $password, $dbname, $port);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$message = '';
$message_type = '';

// Check if the staff member is already logged in
if (isset($_SESSION['staff_id'])) {
    header("Location: staff_dashboard.php");
    exit();
}

// -----------------------------------------------------
// 1. Handle Login Request (POST)
// -----------------------------------------------------
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login'])) {
    $staff_username = trim(htmlspecialchars($_POST['username']));
    $input_password = $_POST['password'];

    if (empty($staff_username) || empty($input_password)) {
        $message = "Please enter both username and password.";
        $message_type = 'error';
    } else {
        // NOTE: We assume a 'staff_login' table exists with columns: staff_id, username, password_hash
        // For demonstration, we will use 'staff_id' from the 'staff' table and assume a 'username' column for simplicity, 
        // as a dedicated staff_login table schema was not provided. In a real system, you MUST use a hashed password from a login table.
        
        // Mocked secure check for staff credentials (Replace this with real hashed password verification later)
        // For now, we'll try to find the staff by a unique identifier like staff_id/username.
        
        // This query attempts to join staff and a hypothetical staff_login table (or assumes username is in 'staff')
        // Let's modify to use a simple lookup on a known staff_id/username pattern for initial testing.
        
        // *** IMPORTANT ***: Since a staff_login table schema was not provided, we will temporarily check against the 'staff' table
        // by looking up a unique identifier (like staff_id) and checking a placeholder password.
        // You MUST implement a proper 'staff_login' table with hashed passwords for production!
        
        $sql = "SELECT staff_id, position FROM staff WHERE staff_id = ? AND first_name = ?"; // Mocked authentication using staff_id and first_name
        
        // For secure testing, let's assume Staff ID is the username, and the password is 'pass'
        // This is highly insecure and for testing only. Replace with proper logic.
        $sql = "SELECT staff_id, position FROM staff WHERE staff_id = ?"; // Check by ID
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $staff_username); // Assuming staff_username input is actually the staff_id for simplicity
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 1) {
            $staff_user = $result->fetch_assoc();
            
            // MOCK PASSWORD CHECK: If staff ID is found, assume the password is 'password123' for testing
            // In a real application, you'd use password_verify($input_password, $staff_user['password_hash'])
            if ($input_password === 'password123') { 
                $_SESSION['staff_id'] = $staff_user['staff_id'];
                $_SESSION['staff_position'] = $staff_user['position'];
                
                // Audit Log Entry
                $user_role = $staff_user['position'];
                $user_id = $staff_user['staff_id'];
                $action_type = "STAFF_LOGIN";
                $details = "Staff user {$user_id} ({$user_role}) logged in successfully.";
                $ip_address = $_SERVER['REMOTE_ADDR'];
                
                $sql_audit = "INSERT INTO audit_log (timestamp, user_role, user_id, action_type, details, ip_address) VALUES (NOW(), ?, ?, ?, ?, ?)";
                $stmt_audit = $conn->prepare($sql_audit);
                $stmt_audit->bind_param("sisss", $user_role, $user_id, $action_type, $details, $ip_address);
                $stmt_audit->execute();
                $stmt_audit->close();

                header("Location: staff_dashboard.php");
                exit();
            } else {
                $message = "Invalid Staff ID or Password.";
                $message_type = 'error';
            }

        } else {
            $message = "Invalid Staff ID or Password.";
            $message_type = 'error';
        }
        $stmt->close();
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Login - Banking System</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { 
            font-family: 'Inter', sans-serif; 
            background: linear-gradient(135deg, #004c8c 0%, #007bff 100%); /* Deep Blue Gradient */
            display: flex; 
            justify-content: center; 
            align-items: center; 
            min-height: 100vh; 
            margin: 0;
            color: #333;
        }

        .login-container {
            background: rgba(255, 255, 255, 0.95);
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 400px;
            text-align: center;
        }

        h2 {
            color: #004c8c;
            margin-bottom: 25px;
            font-weight: 800;
        }
        
        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #555;
        }

        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 8px;
            box-sizing: border-box;
            font-size: 1em;
            transition: border-color 0.3s, box-shadow 0.3s;
        }

        .form-group input:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.25);
            outline: none;
        }

        .submit-btn {
            background-color: #2ecc71;
            color: white;
            padding: 14px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1.1em;
            font-weight: 700;
            width: 100%;
            transition: background-color 0.3s, transform 0.1s;
            box-shadow: 0 4px 10px rgba(46, 204, 113, 0.4);
        }

        .submit-btn:hover {
            background-color: #27ae60;
            transform: translateY(-2px);
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            font-weight: 500;
            text-align: center;
        }

        .alert.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .role-switch {
            margin-top: 25px;
            font-size: 0.9em;
            color: #666;
        }

        .role-switch a {
            color: #007bff;
            text-decoration: none;
            font-weight: 600;
        }
    </style>
</head>
<body>

    <div class="login-container">
        <h2>Staff Portal Login</h2>

        <?php if ($message): ?>
            <div class="alert <?php echo $message_type; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="staff_login.php">
            <div class="form-group">
                <label for="username">Staff ID / Username</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" name="login" class="submit-btn">
                Log In
            </button>
        </form>

        <div class="role-switch">
            Logging in as a Customer? <a href="customer_login.php">Customer Login</a>
            <p style="margin-top: 15px; color: #999; font-size: 0.8em;">
                **TESTING NOTE:** Use your Staff ID for the username and 'password123' for the password.
            </p>
        </div>
    </div>

</body>
</html>
