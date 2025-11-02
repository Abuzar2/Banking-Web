<?php
session_start();
// Check if admin is logged in
if (!isset($_SESSION['admin_user'])) {
    header("Location: admin_login.php");
    exit();
}

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
$message_type = ''; // success or danger

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 1. Sanitize and Validate Input
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $ssn = trim($_POST['ssn']);
    $address = trim($_POST['address']);
    $phone_number = trim($_POST['phone_number']);
    $dob = trim($_POST['date_of_birth']);
    $city = trim($_POST['city']);
    $mobile_no = trim($_POST['mobile_no']);
    
    // Basic validation 
    if (empty($first_name) || empty($last_name) || empty($mobile_no) || empty($dob)) {
        $message = "Error: Required fields (First Name, Last Name, Mobile No., Date of Birth) cannot be empty.";
        $message_type = 'danger';
    } else {
        // 2. Prepare SQL Statement for Insertion
        $sql = "INSERT INTO CUSTOMER (first_name, last_name, ssn, address, phone_number, date_of_birth, city, mobile_no) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        
        if ($stmt) {
            // s: string
            $stmt->bind_param("ssssssss", 
                $first_name, 
                $last_name, 
                $ssn, 
                $address, 
                $phone_number, 
                $dob, 
                $city, 
                $mobile_no
            );
            
            // 3. Execute and Check Result
            if ($stmt->execute()) {
                $new_cust_id = $conn->insert_id;
                $message = "✅ Success! New Customer **" . htmlspecialchars($first_name) . " " . htmlspecialchars($last_name) . "** created with ID: **" . $new_cust_id . "**.";
                $message_type = 'success';
                
                // Clear POST data to prevent form resubmission on refresh
                $_POST = array(); 
            } else {
                // Check for duplicate SSN error (UNIQUE KEY constraint)
                if ($conn->errno == 1062) {
                    $message = "Error: A customer with that SSN or mobile number already exists.";
                } else {
                    $message = "Error: Could not add customer. MySQL Error: " . $stmt->error;
                }
                $message_type = 'danger';
            }
            $stmt->close();
        } else {
            $message = "Database query preparation failed: " . $conn->error;
            $message_type = 'danger';
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Add New Customer</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #00796B; /* Teal/Green for success/primary action */
            --accent-color: #3f51b5; /* Professional Blue */
            --danger-color: #dc3545;
            --background-light: #f4f7f9;
            --text-dark: #333;
            --text-medium: #555;
            --border-color: #e0e0e0;
        }

        body { 
            font-family: 'Inter', sans-serif; 
            background-color: var(--background-light); 
            color: var(--text-dark); 
            margin: 0; 
            padding: 0; 
        }
        .header { 
            background-color: var(--accent-color); 
            color: white; 
            padding: 15px 0; 
            text-align: center; 
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1); 
            font-size: 1.5em;
            font-weight: 600;
        }
        .container { 
            max-width: 750px; 
            margin: 40px auto; 
            padding: 30px; 
            background: white; 
            border-radius: 12px; 
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08); 
        }
        .back-link { 
            display: inline-block; 
            margin-bottom: 25px; 
            color: var(--accent-color); 
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s;
        }
        .back-link:hover { color: #5c6bc0; }

        h2 { 
            color: var(--primary-color); 
            border-bottom: 2px solid var(--border-color); 
            padding-bottom: 15px; 
            margin-bottom: 30px; 
            font-weight: 700; 
        }
        h3 { 
            color: var(--accent-color); 
            padding-bottom: 5px; 
            margin-top: 25px; 
            margin-bottom: 15px; 
            font-weight: 600; 
        }
        
        .form-group { margin-bottom: 20px; }
        .form-group label { 
            display: block; 
            margin-bottom: 6px; 
            font-weight: 500; 
            color: var(--text-medium); 
        }
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            box-sizing: border-box;
            font-size: 1em;
            transition: border-color 0.3s, box-shadow 0.3s; /* Animation */
        }
        /* Input Focus Animation */
        .form-group input:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 3px rgba(0, 121, 107, 0.2); /* Subtle teal glow */
        }

        .form-row { display: flex; gap: 20px; }
        .form-col { flex: 1; }

        .btn-submit {
            background-color: var(--primary-color);
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1.1em;
            font-weight: 600;
            width: 100%;
            transition: background-color 0.3s, transform 0.1s; /* Animation */
            box-shadow: 0 4px 15px rgba(0, 121, 107, 0.3);
        }
        .btn-submit:hover { 
            background-color: #004d40; 
            box-shadow: 0 6px 20px rgba(0, 121, 107, 0.4);
        }
        .btn-submit:active {
            transform: scale(0.99);
        }

        .message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 6px;
            font-weight: 500;
            border: 1px solid transparent;
        }
        .message.success { 
            background-color: #e6f5ea; 
            color: #1e6e3c; 
            border-color: #b8e0c8; 
        }
        .message.danger { 
            background-color: #fcebeb; 
            color: #922b2b; 
            border-color: #f5c6cb; 
        }
        .required-star { color: var(--danger-color); }

        /* Responsive adjustments */
        @media (max-width: 600px) {
            .form-row { flex-direction: column; gap: 0; }
            .container { margin: 20px; padding: 20px; }
        }
    </style>
</head>
<body>

    <div class="header">
        BANKING ADMIN SYSTEM
    </div>

    <div class="container">
        <a href="admin_customer_details.php" class="back-link">
            ← Back to Customer Management
        </a>

        <h2>➕ Register New Customer</h2>
        
        <?php if ($message): ?>
            <div class="message <?php echo $message_type; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <form action="admin_customer_add.php" method="POST">
            <h3>Personal Details</h3>
            <div class="form-row">
                <div class="form-col form-group">
                    <label for="first_name">First Name <span class="required-star">*</span></label>
                    <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>" required>
                </div>
                <div class="form-col form-group">
                    <label for="last_name">Last Name <span class="required-star">*</span></label>
                    <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-col form-group">
                    <label for="date_of_birth">Date of Birth <span class="required-star">*</span></label>
                    <input type="date" id="date_of_birth" name="date_of_birth" value="<?php echo htmlspecialchars($_POST['date_of_birth'] ?? ''); ?>" required>
                </div>
                <div class="form-col form-group">
                    <label for="ssn">SSN (Social Security Number)</label>
                    <input type="text" id="ssn" name="ssn" value="<?php echo htmlspecialchars($_POST['ssn'] ?? ''); ?>">
                </div>
            </div>

            <h3>Contact & Address</h3>
            <div class="form-row">
                <div class="form-col form-group">
                    <label for="mobile_no">Mobile No <span class="required-star">*</span></label>
                    <input type="text" id="mobile_no" name="mobile_no" value="<?php echo htmlspecialchars($_POST['mobile_no'] ?? ''); ?>" required>
                </div>
                <div class="form-col form-group">
                    <label for="phone_number">Home Phone</label>
                    <input type="text" id="phone_number" name="phone_number" value="<?php echo htmlspecialchars($_POST['phone_number'] ?? ''); ?>">
                </div>
            </div>

            <div class="form-group">
                <label for="address">Address</label>
                <input type="text" id="address" name="address" value="<?php echo htmlspecialchars($_POST['address'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label for="city">City</label>
                <input type="text" id="city" name="city" value="<?php echo htmlspecialchars($_POST['city'] ?? ''); ?>">
            </div>

            <div class="form-group" style="margin-top: 30px;">
                <button type="submit" class="btn-submit">Register Customer</button>
            </div>
        </form>

    </div>

</body>
</html>
