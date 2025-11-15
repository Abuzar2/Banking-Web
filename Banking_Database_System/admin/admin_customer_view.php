<?php
session_start();

// Security check - ensure admin is logged in
if (!isset($_SESSION['admin_user'])) {
    header("Location: admin_login.php");
    exit();
}

$servername = "localhost";
$username = "root";   
$password = ""; 
$dbname = "banking_sys";
$port = 3307;

$conn = new mysqli($servername, $username, $password, $dbname, $port);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get Customer ID from URL
$cust_id = isset($_GET['cust_id']) ? (int)$_GET['cust_id'] : null;

if (!$cust_id) {
    die("Invalid Customer ID");
}

// Fetch customer details
$customer = [];
$accounts = [];
$loans = [];

// Customer basic info
$sql_customer = "SELECT * FROM customer WHERE cust_id = ?";
$stmt = $conn->prepare($sql_customer);
$stmt->bind_param("i", $cust_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $customer = $result->fetch_assoc();
} else {
    die("Customer not found");
}
$stmt->close();

// Fetch customer accounts
$sql_accounts = "SELECT a.account_number, a.account_type, a.balance, a.date_opened, b.branch_name 
                 FROM account a 
                 JOIN customer_account ca ON a.account_number = ca.account_number 
                 JOIN branch b ON a.branch_id = b.branch_id 
                 WHERE ca.cust_id = ?";
$stmt_accounts = $conn->prepare($sql_accounts);
$stmt_accounts->bind_param("i", $cust_id);
$stmt_accounts->execute();
$accounts = $stmt_accounts->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_accounts->close();

// Fetch customer loans
$sql_loans = "SELECT l.loan_number, l.loan_type, l.amount, l.interest_rate, l.start_date, b.branch_name 
              FROM loan l 
              JOIN customer_loan cl ON l.loan_number = cl.loan_number 
              JOIN branch b ON l.branch_id = b.branch_id 
              WHERE cl.cust_id = ?";
