<?php
session_start();

// Security Check: Only Managers should access this.
if (!isset($_SESSION['staff_id']) || $_SESSION['staff_position'] !== 'Manager') {
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

// Helper function to format money
function format_currency($amount) {
    return '$' . number_format($amount, 2);
}

$branch_asset_summary = [];
$staff_transaction_performance = [];
$report_period_days = 30; // Standard reporting period

// -----------------------------------------------------
// 1. Report 1: Branch Asset & Loan Summary
// -----------------------------------------------------

$sql_branch_summary = "
    SELECT 
        B.branch_name,
        B.branch_city,
        B.assets,
        COALESCE(SUM(L.amount), 0.00) AS total_loans_outstanding
    FROM 
        branch B
    LEFT JOIN 
        loan L ON B.branch_id = L.branch_id
    GROUP BY 
        B.branch_id, B.branch_name, B.branch_city, B.assets
    ORDER BY 
        B.assets DESC
";

$result_summary = $conn->query($sql_branch_summary);
if ($result_summary) {
    while ($row = $result_summary->fetch_assoc()) {
        $branch_asset_summary[] = $row;
    }
}

// -----------------------------------------------------
// 2. Report 2: Staff Transaction Performance (Last 30 Days)
// -----------------------------------------------------

$date_30_days_ago = date('Y-m-d H:i:s', strtotime("-{$report_period_days} days"));

// FIXED QUERY - Using correct column names from your database
$sql_staff_performance = "
    SELECT 
        T.staff_id,
        S.first_name,
        S.last_name,
        S.position,
        COUNT(T.trans_id) AS total_transactions_processed,
        SUM(CASE WHEN T.trans_type = 'Deposit' THEN T.amount ELSE 0 END) AS total_deposits,
        SUM(CASE WHEN T.trans_type = 'Withdrawal' THEN T.amount ELSE 0 END) AS total_withdrawals
    FROM 
        transaction T
    JOIN 
        staff S ON T.staff_id = S.staff_id
    WHERE 
        T.trans_date >= ?
    GROUP BY 
        T.staff_id, S.first_name, S.last_name, S.position
    ORDER BY 
        total_transactions_processed DESC
";

$stmt_performance = $conn->prepare($sql_staff_performance);
if ($stmt_performance) {
    $stmt_performance->bind_param("s", $date_30_days_ago);
    $stmt_performance->execute();
    $result_performance = $stmt_performance->get_result();

    while ($row = $result_performance->fetch_assoc()) {
        $staff_transaction_performance[] = $row;
    }
    $stmt_performance->close();
} else {
    echo "<!-- Query Error: " . $conn->error . " -->";
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Branch & Staff Reports - Manager Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        /* General Setup */
        body { font-family: 'Inter', sans-serif; background-color: #f0f4f8; color: #34495e; margin: 0; padding: 0; }
        
        /* Header & Navigation */
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
        .nav-links a:hover { background-color: #0056b3; }

        .container { max-width: 1300px; margin: 30px auto; padding: 0 20px; }
        
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
            border-bottom: 1px solid #e0e6ed;
            padding-bottom: 5px;
            margin-top: 40px;
            margin-bottom: 20px;
            font-weight: 600;
        }
        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #007bff;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.2s;
        }
        .back-link:hover { color: #0056b3; text-decoration: underline; }

        /* Report Table Styling */
        .report-table { 
            width: 100%; 
            border-collapse: separate; 
            border-spacing: 0;
            margin-top: 10px; 
            overflow: hidden;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
            background-color: white;
        }
        .report-table th, .report-table td { 
            padding: 12px 15px; 
            text-align: left; 
            border-bottom: 1px solid #e0e6ed; 
            font-size: 0.95em;
        }
        .report-table th { 
            background-color: #5d6d7e; /* Darker blue-grey for data tables */
            color: white; 
            font-weight: 600;
            text-transform: uppercase;
        }
        .report-table tbody tr:hover { 
            background-color: #f0f3f6; 
        }
        .value-cell { font-weight: 700; }
        .deposit-cell { color: #2ecc71; }
        .withdrawal-cell { color: #e74c3c; }
        
        /* Status Messages */
        .status-message {
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
            text-align: center;
        }
        .status-info {
            background-color: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        .status-warning {
            background-color: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
    </style>
</head>
<body>

    <div class="header">
        <div class="logo">
            <h1>MANAGER PORTAL (REPORTS)</h1>
        </div>
        <div class="nav-links">
            <a href="staff_dashboard.php">Dashboard</a>
        </div>
    </div>

    <div class="container">
        
        <a href="staff_dashboard.php" class="back-link">&leftarrow; Back to Dashboard</a>
        <h2>Branch & Staff Performance Reports</h2>

        <!-- Report 1: Branch Asset Summary -->
        <h3>Report 1: Branch Financial Summary</h3>
        <p style="font-style: italic; color: #7f8c8d;">Snapshot of branch assets and total outstanding loans.</p>

        <?php if (!empty($branch_asset_summary)): ?>
            <table class="report-table">
                <thead>
                    <tr>
                        <th style="width: 20%;">Branch Name</th>
                        <th style="width: 20%;">City</th>
                        <th style="width: 30%;">Total Branch Assets</th>
                        <th style="width: 30%;">Total Loans Outstanding</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($branch_asset_summary as $branch): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($branch['branch_name']); ?></td>
                            <td><?php echo htmlspecialchars($branch['branch_city']); ?></td>
                            <td class="value-cell"><?php echo format_currency($branch['assets']); ?></td>
                            <td class="value-cell"><?php echo format_currency($branch['total_loans_outstanding']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="status-message status-warning">
                <p>No branch data found to generate the financial summary report.</p>
            </div>
        <?php endif; ?>

        <!-- Report 2: Staff Transaction Performance -->
        <h3>Report 2: Staff Transaction Performance (Last <?php echo $report_period_days; ?> Days)</h3>
        <p style="font-style: italic; color: #7f8c8d;">Summary of transactional activity processed by staff members.</p>

        <?php if (!empty($staff_transaction_performance)): ?>
            <table class="report-table">
                <thead>
                    <tr>
                        <th style="width: 15%;">Staff ID</th>
                        <th style="width: 20%;">Name</th>
                        <th style="width: 15%;">Position</th>
                        <th style="width: 20%;">Total Transactions</th>
                        <th style="width: 15%;">Total Deposited</th>
                        <th style="width: 15%;">Total Withdrawn</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($staff_transaction_performance as $staff): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($staff['staff_id']); ?></td>
                            <td><?php echo htmlspecialchars($staff['first_name'] . ' ' . $staff['last_name']); ?></td>
                            <td><?php echo htmlspecialchars($staff['position']); ?></td>
                            <td class="value-cell"><?php echo number_format($staff['total_transactions_processed']); ?></td>
                            <td class="value-cell deposit-cell"><?php echo format_currency($staff['total_deposits']); ?></td>
                            <td class="value-cell withdrawal-cell"><?php echo format_currency($staff['total_withdrawals']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="status-message status-info">
                <p>No transaction data found for the last <?php echo $report_period_days; ?> days to generate the staff performance report.</p>
                <p><small>This could be because:</small></p>
                <ul style="text-align: left; display: inline-block;">
                    <li>No transactions were processed in the last <?php echo $report_period_days; ?> days</li>
                    <li>Transactions were processed without staff assignment</li>
                    <li>Staff members haven't processed any transactions yet</li>
                </ul>
            </div>
        <?php endif; ?>
        
        <!-- Debug Information (Remove in production) -->
        <div style="margin-top: 40px; padding: 15px; background: #f8f9fa; border-radius: 8px; font-size: 0.9em; color: #6c757d;">
            <h4>Report Information:</h4>
            <p><strong>Report Period:</strong> Last <?php echo $report_period_days; ?> days (since <?php echo date('M j, Y', strtotime("-{$report_period_days} days")); ?>)</p>
            <p><strong>Branches Found:</strong> <?php echo count($branch_asset_summary); ?></p>
            <p><strong>Staff with Transactions:</strong> <?php echo count($staff_transaction_performance); ?></p>
        </div>
        
        <div style="height: 40px;"></div>

    </div>

</body>
</html>