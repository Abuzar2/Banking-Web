<?php
// -----------------------------------------------------
// 1. SESSION START AND SECURITY CHECK
// -----------------------------------------------------
session_start();

if (!isset($_GET['welcome'])) {
    header("Location: index.html");
    exit();
}

// Database Connection Details (MUST match the configuration used in admin_customer_detail.php)
$servername = "localhost";
$username = "root";   
$password = ""; 
$dbname = "banking_sys";
$port = 3307;

$conn = new mysqli($servername, $username, $password, $dbname, $port);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$user_first_name = htmlspecialchars($_GET['welcome']);
$message = "";

// -----------------------------------------------------
// 2. HANDLE ADD CUSTOMER (INSERT)
// -----------------------------------------------------
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_customer'])) {
    $fn = $_POST['first_name'];
    $ln = $_POST['last_name'];
    $ssn = $_POST['ssn'];
    $addr = $_POST['address'];
    $phone = $_POST['phone_number'];
    $dob = $_POST['date_of_birth'];
    $city = $_POST['city'] ?? '';
    $mobile_no = $_POST['mobile_no'] ?? '';

    // SQL statement uses all required fields from the 'customer' table schema
    $stmt = $conn->prepare("INSERT INTO CUSTOMER (first_name, last_name, ssn, address, phone_number, date_of_birth, city, mobile_no) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssss", $fn, $ln, $ssn, $addr, $phone, $dob, $city, $mobile_no);

    if ($stmt->execute()) {
        $message = "<div style='color: green;'>✅ Customer **{$fn} {$ln}** added successfully! Now link an account via the 'View Details' page.</div>";
    } else {
        $message = "<div style='color: red;'>❌ Error adding customer: " . $stmt->error . "</div>";
    }
    $stmt->close();
}

// -----------------------------------------------------
// 3. HANDLE REMOVE CUSTOMER (DELETE)
// -----------------------------------------------------
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['remove_customer'])) {
    $cust_id_to_remove = (int)$_POST['cust_id_remove'];
    
    // In a production system, these deletes ensure no foreign key constraints fail.
    // We delete related records first before deleting the main customer.
    $conn->query("DELETE FROM CUSTOMER_ACCOUNT WHERE cust_id = $cust_id_to_remove");
    $conn->query("DELETE FROM CUSTOMER_LOAN WHERE cust_id = $cust_id_to_remove");
    
    $stmt = $conn->prepare("DELETE FROM CUSTOMER WHERE cust_id = ?");
    $stmt->bind_param("i", $cust_id_to_remove);

    if ($stmt->execute() && $conn->affected_rows > 0) {
        $message = "<div style='color: green;'>✅ Customer ID **{$cust_id_to_remove}** removed successfully.</div>";
    } else {
        $message = "<div style='color: red;'>❌ Error removing customer or ID not found.</div>";
    }
    $stmt->close();
}

// -----------------------------------------------------
// 4. SQL QUERY for Display
// -----------------------------------------------------
$sql = "
    SELECT 
        C.cust_id, 
        C.first_name, 
        C.last_name, 
        A.account_number, 
        A.account_type, 
        A.balance,
        A.date_opened
    FROM 
        CUSTOMER C
    LEFT JOIN  
        CUSTOMER_ACCOUNT CA ON C.cust_id = CA.cust_id
    LEFT JOIN
        ACCOUNT A ON CA.account_number = A.account_number
    ORDER BY 
        C.last_name, C.first_name, A.account_type
";

$result = $conn->query($sql);

