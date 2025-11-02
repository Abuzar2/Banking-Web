<?php
session_start();

// Security Check: If the staff member is not logged in, redirect to login page
if (!isset($_SESSION['staff_id'])) {
    header("Location: staff_login.php");
    exit();
}

// Check if the staff member has access (Teller, Loan Officer, Manager)
$allowed_positions = ['Teller', 'Loan Officer', 'Manager'];
if (!in_array($_SESSION['staff_position'], $allowed_positions)) {
    // Audit Log: Unauthorized access attempt
    // In a real system, you'd log this event.
    header("Location: staff_dashboard.php");
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

$search_term = '';
$customer_data = null;
$customer_accounts = [];
$search_executed = false;
$message = '';
$message_type = '';

// -----------------------------------------------------
// 1. Handle Search Request (GET/POST)
// -----------------------------------------------------
if (isset($_REQUEST['search'])) {
    $search_term = trim(htmlspecialchars($_REQUEST['search_term'] ?? ''));
    $search_executed = true;

    if (empty($search_term)) {
        $message = "Please enter a Customer ID, First Name, or Last Name to search.";
        $message_type = 'error';
    } else {
        
        // Determine search type (by ID or by Name)
        if (is_numeric($search_term) && strlen($search_term) < 9) { // Assuming cust_id is relatively short
            $search_by_id = true;
            $cust_id = (int)$search_term;
        } else {
            $search_by_id = false;
            $name_like = "%" . $search_term . "%";
        }

        // --- Step 1a: Fetch Customer Details ---
        $sql_customer = "SELECT cust_id, first_name, last_name, ssn, address, phone_number, mobile_no, city, date_of_birth FROM customer ";
        
        if ($search_by_id) {
            $sql_customer .= "WHERE cust_id = ?";
            $stmt_customer = $conn->prepare($sql_customer);
            $stmt_customer->bind_param("i", $cust_id);
        } else {
            // Search by name (first name OR last name match)
            $sql_customer .= "WHERE first_name LIKE ? OR last_name LIKE ?";
            $stmt_customer = $conn->prepare($sql_customer);
            $stmt_customer->bind_param("ss", $name_like, $name_like);
        }

        $stmt_customer->execute();
        $result_customer = $stmt_customer->get_result();

        if ($result_customer->num_rows === 1) {
            $customer_data = $result_customer->fetch_assoc();
            $target_cust_id = $customer_data['cust_id'];

            // --- Step 1b: Fetch Customer Accounts ---
            $sql_accounts = "
                SELECT 
                    A.account_number, 
                    A.account_type, 
                    A.balance, 
                    A.date_opened, 
                    B.branch_name,
                    B.branch_city
                FROM 
                    account A
                JOIN 
                    customer_account CA ON A.account_number = CA.account_number
                JOIN
                    branch B ON A.branch_id = B.branch_id
                WHERE 
                    CA.cust_id = ?
                ORDER BY 
                    A.account_type DESC, A.account_number ASC
            ";
            
            $stmt_accounts = $conn->prepare($sql_accounts);
            $stmt_accounts->bind_param("i", $target_cust_id);
            $stmt_accounts->execute();
            $result_accounts = $stmt_accounts->get_result();
            
            while ($row = $result_accounts->fetch_assoc()) {
                $customer_accounts[] = $row;
            }
            $stmt_accounts->close();

        } elseif ($result_customer->num_rows > 1) {
            $message = "Multiple customers found. Please refine your search or use the exact Customer ID.";
            $message_type = 'error';
        } else {
            $message = "No customer found matching the search criteria.";
            $message_type = 'error';
        }
        $stmt_customer->close();
    }
}

$conn->close();

// Helper function to format money
function format_currency($amount) {
    return '$' . number_format($amount, 2);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Lookup - Staff Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        /* General Setup */
        body { font-family: 'Inter', sans-serif; background-color: #f0f4f8; color: #34495e; margin: 0; padding: 0; }
        
        /* Header & Navigation (reused from staff_dashboard) */
        .header { 
            background-color: #004c8c; 
            color: white; 
            padding: 15px 40px; 
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
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

        .container { max-width: 1200px; margin: 30px auto; padding: 0 20px; }
        
        h2 { 
            color: #004c8c; 
            border-bottom: 3px solid #e0e6ed; 
            padding-bottom: 8px; 
            margin-bottom: 25px; 
            font-weight: 700;
            font-size: 1.8em;
        }
        h3 {
            color: #007bff;
            border-bottom: 1px solid #eee;
            padding-bottom: 5px;
            margin-top: 30px;
        }
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

        /* Search Form */
        .search-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
            display: flex;
            gap: 15px;
            align-items: center;
            margin-bottom: 30px;
        }
        .search-card input[type="text"] {
            flex-grow: 1;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 1em;
        }
        .search-card button {
            background-color: #007bff;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: background-color 0.3s;
        }
        .search-card button:hover {
            background-color: #0056b3;
        }

        /* Customer Info Display */
        .customer-info-card {
            background: #e9f5ff; /* Light Blue Background */
            border: 1px solid #b3d9ff;
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 30px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px 30px;
        }
        .info-item {
            padding: 5px 0;
            border-bottom: 1px dashed #cce0ff;
        }
        .info-label {
            font-weight: 600;
            color: #004c8c;
            display: block;
            font-size: 0.9em;
        }
        .info-value {
            font-size: 1em;
            word-wrap: break-word;
        }

        /* Account Table Styling (reused) */
        .account-table { 
            width: 100%; 
            border-collapse: separate; 
            border-spacing: 0;
            margin-top: 20px; 
            overflow: hidden;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
        }
        .account-table th, .account-table td { 
            padding: 15px; 
            text-align: left; 
            border-bottom: 1px solid #e0e6ed; 
        }
        .account-table th { 
            background-color: #007bff; 
            color: white; 
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.9em;
        }
        .account-table tbody tr:hover { 
            background-color: #f0f3f6; 
        }
        .balance-col { font-weight: 700; color: #2ecc71; font-size: 1.1em; }

        /* Message Styles */
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            font-weight: 500;
        }
        .alert.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .alert.info {
            background-color: #fff3cd;
            color: #856404;
            border: 1px solid #ffeeba;
        }
    </style>
</head>
<body>

    <div class="header">
        <div class="logo">
            <h1>BANK STAFF PORTAL</h1>
        </div>
        <div class="nav-links">
            <a href="staff_dashboard.php">Dashboard</a>
        </div>
    </div>

    <div class="container">
        
        <a href="staff_dashboard.php" class="back-link">&leftarrow; Back to Dashboard</a>
        <h2>Customer Lookup & Account Summary</h2>

        <?php if ($message): ?>
            <div class="alert <?php echo $message_type; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="search-card">
            <form method="GET" action="staff_customer_lookup.php" style="display: flex; gap: 15px; width: 100%;">
                <input type="text" name="search_term" placeholder="Enter Customer ID, First Name, or Last Name" value="<?php echo htmlspecialchars($search_term); ?>" required>
                <button type="submit" name="search">Search Customer</button>
            </form>
        </div>

        <?php if ($customer_data): ?>
            <h3>Customer Details: <?php echo htmlspecialchars($customer_data['first_name'] . ' ' . $customer_data['last_name']); ?> (ID: <?php echo $customer_data['cust_id']; ?>)</h3>
            
            <div class="customer-info-card">
                <div class="info-item">
                    <span class="info-label">Customer ID</span>
                    <span class="info-value"><?php echo $customer_data['cust_id']; ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Full Name</span>
                    <span class="info-value"><?php echo htmlspecialchars($customer_data['first_name'] . ' ' . $customer_data['last_name']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Date of Birth</span>
                    <span class="info-value"><?php echo htmlspecialchars($customer_data['date_of_birth']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">SSN (Last 4 Digits)</span>
                    <span class="info-value"><?php echo substr($customer_data['ssn'], -4); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Mobile Phone</span>
                    <span class="info-value"><?php echo htmlspecialchars($customer_data['mobile_no']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Home Phone</span>
                    <span class="info-value"><?php echo htmlspecialchars($customer_data['phone_number']); ?></span>
                </div>
                <div class="info-item" style="grid-column: span 2;">
                    <span class="info-label">Address</span>
                    <span class="info-value"><?php echo htmlspecialchars($customer_data['address']) . ', ' . htmlspecialchars($customer_data['city']); ?></span>
                </div>
            </div>

            <h3>Linked Accounts</h3>
            <?php if (!empty($customer_accounts)): ?>
                <table class="account-table">
                    <thead>
                        <tr>
                            <th>Account Type</th>
                            <th>Account Number</th>
                            <th>Balance</th>
                            <th>Date Opened</th>
                            <th>Branch</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $total_customer_balance = 0;
                        foreach ($customer_accounts as $account): 
                            $total_customer_balance += $account['balance'];
                        ?>
                            <tr>
                                <td><?php echo htmlspecialchars($account['account_type']); ?></td>
                                <td><?php echo htmlspecialchars($account['account_number']); ?></td>
                                <td class="balance-col"><?php echo format_currency($account['balance']); ?></td>
                                <td><?php echo htmlspecialchars($account['date_opened']); ?></td>
                                <td><?php echo htmlspecialchars($account['branch_name']) . ' (' . htmlspecialchars($account['branch_city']) . ')'; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="2" style="text-align: right; font-weight: 700;">Total Holdings:</td>
                            <td class="balance-col" style="font-size: 1.2em;"><?php echo format_currency($total_customer_balance); ?></td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                </table>
            <?php else: ?>
                <div class="alert info">
                    This customer currently has no active bank accounts linked.
                </div>
            <?php endif; ?>

        <?php elseif ($search_executed): ?>
            <div class="alert error">
                Search complete. No single customer match found for "<?php echo htmlspecialchars($search_term); ?>".
            </div>
        <?php endif; ?>
        
        <div style="height: 40px;"></div>

    </div>

</body>
</html>
