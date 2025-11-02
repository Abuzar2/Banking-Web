<?php
session_start();

// Security Check: If the customer is not logged in, redirect to login page
if (!isset($_SESSION['cust_id'])) {
    header("Location: customer_login.php");
    exit();
}

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

$cust_id = $_SESSION['cust_id'];
$customer_data = null;
$message = '';
$message_type = ''; // 'success' or 'error'

// -----------------------------------------------------
// 1. Handle Profile Update Request (POST)
// -----------------------------------------------------
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_profile'])) {
    
    // Sanitize and validate inputs based on your table columns
    $cust_address = htmlspecialchars($_POST['cust_address']);
    $cust_phone_number = htmlspecialchars($_POST['cust_phone_number']); // corresponds to phone_number
    $cust_city = htmlspecialchars($_POST['cust_city']);
    $cust_mobile_no = htmlspecialchars($_POST['cust_mobile_no']); // corresponds to mobile_no

    if (!$cust_address || !$cust_phone_number || !$cust_city || !$cust_mobile_no) {
        $message = "All editable fields (Address, Phone, City, Mobile) are required.";
        $message_type = 'error';
    } else {
        // SQL to update CUSTOMER table
        // We update the address, city, phone_number, and mobile_no
        $sql_update = "UPDATE customer SET address = ?, phone_number = ?, city = ?, mobile_no = ? WHERE cust_id = ?";
        $stmt_update = $conn->prepare($sql_update);
        
        if ($stmt_update === false) {
             $message = "Database error during preparation: " . $conn->error;
             $message_type = 'error';
        } else {
            $stmt_update->bind_param("ssssi", $cust_address, $cust_phone_number, $cust_city, $cust_mobile_no, $cust_id);

            if ($stmt_update->execute()) {
                // Update session variable for immediate effect (if needed, though name isn't changing)
                // For this structure, we rely on the next fetch to get fresh data
                $message = "Your profile information has been updated successfully!";
                $message_type = 'success';
            } else {
                $message = "Error updating profile: " . $stmt_update->error;
                $message_type = 'error';
            }
            $stmt_update->close();
        }
    }
}

// -----------------------------------------------------
// 2. Fetch Customer Data (before or after update)
// -----------------------------------------------------
$sql_fetch = "SELECT first_name, last_name, address, phone_number, city, mobile_no, ssn, date_of_birth FROM customer WHERE cust_id = ?";
$stmt_fetch = $conn->prepare($sql_fetch);
$stmt_fetch->bind_param("i", $cust_id);
$stmt_fetch->execute();
$result_fetch = $stmt_fetch->get_result();

if ($result_fetch->num_rows > 0) {
    $customer_data = $result_fetch->fetch_assoc();
} else {
    // Should not happen if the user is logged in
    $message = "Error: Customer data not found.";
    $message_type = 'error';
}
$stmt_fetch->close();
$conn->close();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Profile - Secure Banking Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* General Setup */
        body { font-family: 'Inter', sans-serif; background-color: #f4f6f9; color: #34495e; margin: 0; padding: 0; }
        
        /* Header & Navigation */
        .header { 
            background-color: #004c8c; 
            color: white; 
            padding: 15px 40px; 
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        .logo h1 { font-size: 1.5em; font-weight: 700; margin: 0; }
        .nav-links a { 
            color: white; 
            text-decoration: none; 
            margin-left: 20px; 
            padding: 5px 10px;
            border-radius: 4px;
            transition: background-color 0.3s;
        }
        .nav-links a:hover {
            background-color: #0056b3;
        }
        .container { max-width: 600px; margin: 30px auto; padding: 0 20px; }
        
        h2 { 
            color: #004c8c; 
            border-bottom: 3px solid #e0e6ed; 
            padding-bottom: 8px; 
            margin-bottom: 25px; 
            font-weight: 700;
            font-size: 1.8em;
        }

        /* Card and Form Styling */
        .profile-card {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            border: 1px solid #e0e6ed;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #555;
        }
        .form-group input:not([disabled]), .form-group textarea:not([disabled]) {
            width: 100%;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 6px;
            box-sizing: border-box;
            font-size: 1em;
            transition: border-color 0.3s, box-shadow 0.3s;
        }
        .form-group input:focus:not([disabled]), .form-group textarea:focus:not([disabled]) {
            border-color: #007bff;
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.25);
            outline: none;
        }
        .form-group input[disabled] {
            background-color: #e9ecef; /* Light gray for disabled fields */
            color: #6c757d;
            cursor: not-allowed;
        }

        .submit-btn {
            background-color: #2ecc71; /* Green for Update/Save */
            color: white;
            padding: 15px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1.1em;
            font-weight: 600;
            width: 100%;
            transition: background-color 0.3s, transform 0.1s;
        }
        .submit-btn:hover {
            background-color: #27ae60;
            transform: translateY(-1px);
        }

        /* Message Styles */
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 6px;
            font-weight: 500;
        }
        .alert.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        /* Utility */
        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #007bff;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.2s;
        }
        .back-link:hover {
            color: #0056b3;
            text-decoration: underline;
        }
    </style>
