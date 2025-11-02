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
// NOTE: Ensure 'cust_name' is set during login for this to work.
$cust_name = $_SESSION['cust_name'] ?? "Valued Customer"; 
$accounts_data = [];

// -----------------------------------------------------
// 1. Fetch Accounts and Balances
// -----------------------------------------------------
// Join CUSTOMER_ACCOUNT (CA) to ACCOUNT (A) using account_number
$sql_accounts = "
    SELECT 
        A.account_number,
        A.account_type,
        A.balance,
        A.branch_id
    FROM 
        ACCOUNT A
    JOIN 
        customer_account CA ON A.account_number = CA.account_number
    WHERE 
        CA.cust_id = ?
    ORDER BY 
        A.account_type DESC, A.account_number ASC
";

$stmt = $conn->prepare($sql_accounts);
if (!$stmt) {
    // Handle prepare error gracefully
    error_log("Failed to prepare SQL statement: " . $conn->error);
    // You might want to redirect to an error page here.
} else {
    $stmt->bind_param("i", $cust_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $accounts_data[] = $row;
        }
    }
    $stmt->close();
}

$conn->close();

// Function to log out the user
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: customer_login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Dashboard - Secure Banking Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* General Reset and Typography */
        body { font-family: 'Inter', sans-serif; background-color: #f4f6f9; color: #34495e; margin: 0; padding: 0; }
        
        /* Header & Navigation */
        .header { 
            background-color: #004c8c; /* Deep Corporate Blue */
            color: white; 
            padding: 15px 40px; 
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        .logo h1 { font-size: 1.5em; font-weight: 700; margin: 0; }
        .logout-btn { 
            background-color: #dc3545; 
            color: white; 
            padding: 8px 15px; 
            text-decoration: none; 
            border-radius: 6px; 
            font-weight: 500;
            transition: background-color 0.3s, transform 0.1s;
        }
        .logout-btn:hover { 
            background-color: #c82333; 
            transform: translateY(-1px);
        }

        /* Main Container and Welcome Card */
        .container { max-width: 1100px; margin: 30px auto; padding: 0 20px; }
        .welcome-card {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%); /* Blue Gradient */
            color: white;
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 30px;
            box-shadow: 0 6px 15px rgba(0, 76, 140, 0.3);
        }
        .welcome-message { font-size: 1.8em; font-weight: 600; margin-bottom: 5px; }
        .welcome-sub { font-size: 1em; opacity: 0.9; }

        /* Section Headings */
        h2 { 
            color: #004c8c; 
            border-bottom: 3px solid #e0e6ed; 
            padding-bottom: 8px; 
            margin-bottom: 25px; 
            font-weight: 700;
            font-size: 1.6em;
        }

        /* Account Table Styling */
        .account-table { 
            width: 100%; 
            border-collapse: separate; /* Use separate for border-radius */
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
            background-color: #007bff; /* Bright Blue Header */
            color: white; 
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.9em;
        }
        .account-table tbody tr:hover { 
            background-color: #f0f3f6; /* Subtle row hover */
        }
        .account-table tr:last-child td { border-bottom: none; }
        
        .account-type { font-weight: 500; color: #007bff; }
        .account-number { font-weight: 700; color: #34495e; font-family: monospace; }
        .balance-col { font-weight: 700; color: #2ecc71; /* Success Green */ font-size: 1.2em; }

        /* Footer Row for Total Holdings */
        .total-row th, .total-row td {
            background-color: #dbe9f6 !important; /* Light blue background for emphasis */
            color: #004c8c;
            font-weight: 700;
            border-top: 2px solid #007bff;
        }
        .total-balance-value {
            font-size: 1.4em;
            color: #28a745; /* Green for total money */
        }

        /* Quick Actions Grid */
        .actions-grid {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            margin-bottom: 40px;
        }
        .action-card {
            flex: 1 1 200px;
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
            text-decoration: none;
            color: #007bff;
            font-weight: 600;
            text-align: center;
            border: 1px solid #e0e6ed;
            transition: box-shadow 0.3s, border-color 0.3s, transform 0.1s;
        }
        .action-card:hover {
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            border-color: #007bff;
            transform: translateY(-2px);
        }
        .action-icon {
            font-size: 2em;
            margin-bottom: 10px;
            color: #007bff;
        }

        /* Utility Styles for Responsiveness */
        @media (max-width: 600px) {
            .header { padding: 15px 20px; }
            .logo h1 { font-size: 1.3em; }
            .container { margin: 20px 0; padding: 0 15px; }
            .account-table th, .account-table td { padding: 10px; font-size: 0.9em; }
            .welcome-card { padding: 20px; }
            .welcome-message { font-size: 1.4em; }
            .actions-grid { justify-content: center; }
        }
    </style>
</head>
<body>

    <div class="header">
        <div class="logo">
            <h1>SECURE BANKING PORTAL</h1>
        </div>
        <div>
             <a href="customer_dashboard.php?logout=true" class="logout-btn">Log Out</a>
        </div>
    </div>

    <div class="container">
        
        <div class="welcome-card">
            <div class="welcome-sub">Welcome Back,</div>
            <div class="welcome-message">
                <?php echo htmlspecialchars($cust_name); ?>
            </div>
            <div class="welcome-sub">Customer ID: <?php echo $cust_id; ?> | Session secured.</div>
        </div>

        <h2>Quick Access</h2>
        <div class="actions-grid">
            <a href="customer_transactions.php" class="action-card">
                <span class="action-icon">🧾</span>
                View Transaction History
            </a>
            <a href="customer_transfer.php" class="action-card">
                <span class="action-icon">💸</span>
                Transfer Funds
            </a>
            <a href="customer_pay_bills.php" class="action-card">
                <span class="action-icon">💳</span>
                Pay Bills
            </a>
            <a href="customer_manage_profile.php" class="action-card">
                <span class="action-icon">⚙️</span>
                Manage Profile
            </a>
        </div>


        <h2>Account Summary</h2>

        <?php if (!empty($accounts_data)): ?>
            <table class="account-table">
                <thead>
                    <tr>
                        <th>Account Type</th>
                        <th>Account Number</th>
                        <th>Branch ID</th>
                        <th>Current Balance</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $total_balance = 0;
                    foreach ($accounts_data as $account): 
                        $total_balance += $account['balance'];
                    ?>
                        <tr>
                            <td class="account-type"><?php echo htmlspecialchars($account['account_type']); ?></td>
                            <td class="account-number"><?php echo htmlspecialchars($account['account_number']); ?></td>
                            <td><?php echo htmlspecialchars($account['branch_id']); ?></td>
                            <td class="balance-col">$<?php echo number_format($account['balance'], 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr class="total-row">
                        <th colspan="3" style="text-align: right;">Total Portfolio Holdings:</th>
                        <td class="total-balance-value">$<?php echo number_format($total_balance, 2); ?></td>
                    </tr>
                </tfoot>
            </table>
        <?php else: ?>
            <div style="padding: 20px; border: 1px solid #ffeeba; background-color: #fff3cd; color: #856404; border-radius: 8px;">
                <p style="margin: 0; font-weight: 500;">Notice: You currently have no bank accounts linked to this profile. Please contact customer support to set up your accounts.</p>
            </div>
        <?php endif; ?>
        
        <div style="height: 40px;"></div>

    </div>

</body>
</html>
