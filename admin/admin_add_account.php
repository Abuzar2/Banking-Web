<?php
// -----------------------------------------------------
// 1. SESSION START AND SECURITY CHECK
// -----------------------------------------------------
session_start();

if (!isset($_GET['welcome'])) {
    header("Location: index.html");
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

$user_first_name = htmlspecialchars($_GET['welcome']);
$message = "";
$pre_selected_cust_id = isset($_GET['cust_id']) ? intval($_GET['cust_id']) : null;

// -----------------------------------------------------
// 2. FETCH ALL CUSTOMERS AND BRANCHES FOR DROPDOWNS
// -----------------------------------------------------

// Fetch Customers
$customers = [];
$cust_result = $conn->query("SELECT cust_id, first_name, last_name FROM CUSTOMER ORDER BY last_name");
if ($cust_result->num_rows > 0) {
    while ($row = $cust_result->fetch_assoc()) {
        $customers[] = $row;
    }
}

// Fetch Branches
$branches = [];
$branch_result = $conn->query("SELECT branch_id, branch_name, branch_city FROM BRANCH ORDER BY branch_name");
if ($branch_result->num_rows > 0) {
    while ($row = $branch_result->fetch_assoc()) {
        $branches[] = $row;
    }
}

// -----------------------------------------------------
// 3. HANDLE OPEN ACCOUNT (INSERT INTO ACCOUNT & CUSTOMER_ACCOUNT)
// -----------------------------------------------------
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['open_account'])) {
    $cust_id = intval($_POST['cust_id']);
    $account_type = $_POST['account_type'];
    $initial_deposit = floatval($_POST['initial_deposit']);
    $branch_id = intval($_POST['branch_id']);
    $date_opened = date('Y-m-d');

    // Generate a unique 16-digit account number (bigint)
    $new_account_number = mt_rand(1000000000000000, 9999999999999999);

    $conn->begin_transaction();
    try {
        // 3a. Insert into ACCOUNT table
        $stmt_acc = $conn->prepare("INSERT INTO ACCOUNT (account_number, account_type, balance, date_opened, branch_id) VALUES (?, ?, ?, ?, ?)");
        $stmt_acc->bind_param("isdsi", $new_account_number, $account_type, $initial_deposit, $date_opened, $branch_id);
        
        if (!$stmt_acc->execute()) {
            throw new Exception("Error inserting account: " . $stmt_acc->error);
        }
        $stmt_acc->close();

        // 3b. Insert into CUSTOMER_ACCOUNT junction table
        $stmt_ca = $conn->prepare("INSERT INTO CUSTOMER_ACCOUNT (cust_id, account_number) VALUES (?, ?)");
        $stmt_ca->bind_param("ii", $cust_id, $new_account_number);

        if (!$stmt_ca->execute()) {
            throw new Exception("Error linking customer: " . $stmt_ca->error);
        }
        $stmt_ca->close();
        
        // 3c. Record the initial deposit as a Transaction (if > 0)
        if ($initial_deposit > 0) {
             $trans_date = date('Y-m-d H:i:s');
             $stmt_trans = $conn->prepare("INSERT INTO TRANSACTION (account_number, trans_type, amount, trans_date, description) VALUES (?, 'Deposit', ?, ?, 'Initial Account Deposit')");
             $stmt_trans->bind_param("ids", $new_account_number, $initial_deposit, $trans_date);
             if (!$stmt_trans->execute()) {
                 throw new Exception("Error recording transaction: " . $stmt_trans->error);
             }
             $stmt_trans->close();
        }

        $conn->commit();
        
        // Success message
        $message = "<div style='color: green; background-color: #e6ffe6; padding: 10px; border-radius: 4px; border: 1px solid green;'>
            ✅ **SUCCESS!** New **{$account_type}** account opened: **{$new_account_number}** with initial balance of **$" . number_format($initial_deposit, 2) . "** for Customer ID **{$cust_id}**.
            </div>";

    } catch (Exception $e) {
        $conn->rollback();
        $message = "<div style='color: red; background-color: #ffe6e6; padding: 10px; border-radius: 4px; border: 1px solid red;'>
            ❌ **TRANSACTION FAILED:** " . $e->getMessage() . "
            </div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Open New Account | Admin Panel</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f0f4f8; color: #333; margin: 0; padding: 0; }
        .header { background-color: #007bff; color: white; padding: 15px; text-align: center; }
        .container { max-width: 600px; margin: 30px auto; padding: 20px; background: white; border-radius: 8px; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1); }
        h2 { color: #007bff; margin-bottom: 20px; border-bottom: 2px solid #ccc; padding-bottom: 5px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group select, .form-group input { 
            width: 100%; 
            padding: 10px; 
            border: 1px solid #ccc; 
            border-radius: 4px; 
            box-sizing: border-box; 
            font-size: 16px;
        }
        button { 
            width: 100%; 
            padding: 12px; 
            background-color: #28a745; 
            color: white; 
            border: none; 
            border-radius: 4px; 
            cursor: pointer; 
            font-size: 18px;
            margin-top: 10px;
        }
        button:hover { background-color: #1e7e34; }
        .back-link { display: block; margin-top: 20px; color: #007bff; text-decoration: none; font-weight: bold; }
    </style>
</head>
<body>

    <div class="header">
        <h1>Open New Bank Account</h1>
    </div>

    <div class="container">
        <?php echo $message; // Display success/error message ?>
        
        <h2>Open Account</h2>
        <form method="POST" action="admin_add_account.php?welcome=<?php echo $user_first_name; ?>">

            <div class="form-group">
                <label for="cust_id">Select Customer</label>
                <select name="cust_id" id="cust_id" required>
                    <option value="" disabled>-- Select a Customer --</option>
                    <?php 
                    if (!empty($customers)):
                        foreach ($customers as $customer): 
                            $selected = ($customer['cust_id'] == $pre_selected_cust_id) ? 'selected' : '';
                            echo "<option value='{$customer['cust_id']}' {$selected}>" . 
                                htmlspecialchars($customer['last_name'] . ', ' . $customer['first_name'] . ' (ID: ' . $customer['cust_id']) . 
                                "</option>";
                        endforeach; 
                    else:
                        echo "<option value='' disabled>No customers found! Add one first.</option>";
                    endif;
                    ?>
                </select>
            </div>

            <div class="form-group">
                <label for="account_type">Account Type</label>
                <select name="account_type" id="account_type" required>
                    <option value="Savings">Savings</option>
                    <option value="Checking">Checking</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="branch_id">Select Branch</label>
                <select name="branch_id" id="branch_id" required>
                    <option value="" disabled selected>-- Select Home Branch --</option>
                    <?php 
                    if (!empty($branches)):
                        foreach ($branches as $branch): 
                            echo "<option value='{$branch['branch_id']}'>" . 
                                htmlspecialchars($branch['branch_name'] . ' (' . $branch['branch_city']) . 
                                ")</option>";
                        endforeach; 
                    else:
                        echo "<option value='' disabled>No branches found!</option>";
                    endif;
                    ?>
                </select>
            </div>

            <div class="form-group">
                <label for="initial_deposit">Initial Deposit Amount ($)</label>
                <input type="number" name="initial_deposit" id="initial_deposit" step="0.01" min="0" value="100.00" required>
                <small>The balance will be initialized with this amount and a corresponding transaction will be logged.</small>
            </div>

            <button type="submit" name="open_account">Open Account & Link Customer</button>
        </form>

        <a class="back-link" href="admin_customer_full.php?welcome=<?php echo $user_first_name; ?>">← Back to Customer Management</a>
    </div>

</body>
</html>