</head>
<body>

    <div class="header">
        <div class="logo">
            <h1>SECURE BANKING PORTAL</h1>
        </div>
        <div class="nav-links">
            <a href="customer_dashboard.php">Dashboard</a>
        </div>
    </div>

    <div class="container">
        
        <a href="customer_dashboard.php" class="back-link">&leftarrow; Back to Dashboard</a>
        <h2>Manage My Profile</h2>

        <?php if ($message): ?>
            <div class="alert <?php echo $message_type; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="profile-card">

            <?php if ($customer_data): ?>
                <p style="margin-top: 0; color: #7f8c8d;">Customer ID: **<?php echo $cust_id; ?>** | SSN (Partial): **<?php echo substr($customer_data['ssn'], -4); ?>**</p>
                <form method="POST" action="customer_manage_profile.php">
                    
                    <div class="form-group">
                        <label for="full_name">Full Name</label>
                        <input type="text" id="full_name" value="<?php echo htmlspecialchars($customer_data['first_name'] . ' ' . $customer_data['last_name']); ?>" disabled>
                        <small style="color: #999;">Contact support to change your legal name.</small>
                    </div>

                    <div class="form-group">
                        <label for="date_of_birth">Date of Birth</label>
                        <input type="date" id="date_of_birth" value="<?php echo htmlspecialchars($customer_data['date_of_birth']); ?>" disabled>
                    </div>

                    <h3 style="color: #004c8c; border-bottom: 1px solid #eee; padding-bottom: 5px; margin-top: 30px;">Contact Information</h3>

                    <div class="form-group">
                        <label for="cust_mobile_no">Mobile Number (Primary)</label>
                        <input type="tel" id="cust_mobile_no" name="cust_mobile_no" value="<?php echo htmlspecialchars($customer_data['mobile_no']); ?>" maxlength="15" required>
                    </div>

                    <div class="form-group">
                        <label for="cust_phone_number">Home/Other Phone</label>
                        <input type="tel" id="cust_phone_number" name="cust_phone_number" value="<?php echo htmlspecialchars($customer_data['phone_number']); ?>" maxlength="15" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="cust_address">Street Address</label>
                        <textarea id="cust_address" name="cust_address" rows="2" maxlength="255" required><?php echo htmlspecialchars($customer_data['address']); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="cust_city">City</label>
                        <input type="text" id="cust_city" name="cust_city" value="<?php echo htmlspecialchars($customer_data['city']); ?>" maxlength="50" required>
                    </div>

                    <div class="form-group">
                        <button type="submit" name="update_profile" class="submit-btn">
                            Save Profile Changes
                        </button>
                    </div>
                </form>
            <?php else: ?>
                <div class="alert error">
                    Unable to load profile data. Please return to the dashboard and try again.
                </div>
            <?php endif; ?>

        </div>
        
        <div style="height: 40px;"></div>

    </div>

</body>
</html>
```eof

This code now correctly uses the fields from your `customer` table (`first_name`, `last_name`, `address`, `phone_number`, `city`, `mobile_no`) for display and update operations.

Do you want to proceed with building the **Employee/Staff Portal** features, or is there another customer-side function you need?