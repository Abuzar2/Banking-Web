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

$staff_id = $_SESSION['staff_id'];
$message = '';
$message_type = '';
$last_new_account_number = '';

// Helper function to generate a unique 10-digit account number (Basic pseudo-random generation)
function generate_account_number($conn) {
    do {
        // Generate a 10-digit number (e.g., between 1,000,000,000 and 9,999,999,999)
        $account_number = mt_rand(1000000000, 9999999999);
        $stmt = $conn->prepare("SELECT account_number FROM account WHERE account_number = ?");
        $stmt->bind_param("s", $account_number);
        $stmt->execute();
        $result = $stmt->get_result();
        $is_duplicate = $result->num_rows > 0;
        $stmt->close();
    } while ($is_duplicate);
    return $account_number;
}


// -----------------------------------------------------
// 1. Handle Account Creation Request (POST)
// -----------------------------------------------------
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['open_account'])) {
    
    // Sanitize and validate inputs
    $cust_id = filter_var($_POST['cust_id'], FILTER_VALIDATE_INT);
    $account_type = htmlspecialchars($_POST['account_type']);
    $initial_deposit = filter_var($_POST['initial_deposit'], FILTER_VALIDATE_FLOAT);
    $branch_id = filter_var($_POST['branch_id'], FILTER_VALIDATE_INT);

    if (!$cust_id || !$branch_id || !in_array($account_type, ['Savings', 'Checking']) || $initial_deposit === false || $initial_deposit < 0) {
        $message = "Invalid input. Please ensure Customer ID, Branch ID, Account Type, and Initial Deposit (must be non-negative) are correct.";
        $message_type = 'error';
    } else {
        // --- Validation 1: Does the customer exist? ---
        $stmt_cust = $conn->prepare("SELECT cust_id FROM customer WHERE cust_id = ?");
        $stmt_cust->bind_param("i", $cust_id);
        $stmt_cust->execute();
        if ($stmt_cust->get_result()->num_rows === 0) {
            $message = "Customer ID **$cust_id** not found. Please verify the customer before opening an account.";
            $message_type = 'error';
            $stmt_cust->close();
        } else {
            $stmt_cust->close();

            // --- Validation 2: Does the branch exist? ---
            $stmt_branch = $conn->prepare("SELECT branch_id FROM branch WHERE branch_id = ?");
            $stmt_branch->bind_param("i", $branch_id);
            $stmt_branch->execute();
            if ($stmt_branch->get_result()->num_rows === 0) {
                $message = "Branch ID **$branch_id** not found. Please verify the branch details.";
                $message_type = 'error';
                $stmt_branch->close();
            } else {
                $stmt_branch->close();

                // --- Begin Atomic Transaction ---
                $conn->begin_transaction();
                $success = false;

                try {
                    // Generate Unique Account Number
                    $new_account_number = generate_account_number($conn);

                    // --- Step A: Insert into ACCOUNT table ---
                    $sql_acc = "INSERT INTO account (account_number, account_type, balance, date_opened, branch_id) VALUES (?, ?, ?, CURDATE(), ?)";
                    $stmt_acc = $conn->prepare($sql_acc);
                    $stmt_acc->bind_param("isdi", $new_account_number, $account_type, $initial_deposit, $branch_id);
                    $stmt_acc->execute();
                    
                    if ($stmt_acc->affected_rows !== 1) {
                        throw new Exception("Account table insertion failed.");
                    }
                    $stmt_acc->close();

                    // --- Step B: Insert into CUSTOMER_ACCOUNT table (Link Customer to Account) ---
                    $sql_link = "INSERT INTO customer_account (cust_id, account_number) VALUES (?, ?)";
                    $stmt_link = $conn->prepare($sql_link);
                    $stmt_link->bind_param("ii", $cust_id, $new_account_number);
                    $stmt_link->execute();
                    
                    if ($stmt_link->affected_rows !== 1) {
                        throw new Exception("Customer-Account linking failed.");
                    }
                    $stmt_link->close();

                    // --- Step C: Log the Audit Event ---
                    $user_role = $_SESSION['staff_position'];
                    $action_type = "ACCOUNT_OPEN";
                    $details = "Opened new $account_type account (No: $new_account_number) for Customer ID $cust_id with initial deposit of $initial_deposit at Branch $branch_id.";
                    $ip_address = $_SERVER['REMOTE_ADDR'];
                    
                    $sql_audit = "INSERT INTO audit_log (timestamp, user_role, user_id, action_type, details, ip_address) VALUES (NOW(), ?, ?, ?, ?, ?)";
                    $stmt_audit = $conn->prepare($sql_audit);
                    $stmt_audit->bind_param("sisss", $user_role, $staff_id, $action_type, $details, $ip_address);
                    $stmt_audit->execute();
                    $stmt_audit->close();
                    
                    // --- Commit Transaction and Set Success ---
                    $conn->commit();
                    $success = true;
                    $last_new_account_number = $new_account_number;
                    $message = "🎉 **Success!** A new **$account_type** account has been opened for Customer ID **$cust_id**.<br>New Account Number: **$new_account_number**.<br>Initial Balance: $" . number_format($initial_deposit, 2);
                    $message_type = 'success';

                } catch (Exception $e) {
                    $conn->rollback();
                    $message = "Transaction Failed: " . $e->getMessage() . " Please try again or check database connectivity.";
                    $message_type = 'error';
                }
            }
        }
    }
}

