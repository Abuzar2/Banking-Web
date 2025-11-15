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
$message = '';
$message_type = '';
$transactions = [];
$account_filter = isset($_GET['account']) ? $_GET['account'] : 'all';

// Fetch customer's accounts for filter dropdown
$sql_accounts = "SELECT a.account_number, a.account_type, a.balance 
                 FROM account a 
                 JOIN customer_account ca ON a.account_number = ca.account_number 
                 WHERE ca.cust_id = ? 
                 ORDER BY a.account_type, a.account_number";
$stmt_accounts = $conn->prepare($sql_accounts);
$stmt_accounts->bind_param("i", $cust_id);
$stmt_accounts->execute();
$customer_accounts = $stmt_accounts->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_accounts->close();

// Fetch transactions based on filter
if ($account_filter === 'all') {
    // Get all transactions for all customer accounts
    $sql_transactions = "SELECT t.trans_id, t.account_number, t.trans_type, t.amount, 
                                t.trans_date, t.description, a.account_type
                         FROM transaction t
                         JOIN account a ON t.account_number = a.account_number
                         WHERE t.account_number IN (
                             SELECT account_number FROM customer_account WHERE cust_id = ?
                         )
                         ORDER BY t.trans_date DESC, t.trans_id DESC";
    $stmt_transactions = $conn->prepare($sql_transactions);
    $stmt_transactions->bind_param("i", $cust_id);
} else {
    // Get transactions for specific account
    $sql_transactions = "SELECT t.trans_id, t.account_number, t.trans_type, t.amount, 
                                t.trans_date, t.description, a.account_type
                         FROM transaction t
                         JOIN account a ON t.account_number = a.account_number
                         WHERE t.account_number = ? AND t.account_number IN (
                             SELECT account_number FROM customer_account WHERE cust_id = ?
                         )
                         ORDER BY t.trans_date DESC, t.trans_id DESC";
    $stmt_transactions = $conn->prepare($sql_transactions);
    $stmt_transactions->bind_param("ii", $account_filter, $cust_id);
}

if ($stmt_transactions->execute()) {
    $result = $stmt_transactions->get_result();
    $transactions = $result->fetch_all(MYSQLI_ASSOC);
} else {
    $message = "Error fetching transactions: " . $stmt_transactions->error;
    $message_type = 'error';
}
$stmt_transactions->close();
$conn->close();

// Function to format amount with color based on transaction type
function formatAmount($type, $amount) {
    $formatted = number_format($amount, 2);
    if ($type === 'Deposit') {
        return '<span style="color: #27ae60;">+$' . $formatted . '</span>';
    } elseif ($type === 'Withdrawal') {
        return '<span style="color: #e74c3c;">-$' . $formatted . '</span>';
    } else {
        return '<span style="color: #3498db;">$' . $formatted . '</span>';
    }
}

