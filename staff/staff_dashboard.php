<?php
session_start();

// Security Check: If the staff member is not logged in, redirect to login page
if (!isset($_SESSION['staff_id'])) {
    header("Location: staff_login.php");
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

$staff_id = $_SESSION['staff_id'];
$staff_position = $_SESSION['staff_position'];
$staff_data = [];
$kpi_data = [
    'total_customers' => 0,
    'total_accounts' => 0,
    'total_assets' => 0.00,
    'total_loans' => 0.00
];

// Function to log out the user
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: staff_login.php");
    exit();
}

// -----------------------------------------------------
// 1. Fetch Staff Member Details
// -----------------------------------------------------
$sql_staff = "SELECT first_name, last_name, branch_id FROM staff WHERE staff_id = ?";
$stmt_staff = $conn->prepare($sql_staff);
$stmt_staff->bind_param("i", $staff_id);
$stmt_staff->execute();
$result_staff = $stmt_staff->get_result();

if ($result_staff->num_rows > 0) {
    $staff_data = $result_staff->fetch_assoc();
    $full_name = htmlspecialchars($staff_data['first_name'] . ' ' . $staff_data['last_name']);
    $branch_id = htmlspecialchars($staff_data['branch_id']);
} else {
    // Critical error: Staff member not found. Force logout.
    session_destroy();
    header("Location: staff_login.php");
    exit();
}
$stmt_staff->close();

// -----------------------------------------------------
// 2. Fetch Bank-wide KPIs
// -----------------------------------------------------

// Total Customers
$sql_cust_count = "SELECT COUNT(cust_id) AS total_customers FROM customer";
$result_cust = $conn->query($sql_cust_count);
$kpi_data['total_customers'] = $result_cust->fetch_assoc()['total_customers'];

// Total Accounts
$sql_acc_count = "SELECT COUNT(account_number) AS total_accounts FROM account";
$result_acc = $conn->query($sql_acc_count);
$kpi_data['total_accounts'] = $result_acc->fetch_assoc()['total_accounts'];

// Total Bank Assets (sum of assets across all branches)
$sql_assets_sum = "SELECT SUM(assets) AS total_assets FROM branch";
$result_assets = $conn->query($sql_assets_sum);
$kpi_data['total_assets'] = $result_assets->fetch_assoc()['total_assets'] ?? 0.00;

// Total Bank Loans
$sql_loans_sum = "SELECT SUM(amount) AS total_loans FROM loan";
$result_loans = $conn->query($sql_loans_sum);
$kpi_data['total_loans'] = $result_loans->fetch_assoc()['total_loans'] ?? 0.00;