// Fetch list of branches for the dropdown (simplified for now)
$branches = [];
$sql_branches = "SELECT branch_id, branch_name, branch_city FROM branch ORDER BY branch_id";
$result_branches = $conn->query($sql_branches);
if ($result_branches) {
    while ($row = $result_branches->fetch_assoc()) {
        $branches[] = $row;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Open New Account - Staff Portal</title>
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

        .container { max-width: 600px; margin: 30px auto; padding: 0 20px; }
        
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

        .open-btn {
            background-color: #3498db; /* Blue for New Account */
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
        .open-btn:hover { background-color: #2980b9; transform: translateY(-1px); }

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

        .required-note {
            font-size: 0.8em;
            color: #7f8c8d;
            margin-top: -10px;
            margin-bottom: 20px;
            display: block;
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
        <h2>Open New Bank Account</h2>

        <?php if ($message): ?>
            <div class="alert <?php echo $message_type; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="form-card">
            <form method="POST" action="staff_new_account.php">
                
                <p style="color: #004c8c; font-weight: 600;">Account Details</p>

                <div class="form-group">
                    <label for="cust_id">Customer ID</label>
                    <input type="number" id="cust_id" name="cust_id" placeholder="Enter existing Customer ID" required>
                    <span class="required-note">The customer must already be registered in the system.</span>
                </div>

                <div class="form-group">
                    <label for="account_type">Account Type</label>
                    <select id="account_type" name="account_type" required>
                        <option value="">-- Select Type --</option>
                        <option value="Savings">Savings</option>
                        <option value="Checking">Checking</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="initial_deposit">Initial Deposit Amount (USD)</label>
                    <input type="number" id="initial_deposit" name="initial_deposit" step="0.01" min="0" value="0.00" required>
                </div>
                
                <div class="form-group">
                    <label for="branch_id">Branch Location</label>
                    <select id="branch_id" name="branch_id" required>
                        <option value="">-- Select Branch --</option>
                        <?php foreach ($branches as $branch): ?>
                            <option value="<?php echo $branch['branch_id']; ?>">
                                <?php echo htmlspecialchars($branch['branch_name'] . ' (ID: ' . $branch['branch_id'] . ', ' . $branch['branch_city'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <button type="submit" name="open_account" class="open-btn">
                        Finalize & Open Account
                    </button>
                </div>
            </form>
        </div>
        
        <div style="height: 40px;"></div>

    </div>

</body>
</html>