// Function to format transaction type with badge
function formatTransactionType($type) {
    $colors = [
        'Deposit' => '#27ae60',
        'Withdrawal' => '#e74c3c',
        'Transfer' => '#3498db'
    ];
    $color = $colors[$type] ?? '#95a5a6';
    return '<span style="background-color: ' . $color . '; color: white; padding: 4px 8px; border-radius: 12px; font-size: 0.85em; font-weight: 600;">' . $type . '</span>';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaction History - Secure Banking Portal</title>
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
        .container { max-width: 1200px; margin: 30px auto; padding: 0 20px; }
        
        h2 { 
            color: #004c8c; 
            border-bottom: 3px solid #e0e6ed; 
            padding-bottom: 8px; 
            margin-bottom: 25px; 
            font-weight: 700;
            font-size: 1.8em;
        }

        /* Filter Section */
        .filter-section {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .filter-section label {
            font-weight: 600;
            color: #555;
        }
        .filter-section select {
            padding: 8px 12px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 1em;
        }
        .apply-btn {
            background-color: #007bff;
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 600;
        }
        .apply-btn:hover {
            background-color: #0056b3;
        }

        /* Transactions Card */
        .transactions-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            border: 1px solid #e0e6ed;
            overflow: hidden;
        }

        /* Transactions Table */
        .transactions-table {
            width: 100%;
            border-collapse: collapse;
        }
        .transactions-table th {
            background-color: #f8f9fa;
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: #495057;
            border-bottom: 2px solid #e9ecef;
        }
        .transactions-table td {
            padding: 15px;
            border-bottom: 1px solid #e9ecef;
            vertical-align: top;
        }
        .transactions-table tr:hover {
            background-color: #f8f9fa;
        }
        .transactions-table tr:last-child td {
            border-bottom: none;
        }

        /* Account Badge */
        .account-badge {
            background-color: #e9ecef;
            color: #495057;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.85em;
            font-weight: 500;
        }

        /* No Transactions Message */
        .no-transactions {
            text-align: center;
            padding: 40px;
            color: #6c757d;
        }
        .no-transactions i {
            font-size: 3em;
            margin-bottom: 15px;
            display: block;
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

        /* Responsive Design */
        @media (max-width: 768px) {
            .container {
                padding: 0 15px;
            }
            .transactions-table {
                font-size: 0.9em;
            }
            .transactions-table th,
            .transactions-table td {
                padding: 10px 8px;
            }
            .filter-section {
                flex-direction: column;
                align-items: stretch;
            }
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
            <a href="customer_manage_profile.php">My Profile</a>
        </div>
    </div>

    <div class="container">
        
        <a href="customer_dashboard.php" class="back-link">&leftarrow; Back to Dashboard</a>
        <h2>Transaction History</h2>

        <?php if ($message): ?>
            <div class="alert <?php echo $message_type; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <!-- Filter Section -->
        <div class="filter-section">
            <label for="account_filter">Filter by Account:</label>
            <select id="account_filter" name="account_filter">
                <option value="all" <?php echo $account_filter === 'all' ? 'selected' : ''; ?>>All Accounts</option>
                <?php foreach ($customer_accounts as $account): ?>
                    <option value="<?php echo $account['account_number']; ?>" 
                            <?php echo $account_filter == $account['account_number'] ? 'selected' : ''; ?>>
                        <?php echo $account['account_type'] . ' - ' . $account['account_number'] . ' ($' . number_format($account['balance'], 2) . ')'; ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button class="apply-btn" onclick="applyFilter()">Apply Filter</button>
        </div>

        <!-- Transactions Table -->
        <div class="transactions-card">
            <?php if (!empty($transactions)): ?>
                <table class="transactions-table">
                    <thead>
                        <tr>
                            <th>Date & Time</th>
                            <th>Transaction ID</th>
                            <th>Account</th>
                            <th>Type</th>
                            <th>Description</th>
                            <th>Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($transactions as $transaction): ?>
                            <tr>
                                <td>
                                    <strong><?php echo date('M j, Y', strtotime($transaction['trans_date'])); ?></strong><br>
                                    <small style="color: #6c757d;"><?php echo date('g:i A', strtotime($transaction['trans_date'])); ?></small>
                                </td>
                                <td>#<?php echo $transaction['trans_id']; ?></td>
                                <td>
                                    <div class="account-badge">
                                        <?php echo $transaction['account_type'] . '<br>'; ?>
                                        <small>****<?php echo substr($transaction['account_number'], -4); ?></small>
                                    </div>
                                </td>
                                <td><?php echo formatTransactionType($transaction['trans_type']); ?></td>
                                <td><?php echo htmlspecialchars($transaction['description'] ?? 'No description'); ?></td>
                                <td style="font-weight: 600; text-align: right;">
                                    <?php echo formatAmount($transaction['trans_type'], $transaction['amount']); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-transactions">
                    <i>📊</i>
                    <h3>No Transactions Found</h3>
                    <p>There are no transactions to display for the selected account filter.</p>
                </div>
            <?php endif; ?>
        </div>
        
        <div style="height: 40px;"></div>

    </div>

    <script>
        function applyFilter() {
            const accountFilter = document.getElementById('account_filter').value;
            window.location.href = `transaction_history.php?account=${accountFilter}`;
        }

        // Auto-submit form when filter changes
        document.getElementById('account_filter').addEventListener('change', function() {
            applyFilter();
        });
    </script>

</body>
</html>