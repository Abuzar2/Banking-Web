<?php
session_start();

// Database Connection
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

// Handle Registration
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['register'])) {
    // Sanitize inputs
    $first_name = htmlspecialchars(trim($_POST['first_name']));
    $last_name = htmlspecialchars(trim($_POST['last_name']));
    $ssn = htmlspecialchars(trim($_POST['ssn']));
    $address = htmlspecialchars(trim($_POST['address']));
    $phone_number = htmlspecialchars(trim($_POST['phone_number']));
    $date_of_birth = $_POST['date_of_birth'];
    $city = htmlspecialchars(trim($_POST['city']));
    $mobile_no = htmlspecialchars(trim($_POST['mobile_no']));
    $username = htmlspecialchars(trim($_POST['username']));
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validation
    $errors = [];

    // Check if username already exists
    $check_username = $conn->prepare("SELECT cust_id FROM cust_login WHERE username = ?");
    $check_username->bind_param("s", $username);
    $check_username->execute();
    if ($check_username->get_result()->num_rows > 0) {
        $errors[] = "Username already exists. Please choose another.";
    }
    $check_username->close();

    // Check if SSN already exists
    if (!empty($ssn)) {
        $check_ssn = $conn->prepare("SELECT cust_id FROM customer WHERE ssn = ?");
        $check_ssn->bind_param("s", $ssn);
        $check_ssn->execute();
        if ($check_ssn->get_result()->num_rows > 0) {
            $errors[] = "SSN already registered in our system.";
        }
        $check_ssn->close();
    }

    // Password validation
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    }
    if (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters long.";
    }

    // Required fields
    if (empty($first_name) || empty($last_name) || empty($date_of_birth) || 
        empty($city) || empty($mobile_no) || empty($username) || empty($password)) {
        $errors[] = "All required fields must be filled.";
    }

    if (empty($errors)) {
        $conn->begin_transaction();
        
        try {
            // Insert into customer table with pending status
            $sql_customer = "INSERT INTO customer (first_name, last_name, ssn, address, phone_number, date_of_birth, city, mobile_no, status) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')";
            $stmt_customer = $conn->prepare($sql_customer);
            $stmt_customer->bind_param("ssssssss", $first_name, $last_name, $ssn, $address, $phone_number, $date_of_birth, $city, $mobile_no);
            
            if ($stmt_customer->execute()) {
                $cust_id = $stmt_customer->insert_id;
                
                // Create login credentials
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $sql_login = "INSERT INTO cust_login (cust_id, username, password_hash) VALUES (?, ?, ?)";
                $stmt_login = $conn->prepare($sql_login);
                $stmt_login->bind_param("iss", $cust_id, $username, $password_hash);
                
                if ($stmt_login->execute()) {
                    $conn->commit();
                    $message = "Registration submitted successfully! Your application is under review. You will be notified once approved.";
                    $message_type = 'success';
                    
                    // Clear form
                    $_POST = array();
                } else {
                    throw new Exception("Failed to create login credentials.");
                }
                $stmt_login->close();
            } else {
                throw new Exception("Failed to save customer details.");
            }
            $stmt_customer->close();
            
        } catch (Exception $e) {
            $conn->rollback();
            $message = "Registration failed: " . $e->getMessage();
            $message_type = 'error';
        }
    } else {
        $message = implode("<br>", $errors);
        $message_type = 'error';
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Registration - Secure Banking Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f4f6f9; color: #34495e; margin: 0; padding: 0; }
        
        .header { 
            background-color: #004c8c; 
            color: white; 
            padding: 15px 40px; 
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .logo h1 { font-size: 1.5em; font-weight: 700; margin: 0; }
        .nav-links a { 
            color: white; 
            text-decoration: none; 
            margin-left: 20px; 
            padding: 5px 10px;
            border-radius: 4px;
        }
        .nav-links a:hover { background-color: #0056b3; }
        
        .container { max-width: 800px; margin: 30px auto; padding: 0 20px; }
        
        h2 { 
            color: #004c8c; 
            border-bottom: 3px solid #e0e6ed; 
            padding-bottom: 8px; 
            margin-bottom: 25px; 
        }

        .register-card {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        }

        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #555; }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 6px;
            box-sizing: border-box;
            font-size: 1em;
        }
        .form-group input:focus { border-color: #007bff; outline: none; }
        
        .form-row { display: flex; gap: 15px; }
        .form-row .form-group { flex: 1; }
        
        .submit-btn {
            background-color: #2ecc71;
            color: white;
            padding: 15px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1.1em;
            font-weight: 600;
            width: 100%;
        }
        .submit-btn:hover { background-color: #27ae60; }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 6px;
            font-weight: 500;
        }
        .alert.success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert.error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .alert.info { background-color: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }

        .login-link { text-align: center; margin-top: 20px; }
        .required { color: #e74c3c; }
    </style>
</head>
<body>

    <div class="header">
        <div class="logo">
            <h1>SECURE BANKING PORTAL</h1>
        </div>
        <div class="nav-links">
            <a href="customer_login.php">Customer Login</a>
            <a href="index.php">Home</a>
        </div>
    </div>

    <div class="container">
        <h2>New Customer Registration</h2>

        <?php if ($message): ?>
            <div class="alert <?php echo $message_type; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="alert info">
            <strong>Note:</strong> Your registration will be reviewed by our staff. You will be able to login only after approval.
            This process may take 1-2 business days.
        </div>

        <div class="register-card">
            <form method="POST" action="customer_register.php">
                <h3>Personal Information</h3>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="first_name">First Name <span class="required">*</span></label>
                        <input type="text" id="first_name" name="first_name" value="<?php echo $_POST['first_name'] ?? ''; ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="last_name">Last Name <span class="required">*</span></label>
                        <input type="text" id="last_name" name="last_name" value="<?php echo $_POST['last_name'] ?? ''; ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="ssn">Social Security Number (SSN)</label>
                    <input type="text" id="ssn" name="ssn" value="<?php echo $_POST['ssn'] ?? ''; ?>" maxlength="9" placeholder="9 digits without dashes">
                </div>

                <div class="form-group">
                    <label for="date_of_birth">Date of Birth <span class="required">*</span></label>
                    <input type="date" id="date_of_birth" name="date_of_birth" value="<?php echo $_POST['date_of_birth'] ?? ''; ?>" required max="<?php echo date('Y-m-d', strtotime('-18 years')); ?>">
                </div>

                <div class="form-group">
                    <label for="address">Address</label>
                    <textarea id="address" name="address" rows="2"><?php echo $_POST['address'] ?? ''; ?></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="city">City <span class="required">*</span></label>
                        <input type="text" id="city" name="city" value="<?php echo $_POST['city'] ?? ''; ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="mobile_no">Mobile Number <span class="required">*</span></label>
                        <input type="tel" id="mobile_no" name="mobile_no" value="<?php echo $_POST['mobile_no'] ?? ''; ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="phone_number">Home Phone Number</label>
                    <input type="tel" id="phone_number" name="phone_number" value="<?php echo $_POST['phone_number'] ?? ''; ?>">
                </div>

                <h3>Login Credentials</h3>

                <div class="form-group">
                    <label for="username">Username <span class="required">*</span></label>
                    <input type="text" id="username" name="username" value="<?php echo $_POST['username'] ?? ''; ?>" required>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="password">Password <span class="required">*</span></label>
                        <input type="password" id="password" name="password" required minlength="6">
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">Confirm Password <span class="required">*</span></label>
                        <input type="password" id="confirm_password" name="confirm_password" required>
                    </div>
                </div>

                <div class="form-group">
                    <button type="submit" name="register" class="submit-btn">
                        Submit Registration for Review
                    </button>
                </div>
            </form>

            <div class="login-link">
                <p>Already have an account? <a href="customer_login.php">Login here</a></p>
            </div>
        </div>
    </div>

    <script>
        // Password confirmation validation
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirmPassword = this.value;
            
            if (confirmPassword !== password) {
                this.style.borderColor = '#e74c3c';
            } else {
                this.style.borderColor = '#2ecc71';
            }
        });

        // Age validation (18+)
        document.getElementById('date_of_birth').addEventListener('change', function() {
            const dob = new Date(this.value);
            const today = new Date();
            const age = today.getFullYear() - dob.getFullYear();
            
            if (age < 18) {
                alert('You must be 18 years or older to register.');
                this.value = '';
            }
        });
    </script>
</body>
</html>