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

// 1. Get Customer ID from URL
$cust_id = (isset($_GET['cust_id'])) ? (int)$_GET['cust_id'] : null;

if (!$cust_id) {
    // Handle the case where the ID is missing (the error you received)
    $error_message = "Invalid or missing Customer ID.";
} else {
    // 2. Prepare and Execute Query to fetch customer data
    $sql = "SELECT cust_id, first_name, last_name, ssn, address, phone_number, date_of_birth, city, mobile_no 
            FROM CUSTOMER 
            WHERE cust_id = ?";
    
    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        $stmt->bind_param("i", $cust_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $customer = $result->fetch_assoc();
        } else {
            $error_message = "Customer with ID " . htmlspecialchars($cust_id) . " not found.";
        }
        $stmt->close();
    } else {
        $error_message = "Database query preparation failed: " . $conn->error;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin - View Customer</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Roboto', sans-serif; background-color: #f0f4f8; color: #333; margin: 0; padding: 0; }
        .header { background-color: #007bff; color: white; padding: 20px; text-align: center; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); }
        .container { max-width: 800px; margin: 30px auto; padding: 30px; background: white; border-radius: 8px; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1); }
        h2 { color: #007bff; border-bottom: 2px solid #eee; padding-bottom: 10px; margin-bottom: 30px; font-weight: 700; }
        .detail-group { margin-bottom: 20px; padding: 15px; border-left: 5px solid #007bff; background-color: #f9f9ff; border-radius: 4px; }
        .detail-group label { display: block; margin-bottom: 5px; font-weight: 700; color: #555; font-size: 0.9em; }
        .detail-group p { margin: 0; font-size: 1.1em; color: #333; }
        .detail-row { display: flex; flex-wrap: wrap; gap: 20px; }
        .detail-col { flex: 1 1 calc(50% - 10px); min-width: 300px; }
        .action-buttons { margin-top: 30px; text-align: right; }
        .btn { padding: 10px 15px; border-radius: 4px; font-weight: 500; text-decoration: none; transition: background-color 0.3s; }
        .btn-edit { background-color: #ffc107; color: #212529; margin-left: 10px; }
        .btn-edit:hover { background-color: #e0a800; }
        .btn-back { background-color: #6c757d; color: white; }
        .btn-back:hover { background-color: #5a6268; }
        .error-message { padding: 20px; background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; border-radius: 4px; font-weight: 500; text-align: center; }
    </style>
</head>
<body>

    <div class="header">
        <h1>BANKING ADMIN SYSTEM</h1>
    </div>

    <div class="container">
        <?php if (isset($error_message)): ?>
            <div class="error-message">
                <?php echo $error_message; ?>
                <div class="action-buttons" style="text-align: center; margin-top: 15px;">
                    <a href="admin_customer_details.php" class="btn btn-back">Go Back to List</a>
                </div>
            </div>
        <?php else: ?>
            <h2>Customer Profile: <?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']); ?> (ID: <?php echo htmlspecialchars($customer['cust_id']); ?>)</h2>
            
            <div class="detail-row">
                <div class="detail-col">
                    <div class="detail-group">
                        <label>First Name</label>
                        <p><?php echo htmlspecialchars($customer['first_name']); ?></p>
                    </div>
                </div>
                <div class="detail-col">
                    <div class="detail-group">
                        <label>Last Name</label>
                        <p><?php echo htmlspecialchars($customer['last_name']); ?></p>
                    </div>
                </div>
            </div>

            <div class="detail-row">
                <div class="detail-col">
                    <div class="detail-group">
                        <label>Date of Birth</label>
                        <p><?php echo htmlspecialchars($customer['date_of_birth']); ?></p>
                    </div>
                </div>
                <div class="detail-col">
                    <div class="detail-group">
                        <label>SSN</label>
                        <p><?php echo htmlspecialchars($customer['ssn'] ?? 'N/A'); ?></p>
                    </div>
                </div>
            </div>

            <div class="detail-row">
                 <div class="detail-col">
                    <div class="detail-group">
                        <label>Mobile Number</label>
                        <p><?php echo htmlspecialchars($customer['mobile_no']); ?></p>
                    </div>
                </div>
                <div class="detail-col">
                    <div class="detail-group">
                        <label>Home Phone</label>
                        <p><?php echo htmlspecialchars($customer['phone_number'] ?? 'N/A'); ?></p>
                    </div>
                </div>
            </div>

            <div class="detail-group">
                <label>Address</label>
                <p><?php echo htmlspecialchars($customer['address'] ?? 'N/A'); ?></p>
            </div>
            
            <div class="detail-group">
                <label>City</label>
                <p><?php echo htmlspecialchars($customer['city']); ?></p>
            </div>


            <div class="action-buttons">
                <a href="admin_customer_details.php" class="btn btn-back">Back to List</a>
                <a href="admin_customer_edit.php?cust_id=<?php echo htmlspecialchars($customer['cust_id']); ?>" class="btn btn-edit">Edit Customer</a>
            </div>

        <?php endif; ?>
    </div>

</body>
</html>
