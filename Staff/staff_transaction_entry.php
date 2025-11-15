<?php
session_start();

// Security Check: Only Tellers and Managers should access this.
if (!isset($_SESSION['staff_id']) || ($_SESSION['staff_position'] !== 'Teller' && $_SESSION['staff_position'] !== 'Manager')) {
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

$message = '';
$message_type = '';
$account_number = '';
$current_balance = null;
$customer_name = '';

// Helper function to format money
function format_currency($amount) {
    return '$' . number_format($amount, 2);
}

// -----------------------------------------------------
// 1. Handle Account Lookup
// -----------------------------------------------------
if (isset($_POST['lookup_account']) || isset($_POST['process_transaction'])) {
    $account_number = trim(htmlspecialchars($_POST['account_number'] ?? ''));

    if (!empty($account_number)) {
        // Fetch current balance and customer name (for confirmation)
        $sql_lookup = "
            SELECT 
                A.balance, 
                C.first_name, 
                C.last_name
            FROM 
                account A
            JOIN 
                customer_account CA ON A.account_number = CA.account_number
            JOIN 
                customer C ON CA.cust_id = C.cust_id
            WHERE 
                A.account_number = ?
            LIMIT 1"; // LIMIT 1 because multiple customers can share an account, but we only need one name for context

        $stmt_lookup = $conn->prepare($sql_lookup);
        $stmt_lookup->bind_param("s", $account_number);
        $stmt_lookup->execute();
        $result_lookup = $stmt_lookup->get_result();

        if ($result_lookup->num_rows > 0) {
            $data = $result_lookup->fetch_assoc();
            $current_balance = (float)$data['balance'];
            $customer_name = htmlspecialchars($data['first_name'] . ' ' . $data['last_name']);
        } else {
            $message = "Error: Account number **$account_number** not found.";
            $message_type = 'error';
        }
        $stmt_lookup->close();
    }
}

// -----------------------------------------------------
// 2. Handle Transaction Processing (POST)
// -----------------------------------------------------
if (isset($_POST['process_transaction']) && $current_balance !== null) {
    $transaction_type = $_POST['transaction_type'];
    $amount = filter_var($_POST['amount'], FILTER_VALIDATE_FLOAT);

    if ($amount === false || $amount <= 0) {
        $message = "Invalid amount specified.";
        $message_type = 'error';
    } elseif (!in_array($transaction_type, ['Deposit', 'Withdrawal'])) {
        $message = "Invalid transaction type.";
        $message_type = 'error';
    } else {
        
        // Start Transaction Block (to ensure atomicity)
        $conn->begin_transaction();
        $success = false;
        
        try {
            $new_balance = $current_balance;
            
            if ($transaction_type === 'Deposit') {
                $new_balance = $current_balance + $amount;
            } elseif ($transaction_type === 'Withdrawal') {
                if ($amount > $current_balance) {
                    throw new Exception("Insufficient funds. Withdrawal amount exceeds the current balance.");
                }
                $new_balance = $current_balance - $amount;
            }

            // --- Step 2a: Update Account Balance ---
            $sql_update = "UPDATE account SET balance = ? WHERE account_number = ?";
            $stmt_update = $conn->prepare($sql_update);
            $stmt_update->bind_param("ds", $new_balance, $account_number);
            $stmt_update->execute();
            
            if ($stmt_update->affected_rows !== 1) {
                 throw new Exception("Account update failed. Rows affected: " . $stmt_update->affected_rows);
            }
            $stmt_update->close();

            // --- Step 2b: Insert Transaction Record ---
            $staff_id = $_SESSION['staff_id'];
            $sql_insert = "INSERT INTO transaction (account_number, type, amount, transaction_date, staff_id) VALUES (?, ?, ?, NOW(), ?)";
            $stmt_insert = $conn->prepare($sql_insert);
            // Convert amount to positive for the transaction record, the type indicates direction
            $transaction_amount = abs($amount); 
            $stmt_insert->bind_param("ssdi", $account_number, $transaction_type, $transaction_amount, $staff_id);
            $stmt_insert->execute();
            
            if ($stmt_insert->affected_rows !== 1) {
                throw new Exception("Transaction record insertion failed.");
            }
            $stmt_insert->close();
            
            // --- Step 2c: Commit Transaction and Set Success Message ---
            $conn->commit();
            $success = true;
            $message = "✅ **Transaction Successful!** A **$transaction_type** of **" . format_currency($amount) . "** was processed for Account **$account_number** (Customer: $customer_name). New Balance: **" . format_currency($new_balance) . "**.";
            $message_type = 'success';
            
            // Re-fetch the new balance for the display
            $current_balance = $new_balance;
            
        } catch (Exception $e) {
            $conn->rollback();
            $message = "Transaction Failed: " . $e->getMessage();
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
    <title>Process Transaction - Staff Portal</title>
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

        .container { max-width: 800px; margin: 30px auto; padding: 0 20px; }
        
        h2 { 
            color: #004c8c; 
            border-bottom: 3px solid #e0e6ed; 
            padding-bottom: 8px; 
            margin-bottom: 25px; 
            font-weight: 700;
            font-size: 1.8em;
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

        /* Form Card */
        .form-card {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.08);
            border: 1px solid #e0e6ed;
        }
        
        .form-group { margin-bottom: 20px; }
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

        .lookup-form {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        .lookup-form input {
            flex-grow: 1;
        }
        .lookup-form button {
            background-color: #ff9800;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: background-color 0.3s;
        }
        .lookup-form button:hover { background-color: #e68900; }

        /* Current Balance Display */
        .balance-info {
            background-color: #e8f5e9; /* Light Green */
            border: 1px solid #c8e6c9;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            font-weight: 600;
        }
        .balance-info span { color: #1b5e20; }
        .balance-label { font-size: 0.9em; color: #555; }
        .balance-value { font-size: 1.5em; font-weight: 800; color: #388e3c; }


        .transaction-btn {
            background-color: #2ecc71; 
            color: white;
            padding: 15px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1.1em;
            font-weight: 700;
            width: 100%;
            transition: background-color 0.3s, transform 0.1s;
        }
        .transaction-btn:hover { background-color: #27ae60; transform: translateY(-1px); }

        /* Message Styles */
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
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

        /* Responsive */
        @media (max-width: 600px) {
            .lookup-form { flex-direction: column; }
            .lookup-form button { width: 100%; }
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
        <h2>Process Deposit or Withdrawal</h2>

        <?php if ($message): ?>
            <div class="alert <?php echo $message_type; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="form-card">
            
            <!-- Account Lookup Form -->
            <form method="POST" action="staff_transaction_entry.php" class="lookup-form">
                <input type="text" name="account_number" placeholder="Enter Account Number" value="<?php echo htmlspecialchars($account_number); ?>" required>
                <button type="submit" name="lookup_account">Lookup Account</button>
            </form>

            <?php if ($current_balance !== null): ?>
                
                <!-- Balance Display -->
                <div class="balance-info">
                    <div>
                        <span class="balance-label">Customer:</span> <?php echo $customer_name; ?>
                    </div>
                    <div>
                        <span class="balance-label">Current Balance:</span> 
                        <span class="balance-value"><?php echo format_currency($current_balance); ?></span>
                    </div>
                </div>

                <!-- Transaction Entry Form -->
                <form method="POST" action="staff_transaction_entry.php">
                    <input type="hidden" name="account_number" value="<?php echo htmlspecialchars($account_number); ?>">

                    <div class="form-group">
                        <label for="transaction_type">Transaction Type</label>
                        <select id="transaction_type" name="transaction_type" required>
                            <option value="">-- Select Type --</option>
                            <option value="Deposit">Deposit</option>
                            <option value="Withdrawal">Withdrawal</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="amount">Amount (USD)</label>
                        <input type="number" id="amount" name="amount" step="0.01" min="0.01" placeholder="e.g., 500.00" required>
                    </div>

                    <div class="form-group">
                        <button type="submit" name="process_transaction" class="transaction-btn">
                            Execute Transaction
                        </button>
                    </div>
                </form>
            <?php elseif (empty($account_number) && !isset($_POST['process_transaction'])): ?>
                <div class="alert info">
                    Please enter an account number above to begin a transaction.
                </div>
            <?php endif; ?>

        </div>
        
        <div style="height: 40px;"></div>

    </div>

</body>
</html>