$stmt_loans = $conn->prepare($sql_loans);
$stmt_loans->bind_param("i", $cust_id);
$stmt_loans->execute();
$loans = $stmt_loans->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_loans->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Details - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #0d6efd;
            --success: #198754;
            --danger: #dc3545;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            padding-top: 75px;
        }
        
        .navbar {
            background: linear-gradient(135deg, #1f2a38 0%, #2c3e50 100%) !important;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }
        
        .page-header {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            margin-bottom: 30px;
        }
        
        .detail-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            margin-bottom: 25px;
            border-left: 5px solid var(--primary);
        }
        
        .detail-card.success {
            border-left-color: var(--success);
        }
        
        .detail-card.warning {
            border-left-color: #ffc107;
        }
        
        .detail-item {
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .detail-label {
            font-weight: 600;
            color: #6c757d;
            font-size: 0.9em;
            margin-bottom: 5px;
        }
        
        .detail-value {
            font-size: 1.1em;
            font-weight: 500;
            color: #212529;
        }
        
        .account-badge {
            background: #e9ecef;
            padding: 8px 12px;
            border-radius: 6px;
            font-weight: 500;
        }
        
        .balance-positive {
            color: var(--success);
            font-weight: 600;
        }
        
        .no-data {
            text-align: center;
            padding: 40px;
            color: #6c757d;
        }
        
        .no-data i {
            font-size: 3em;
            margin-bottom: 15px;
            opacity: 0.5;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark fixed-top">
    <div class="container-fluid">
        <a class="navbar-brand" href="admin_dashboard.php">
            <span class="fs-4">🏦</span> BANKING ADMIN SYSTEM
        </a>
        <div class="d-flex align-items-center">
            <span class="text-light me-3">Hello, <strong><?php echo htmlspecialchars($_SESSION['admin_user']); ?></strong></span>
            <a href="admin_login.php?logout=true" class="btn btn-outline-light btn-sm">Logout</a>
        </div>
    </div>
</nav>

<div class="container-fluid py-4">
    
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="admin_dashboard.php">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="admin_customer_details.php">Customers</a></li>
            <li class="breadcrumb-item active">Customer Details</li>
        </ol>
    </nav>
    
    <!-- Page Header -->
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h2 class="mb-0">
                    <i class="fas fa-user-circle"></i>
                    Customer Details
                </h2>
                <p class="text-muted mb-0">Complete profile information for <?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']); ?></p>
            </div>
            <div class="col-md-6 text-end">
                <a href="admin_customer_details.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Customers
                </a>
                <a href="admin_customer_edit.php?cust_id=<?php echo $cust_id; ?>" class="btn btn-warning">
                    <i class="fas fa-edit"></i> Edit Customer
                </a>
            </div>
        </div>
    </div>
    
    <div class="row">
        <!-- Personal Information -->
        <div class="col-md-6">
            <div class="detail-card">
                <h4 class="mb-4">
                    <i class="fas fa-id-card"></i>
                    Personal Information
                </h4>
                
                <div class="detail-item">
                    <div class="detail-label">Customer ID</div>
                    <div class="detail-value">#<?php echo htmlspecialchars($customer['cust_id']); ?></div>
                </div>
                
                <div class="detail-item">
                    <div class="detail-label">Full Name</div>
                    <div class="detail-value"><?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']); ?></div>
                </div>
                
                <div class="detail-item">
                    <div class="detail-label">Date of Birth</div>
                    <div class="detail-value"><?php echo date('F j, Y', strtotime($customer['date_of_birth'])); ?></div>
                </div>
                
                <div class="detail-item">
                    <div class="detail-label">SSN</div>
                    <div class="detail-value">
                        <?php 
                        if (!empty($customer['ssn'])) {
                            echo '***-**-' . substr($customer['ssn'], -4);
                        } else {
                            echo 'N/A';
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Contact Information -->
        <div class="col-md-6">
            <div class="detail-card success">
                <h4 class="mb-4">
                    <i class="fas fa-address-book"></i>
                    Contact Information
                </h4>
                
                <div class="detail-item">
                    <div class="detail-label">Mobile Number</div>
                    <div class="detail-value"><?php echo htmlspecialchars($customer['mobile_no']); ?></div>
                </div>
                
                <div class="detail-item">
                    <div class="detail-label">Phone Number</div>
                    <div class="detail-value"><?php echo htmlspecialchars($customer['phone_number'] ?? 'N/A'); ?></div>
                </div>
                
                <div class="detail-item">
                    <div class="detail-label">Address</div>
                    <div class="detail-value"><?php echo htmlspecialchars($customer['address'] ?? 'N/A'); ?></div>
                </div>
                
                <div class="detail-item">
                    <div class="detail-label">City</div>
                    <div class="detail-value"><?php echo htmlspecialchars($customer['city']); ?></div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Accounts Information -->
    <div class="row">
        <div class="col-12">
            <div class="detail-card warning">
                <h4 class="mb-4">
                    <i class="fas fa-piggy-bank"></i>
                    Account Information
                </h4>
                
                <?php if (!empty($accounts)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>Account Number</th>
                                    <th>Type</th>
                                    <th>Balance</th>
                                    <th>Date Opened</th>
                                    <th>Branch</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($accounts as $account): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($account['account_number']); ?></strong></td>
                                        <td>
                                            <span class="account-badge">
                                                <?php echo htmlspecialchars($account['account_type']); ?>
                                            </span>
                                        </td>
                                        <td class="balance-positive">
                                            $<?php echo number_format($account['balance'], 2); ?>
                                        </td>
                                        <td><?php echo date('M j, Y', strtotime($account['date_opened'])); ?></td>
                                        <td><?php echo htmlspecialchars($account['branch_name']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="no-data">
                        <i class="fas fa-piggy-bank"></i>
                        <h5>No Accounts Found</h5>
                        <p>This customer doesn't have any bank accounts yet.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Loans Information -->
    <div class="row">
        <div class="col-12">
            <div class="detail-card" style="border-left-color: #6f42c1;">
                <h4 class="mb-4">
                    <i class="fas fa-hand-holding-usd"></i>
                    Loan Information
                </h4>
                
                <?php if (!empty($loans)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>Loan Number</th>
                                    <th>Type</th>
                                    <th>Amount</th>
                                    <th>Interest Rate</th>
                                    <th>Start Date</th>
                                    <th>Branch</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($loans as $loan): ?>
                                    <tr>
                                        <td><strong>#<?php echo htmlspecialchars($loan['loan_number']); ?></strong></td>
                                        <td>
                                            <span class="account-badge">
                                                <?php echo htmlspecialchars($loan['loan_type']); ?>
                                            </span>
                                        </td>
                                        <td class="balance-positive">
                                            $<?php echo number_format($loan['amount'], 2); ?>
                                        </td>
                                        <td><?php echo ($loan['interest_rate'] * 100); ?>%</td>
                                        <td><?php echo date('M j, Y', strtotime($loan['start_date'])); ?></td>
                                        <td><?php echo htmlspecialchars($loan['branch_name']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="no-data">
                        <i class="fas fa-hand-holding-usd"></i>
                        <h5>No Loans Found</h5>
                        <p>This customer doesn't have any active loans.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