// Data processing to group accounts by customer
$customers_data = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $cust_full_name = $row['first_name'] . ' ' . $row['last_name'];
        $cust_id = $row['cust_id'];
        
        if (!isset($customers_data[$cust_id])) {
            $customers_data[$cust_id] = [
                'name' => $cust_full_name,
                'id' => $cust_id,
                'accounts' => []
            ];
        }
        
        // Add account details only if they exist
        if ($row['account_number']) {
            $customers_data[$cust_id]['accounts'][] = [
                'number' => $row['account_number'],
                'type' => $row['account_type'],
                'balance' => $row['balance'],
                'opened' => $row['date_opened']
            ];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Full Customer Access | Admin Panel</title>
    <style>
        body { font-family: 'Arial', sans-serif; background-color: #f0f4f8; color: #333; margin: 0; padding: 0; }
        .header { background-color: #007bff; color: white; padding: 15px; text-align: center; }
        .container { max-width: 1200px; margin: 30px auto; padding: 20px; background: white; border-radius: 8px; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1); }
        h2 { color: #007bff; margin-bottom: 20px; border-bottom: 2px solid #ccc; padding-bottom: 5px; }
        .customer-card { border: 1px solid #ccc; padding: 15px; margin-bottom: 20px; border-radius: 6px; background-color: #f9f9f9; }
        /* Updated h3 style to include the "View Details" button */
        .customer-card h3 { 
            color: #dc3545; 
            margin-top: 0; 
            border-bottom: 2px solid #dc3545; 
            padding-bottom: 5px;
            display: flex; /* Flexbox for alignment */
            justify-content: space-between;
            align-items: center;
        }
        .account-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .account-table th, .account-table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .account-table th { background-color: #e9ecef; }
        .balance-col { font-weight: bold; color: green; }
        .back-link { display: block; margin-top: 20px; color: #007bff; text-decoration: none; font-weight: bold; }

        /* Styles for Forms and Buttons */
        .add-form-section, .remove-form-section { border: 1px solid #ddd; padding: 20px; border-radius: 6px; margin-bottom: 30px; }
        .add-form-section form { display: flex; flex-wrap: wrap; align-items: flex-end; gap: 10px; }
        .add-form-section input { width: 15%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; min-width: 100px; }
        .add-form-section input[type="date"] { width: 100px; }
        .add-form-section input:nth-child(4) { width: 20%; } /* Address */
        .add-form-section button { padding: 10px 15px; background-color: #28a745; color: white; border: none; border-radius: 4px; cursor: pointer; }
        .remove-form-section { background-color: #ffeaea; border-color: #dc3545; }
        .remove-form-section input { width: 150px; padding: 8px; margin-right: 10px; border: 1px solid #dc3545; border-radius: 4px; box-sizing: border-box; }
        .remove-form-section button { padding: 10px 15px; background-color: #dc3545; color: white; border: none; border-radius: 4px; cursor: pointer; }
        
        /* General Action Button Style */
        .action-button { background-color: #007bff; color: white; border: none; padding: 6px 10px; border-radius: 3px; cursor: pointer; text-decoration: none; display: inline-block; font-size: 0.9rem;}
        .action-button:hover { background-color: #0056b3; }

        @media (max-width: 768px) {
            .add-form-section input { width: 100%; margin-right: 0; margin-bottom: 5px; }
            .add-form-section button { width: 100%; }
            .customer-card h3 { flex-direction: column; align-items: flex-start; }
            .customer-card h3 .action-button { margin-top: 10px; margin-left: 0 !important; }
        }
    </style>
</head>
<body>

    <div class="header">
        <h1>Full Customer Access</h1>
    </div>

    <div class="container">
        <?php echo $message; // Display success/error message ?>
        
        <div class="add-form-section">
            <h2>➕ Add New Customer</h2>
            <form method="POST" action="admin_customer_full.php?welcome=<?php echo $user_first_name; ?>">
                <input type="text" name="first_name" placeholder="First Name" required>
                <input type="text" name="last_name" placeholder="Last Name" required>
                <input type="text" name="ssn" placeholder="SSN (9 Digits)" maxlength="9" required>
                <input type="text" name="address" placeholder="Address" required>
                <input type="text" name="phone_number" placeholder="Phone" required>
                <input type="text" name="city" placeholder="City" required> 
                <input type="text" name="mobile_no" placeholder="Mobile No.">
                <input type="date" name="date_of_birth" placeholder="DOB" required>
                <button type="submit" name="add_customer" style="flex-grow: 1;">Add Customer</button>
            </form>
        </div>
        
        <h2>Customer Accounts and Balances</h2>
        
        <?php
        if (empty($customers_data)) {
            echo "<p>No customers or accounts found in the database. Add one above!</p>";
        } else {
            foreach ($customers_data as $cust_id => $data) {
                echo "<div class='customer-card'>";
                
                // --- CRITICAL UPDATE: ADD LINK TO DETAIL PAGE ---
                echo "<h3>Customer: " . htmlspecialchars($data['name']) . " (ID: " . $data['id'] . ")";
                // Link to the newly created admin_customer_detail.php, passing the customer ID
                echo "<a href='admin_customer_detail.php?id=" . $data['id'] . "&welcome=" . $user_first_name . "' class='action-button'>View Details</a>";
                echo "</h3>";
                // ------------------------------------------------
                
                if (!empty($data['accounts'])) {
                    echo "<table class='account-table'>";
                    echo "<thead><tr><th>Account Number</th><th>Type</th><th>Date Opened</th><th>Current Balance</th><th>Actions</th></tr></thead>";
                    echo "<tbody>";
                    
                    // Display each account
                    foreach ($data['accounts'] as $account) {
                        echo "<tr>";
                        echo "<td>" . $account['number'] . "</td>";
                        echo "<td>" . $account['type'] . "</td>";
                        echo "<td>" . $account['opened'] . "</td>";
                        echo "<td class='balance-col'>$" . number_format($account['balance'], 2) . "</td>";
                        // This transaction link is specific to the account
                        echo "<td><a href='admin_transactions.php?account=" . $account['number'] . "&welcome=" . $user_first_name . "' class='action-button' style='background-color: #dc3545;'>View Transactions</a></td>";
                        echo "</tr>";
                    }
                    
                    echo "</tbody>";
                    echo "</table>";
                } else {
                    echo "<p>This customer currently holds no accounts. Use the 'View Details' button to open one.</p>";
                }
                echo "</div>";
            }
        }
        
        $conn->close();
        ?>

        <div class="remove-form-section">
            <h2>➖ Remove Customer</h2>
            <form method="POST" action="admin_customer_full.php?welcome=<?php echo $user_first_name; ?>" style="display: flex; align-items: center;">
                <label for="cust_id_remove" style="margin-right: 10px; font-weight: bold; color: #dc3545;">Customer ID:</label>
                <input type="number" name="cust_id_remove" required placeholder="Enter Customer ID">
                <button type="submit" name="remove_customer">Remove Customer Permanently</button>
            </form>
        </div>

        <a class="back-link" href="admin_dashboard.php?welcome=<?php echo $user_first_name; ?>">← Back to Admin Dashboard</a>
    </div>

</body>
</html>