$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Dashboard - <?php echo $staff_position; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        /* General Reset and Typography */
        body { font-family: 'Inter', sans-serif; background-color: #f0f4f8; color: #34495e; margin: 0; padding: 0; }
        
        /* Header & Navigation */
        .header { 
            background-color: #004c8c; /* Deep Corporate Blue */
            color: white; 
            padding: 15px 40px; 
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
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
        .container { max-width: 1200px; margin: 30px auto; padding: 0 20px; }
        .welcome-card {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%); 
            color: white;
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 30px;
            box-shadow: 0 8px 20px rgba(0, 76, 140, 0.4);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .welcome-info {
            flex-grow: 1;
        }
        .welcome-message { font-size: 2em; font-weight: 700; margin-bottom: 5px; }
        .welcome-sub { font-size: 1.1em; opacity: 0.9; font-weight: 500;}
        .staff-position { 
            background-color: #2ecc71; 
            padding: 5px 15px; 
            border-radius: 20px; 
            font-weight: 700; 
            font-size: 0.9em;
            text-transform: uppercase;
        }

        /* KPIs Grid */
        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        .kpi-card {
            background-color: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
            border-left: 5px solid #007bff;
        }
        .kpi-title {
            font-size: 0.9em;
            color: #7f8c8d;
            margin-bottom: 5px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .kpi-value {
            font-size: 2.2em;
            font-weight: 800;
            color: #004c8c;
        }

        /* Quick Actions Grid */
        h2 { 
            color: #004c8c; 
            border-bottom: 3px solid #e0e6ed; 
            padding-bottom: 8px; 
            margin-bottom: 25px; 
            font-weight: 700;
            font-size: 1.6em;
        }
        .actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        .action-card {
            background-color: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            text-decoration: none;
            color: #007bff;
            font-weight: 600;
            text-align: center;
            border: 1px solid #e0e6ed;
            transition: box-shadow 0.3s, border-color 0.3s, transform 0.1s;
        }
        .action-card:hover {
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
            border-color: #007bff;
            transform: translateY(-3px);
        }
        .action-icon {
            font-size: 3em;
            margin-bottom: 10px;
            color: #007bff;
        }
        .action-label {
            display: block;
            font-size: 1.1em;
            margin-top: 5px;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .header { padding: 15px 20px; }
            .welcome-card { flex-direction: column; align-items: flex-start; }
            .staff-position { margin-top: 15px; }
            .welcome-message { font-size: 1.6em; }
            .kpi-grid, .actions-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

    <div class="header">
        <div class="logo">
            <h1>BANK STAFF PORTAL</h1>
        </div>
        <div>
             <a href="staff_dashboard.php?logout=true" class="logout-btn">Log Out</a>
        </div>
    </div>

    <div class="container">
        
        <div class="welcome-card">
            <div class="welcome-info">
                <div class="welcome-sub">Welcome,</div>
                <div class="welcome-message">
                    <?php echo $full_name; ?>
                </div>
                <div class="welcome-sub">Staff ID: <?php echo $staff_id; ?> | Branch: <?php echo $branch_id; ?></div>
            </div>
            <div class="staff-position">
                <?php echo $staff_position; ?>
            </div>
        </div>

        <!-- Section 1: Bank-wide Metrics (KPIs) -->
        <h2>Bank Performance Overview</h2>
        <div class="kpi-grid">
            <div class="kpi-card">
                <div class="kpi-title">Total Customers</div>
                <div class="kpi-value"><?php echo number_format($kpi_data['total_customers']); ?></div>
            </div>
            <div class="kpi-card">
                <div class="kpi-title">Total Accounts</div>
                <div class="kpi-value"><?php echo number_format($kpi_data['total_accounts']); ?></div>
            </div>
            <div class="kpi-card">
                <div class="kpi-title">Total Assets (USD)</div>
                <div class="kpi-value">$<?php echo number_format($kpi_data['total_assets'], 2); ?></div>
            </div>
            <div class="kpi-card">
                <div class="kpi-title">Total Loans (USD)</div>
                <div class="kpi-value">$<?php echo number_format($kpi_data['total_loans'], 2); ?></div>
            </div>
        </div>

        <!-- Section 2: Role-Based Quick Actions -->
        <h2>Quick Actions for <?php echo $staff_position; ?></h2>
        <div class="actions-grid">
            
            <?php if ($staff_position === 'Teller' || $staff_position === 'Loan Officer' || $staff_position === 'Manager'): ?>
                <!-- Basic tool for all customer-facing roles -->
                <a href="staff_customer_lookup.php" class="action-card">
                    <span class="action-icon">🔍</span>
                    <span class="action-label">Customer Lookup</span>
                </a>
            <?php endif; ?>

            <?php if ($staff_position === 'Teller'): ?>
                <!-- Teller-specific actions -->
                <a href="staff_transaction_entry.php" class="action-card">
                    <span class="action-icon">💰</span>
                    <span class="action-label">Process Deposit/Withdrawal</span>
                </a>
                <a href="staff_new_account.php" class="action-card">
                    <span class="action-icon">💳</span>
                    <span class="action-label">Open New Account</span>
                </a>
            <?php endif; ?>

            <?php if ($staff_position === 'Loan Officer'): ?>
                <!-- Loan Officer-specific actions -->
                <a href="staff_loan_review.php" class="action-card">
                    <span class="action-icon">📝</span>
                    <span class="action-label">Review Loan Applications</span>
                </a>
                <a href="staff_loan_disbursement.php" class="action-card">
                    <span class="action-icon">💵</span>
                    <span class="action-label">Disburse Funds</span>
                </a>
            <?php endif; ?>

            <?php if ($staff_position === 'Manager'): ?>
                <!-- Manager-specific actions -->
                <a href="staff_audit_log.php" class="action-card">
                    <span class="action-icon">🛡️</span>
                    <span class="action-label">View Audit Log</span>
                </a>
                <a href="staff_branch_reports.php" class="action-card">
                    <span class="action-icon">📊</span>
                    <span class="action-label">Branch & Staff Reports</span>
                </a>
            <?php endif; ?>
        </div>
        
        <div style="height: 40px;"></div>

    </div>

</body>
</html>
