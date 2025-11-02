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
$source_accounts = [];
$message = '';
$message_type = ''; // 'success' or 'error'

// Placeholder for Billers (In a real system, this would come from a BILLER table)
$billers = [
    1 => 'Power & Light Co.',
    2 => 'City Water Services',
    3 => 'Internet & Cable Provider',
    4 => 'University Tuition Fee',
    5 => 'Bank Loan Repayment',
];

// -----------------------------------------------------
// 1. Fetch Source Accounts for dropdown
// -----------------------------------------------------
$sql_source_accounts = "
    SELECT 
        A.account_number,
        A.account_type,
        A.balance
    FROM 
        ACCOUNT A
    JOIN 
        customer_account CA ON A.account_number = CA.account_number
    WHERE 
        CA.cust_id = ?
    ORDER BY 
        A.account_type, A.account_number
";
$stmt_accounts = $conn->prepare($sql_source_accounts);
$stmt_accounts->bind_param("i", $cust_id);
$stmt_accounts->execute();
$result_accounts = $stmt_accounts->get_result();
while ($row = $result_accounts->fetch_assoc()) {
    $source_accounts[] = $row;
}
$stmt_accounts->close();


// -----------------------------------------------------
// 2. Handle Bill Payment Request (POST)
// -----------------------------------------------------
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['pay_bill'])) {
    
    // Sanitize and validate inputs
    $source_account = filter_input(INPUT_POST, 'source_account', FILTER_VALIDATE_INT);
    $biller_id = filter_input(INPUT_POST, 'biller_id', FILTER_VALIDATE_INT);
    $amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT, ['options' => ['min_range' => 0.01]]);
    $reference = htmlspecialchars($_POST['reference'] ?? 'N/A');

    // Basic validation
    if (!$source_account || !$biller_id || !$amount) {
        $message = "Invalid account, biller, or amount provided.";
        $message_type = 'error';
    } elseif (!isset($billers[$biller_id])) {
        $message = "Selected biller is invalid.";
        $message_type = 'error';
    } else {
        $biller_name = $billers[$biller_id];

        // Start database transaction
        $conn->begin_transaction();
        
        try {
            // --- Step 2a: Check Source Account, Balance, and Lock Row ---
            $sql_check_source = "SELECT balance FROM account WHERE account_number = ? AND account_number IN (SELECT account_number FROM customer_account WHERE cust_id = ?) FOR UPDATE";
            $stmt_check = $conn->prepare($sql_check_source);
            $stmt_check->bind_param("ii", $source_account, $cust_id);
            $stmt_check->execute();
            $result_check = $stmt_check->get_result();
            
            if ($result_check->num_rows === 0) {
                throw new Exception("Source account is invalid or does not belong to you.");
            }
            $row_source = $result_check->fetch_assoc();
            $current_balance = $row_source['balance'];
            
            if ($current_balance < $amount) {
                throw new Exception("Insufficient funds in source account. Current balance: $" . number_format($current_balance, 2));
            }
            $stmt_check->close();

            // --- Step 2b: Debit Source Account ---
            $sql_debit = "UPDATE account SET balance = balance - ? WHERE account_number = ?";
            $stmt_debit = $conn->prepare($sql_debit);
            $stmt_debit->bind_param("di", $amount, $source_account);
            if (!$stmt_debit->execute()) {
                throw new Exception("Failed to process payment from source account.");
            }
            $stmt_debit->close();

            // --- Step 2c: Log Transaction (Debit/Payment) ---
            $description = "Bill Payment to " . $biller_name . " - Ref: " . $reference;
            $sql_log_debit = "INSERT INTO `transaction` (account_number, trans_type, amount, trans_date, description) VALUES (?, 'Withdrawal', ?, NOW(), ?)";
            $stmt_log_debit = $conn->prepare($sql_log_debit);
            $stmt_log_debit->bind_param("ids", $source_account, $amount, $description);
            $stmt_log_debit->execute();
            $stmt_log_debit->close();

            // All steps complete, commit the transaction
            $conn->commit();
            $message = "Payment of \$" . number_format($amount, 2) . " to **" . $biller_name . "** successful. Reference: " . htmlspecialchars($reference);
            $message_type = 'success';

        } catch (Exception $e) {
            // Rollback if any step fails
            $conn->rollback();
            $message = "Payment failed: " . $e->getMessage();
            $message_type = 'error';
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
    <title>Pay Bills - Secure Banking Portal</title>
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
        .pay-card {
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
        .form-group input, .form-group select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 6px;
            box-sizing: border-box;
            font-size: 1em;
            transition: border-color 0.3s, box-shadow 0.3s;
        }
        .form-group input:focus, .form-group select:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.25);
            outline: none;
        }
        .submit-btn {
            background-color: #007bff; /* Primary Blue */
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
            background-color: #0056b3;
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
        <h2>Initiate Bill Payment</h2>

        <?php if ($message): ?>
            <div class="alert <?php echo $message_type; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="pay-card">

            <?php if (empty($source_accounts)): ?>
                <div class="alert error">
                    No active accounts found to make a payment.
                </div>
            <?php else: ?>
                <form method="POST" action="customer_pay_bills.php">
                    
                    <div class="form-group">
                        <label for="source_account">Pay From Account</label>
                        <select id="source_account" name="source_account" required>
                            <option value="">Select Account</option>
                            <?php foreach ($source_accounts as $account): ?>
                                <option value="<?php echo $account['account_number']; ?>">
                                    <?php echo htmlspecialchars($account['account_type']) . ' (' . $account['account_number'] . ') - Bal: $' . number_format($account['balance'], 2); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="biller_id">Select Biller</label>
                        <select id="biller_id" name="biller_id" required>
                            <option value="">Select Biller/Service</option>
                            <?php foreach ($billers as $id => $name): ?>
                                <option value="<?php echo $id; ?>">
                                    <?php echo htmlspecialchars($name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="amount">Amount Due ($)</label>
                        <input type="number" id="amount" name="amount" step="0.01" min="0.01" placeholder="0.00" required>
                    </div>

                    <div class="form-group">
                        <label for="reference">Biller Account/Reference Number</label>
                        <input type="text" id="reference" name="reference" maxlength="50" placeholder="e.g., Customer ID, Invoice No." required>
                    </div>

                    <div class="form-group">
                        <button type="submit" name="pay_bill" class="submit-btn">
                            Process Payment
                        </button>
                    </div>
                </form>
            <?php endif; ?>

        </div>
        
        <div style="height: 40px;"></div>

    </div>

</body>
</html>