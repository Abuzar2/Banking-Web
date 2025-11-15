<?php
// MUST BE THE VERY FIRST LINE
session_start();

// Session Security Enhancement
if (!isset($_SESSION['initiated'])) {
    session_regenerate_id(true);
    $_SESSION['initiated'] = true;
}

// Session timeout (30 minutes)
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
    session_unset();
    session_destroy();
    header("Location: admin_login.php");
    exit();
}
$_SESSION['last_activity'] = time();

// Check if admin is logged in
if (!isset($_SESSION['admin_user'])) {
    header("Location: admin_login.php");
    exit();
}

// Retrieve admin name from session
$admin_name = htmlspecialchars($_SESSION['admin_user'] ?? 'Administrator');

// Database Configuration
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'banking_sys');
define('DB_PORT', 3307);

// Database Connection Function
function get_db_connection() {
    $conn = @new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME, DB_PORT);
    if ($conn->connect_error) {
        error_log("Database connection failed: " . $conn->connect_error);
        return null;
    }
    $conn->set_charset("utf8mb4");
    return $conn;
}

// Initialize variables
$conn = get_db_connection();
$db_connected = ($conn !== null);
$customers = [];
$success_message = '';
$error_message = '';
$search_query = '';

// Handle Customer Actions (Add, Edit, Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $db_connected) {
    
    // ADD NEW CUSTOMER
    if (isset($_POST['action']) && $_POST['action'] === 'add') {
        $first_name = trim($_POST['first_name']);
        $last_name = trim($_POST['last_name']);
        $ssn = trim($_POST['ssn']);
        $address = trim($_POST['address']);
        $phone_number = trim($_POST['phone_number']);
        $mobile_no = trim($_POST['mobile_no']);
        $date_of_birth = trim($_POST['date_of_birth']);
        $city = trim($_POST['city']);
        
        $stmt = $conn->prepare("INSERT INTO customer (first_name, last_name, ssn, address, phone_number, mobile_no, date_of_birth, city) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssss", $first_name, $last_name, $ssn, $address, $phone_number, $mobile_no, $date_of_birth, $city);
        
        if ($stmt->execute()) {
            $success_message = "Customer added successfully!";
            
            // Log the action
            $log_stmt = $conn->prepare("INSERT INTO audit_log (timestamp, user_role, user_id, action_type, details) VALUES (NOW(), 'Admin', ?, 'Customer Added', ?)");
            $log_details = "Added customer: $first_name $last_name (SSN: $ssn)";
            $log_stmt->bind_param("ss", $admin_name, $log_details);
            $log_stmt->execute();
            $log_stmt->close();
        } else {
            $error_message = "Error adding customer: " . $stmt->error;
        }
        $stmt->close();
    }
    
    // EDIT CUSTOMER
    if (isset($_POST['action']) && $_POST['action'] === 'edit') {
        $cust_id = intval($_POST['cust_id']);
        $first_name = trim($_POST['first_name']);
        $last_name = trim($_POST['last_name']);
        $ssn = trim($_POST['ssn']);
        $address = trim($_POST['address']);
        $phone_number = trim($_POST['phone_number']);
        $mobile_no = trim($_POST['mobile_no']);
        $date_of_birth = trim($_POST['date_of_birth']);
        $city = trim($_POST['city']);
        
        $stmt = $conn->prepare("UPDATE customer SET first_name=?, last_name=?, ssn=?, address=?, phone_number=?, mobile_no=?, date_of_birth=?, city=? WHERE cust_id=?");
        $stmt->bind_param("ssssssssi", $first_name, $last_name, $ssn, $address, $phone_number, $mobile_no, $date_of_birth, $city, $cust_id);
        
        if ($stmt->execute()) {
            $success_message = "Customer updated successfully!";
            
            // Log the action
            $log_stmt = $conn->prepare("INSERT INTO audit_log (timestamp, user_role, user_id, action_type, details) VALUES (NOW(), 'Admin', ?, 'Customer Updated', ?)");
            $log_details = "Updated customer ID: $cust_id ($first_name $last_name)";
            $log_stmt->bind_param("ss", $admin_name, $log_details);
            $log_stmt->execute();
            $log_stmt->close();
        } else {
            $error_message = "Error updating customer: " . $stmt->error;
        }
        $stmt->close();
    }
    
    // DELETE CUSTOMER
    if (isset($_POST['action']) && $_POST['action'] === 'delete') {
        $cust_id = intval($_POST['cust_id']);
        
        // First, get customer details for logging
        $stmt = $conn->prepare("SELECT first_name, last_name FROM customer WHERE cust_id = ?");
        $stmt->bind_param("i", $cust_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $customer_info = $result->fetch_assoc();
        $stmt->close();
        
        // Delete customer (this will cascade to related tables if foreign keys are set properly)
        $stmt = $conn->prepare("DELETE FROM customer WHERE cust_id = ?");
        $stmt->bind_param("i", $cust_id);
        
        if ($stmt->execute()) {
            $success_message = "Customer deleted successfully!";
            
            // Log the action
            if ($customer_info) {
                $log_stmt = $conn->prepare("INSERT INTO audit_log (timestamp, user_role, user_id, action_type, details) VALUES (NOW(), 'Admin', ?, 'Customer Deleted', ?)");
                $log_details = "Deleted customer ID: $cust_id ({$customer_info['first_name']} {$customer_info['last_name']})";
                $log_stmt->bind_param("ss", $admin_name, $log_details);
                $log_stmt->execute();
                $log_stmt->close();
            }
        } else {
            $error_message = "Error deleting customer: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Handle Search
if (isset($_GET['search'])) {
    $search_query = trim($_GET['search']);
}

// Fetch Customers with their account information
if ($db_connected) {
    if (!empty($search_query)) {
        $search_param = "%{$search_query}%";
        $stmt = $conn->prepare("
            SELECT c.*, 
                   COUNT(DISTINCT ca.account_number) as account_count,
                   COALESCE(SUM(a.balance), 0) as total_balance
            FROM customer c
            LEFT JOIN customer_account ca ON c.cust_id = ca.cust_id
            LEFT JOIN account a ON ca.account_number = a.account_number
            WHERE c.first_name LIKE ? OR c.last_name LIKE ? OR c.ssn LIKE ? OR c.city LIKE ?
            GROUP BY c.cust_id
            ORDER BY c.cust_id DESC
        ");
        $stmt->bind_param("ssss", $search_param, $search_param, $search_param, $search_param);
    } else {
        $stmt = $conn->prepare("
            SELECT c.*, 
                   COUNT(DISTINCT ca.account_number) as account_count,
                   COALESCE(SUM(a.balance), 0) as total_balance
            FROM customer c
            LEFT JOIN customer_account ca ON c.cust_id = ca.cust_id
            LEFT JOIN account a ON ca.account_number = a.account_number
            GROUP BY c.cust_id
            ORDER BY c.cust_id DESC
        ");
    }
    
    if ($stmt) {
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $customers[] = $row;
        }
        $stmt->close();
    }
}

// Logout Logic
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.html");
    exit();
}

// Close connection
if ($db_connected) {
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Management - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    
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
        
        .user-info {
            color: #ced4da;
            margin-right: 15px;
            font-size: 0.95rem;
        }
        
        .page-header {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            margin-bottom: 30px;
        }
        
        .stats-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            border-left: 5px solid var(--success);
            transition: transform 0.3s;
        }
        
        .stats-card:hover {
            transform: translateY(-3px);
        }
        
        .stats-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--success);
        }
        
        .search-box {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            margin-bottom: 20px;
        }
        
        .table-container {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        }
        
        .table thead {
            background: linear-gradient(135deg, #1f2a38 0%, #2c3e50 100%);
            color: white;
        }
        
        .table thead th {
            border: none;
            padding: 15px;
            font-weight: 600;
        }
        
        .table tbody tr {
            transition: background-color 0.2s;
        }
        
        .table tbody tr:hover {
            background-color: #f8f9fa;
        }
        
        .btn-action {
            padding: 5px 12px;
            font-size: 0.85rem;
            margin: 2px;
        }
        
        .badge-custom {
            padding: 8px 12px;
            font-weight: 500;
        }
        
        .modal-header {
            background: linear-gradient(135deg, #1f2a38 0%, #2c3e50 100%);
            color: white;
        }
        
        .customer-detail {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
        }
        
        .alert-custom {
            border-radius: 12px;
            border: none;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .fade-in {
            animation: fadeIn 0.5s ease-out;
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
            <span class="user-info">Hello, <strong><?php echo $admin_name; ?></strong></span>
            <a href="?logout=true" class="btn btn-outline-light btn-sm">Logout</a>
        </div>
    </div>
</nav>

<div class="container-fluid py-4">
    
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="admin_dashboard.php">Dashboard</a></li>
            <li class="breadcrumb-item active">Customer Management</li>
        </ol>
    </nav>
    
    <!-- Page Header -->
    <div class="page-header fade-in">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h2 class="mb-0">👤 Customer Management</h2>
                <p class="text-muted mb-0">Manage customer profiles and account information</p>
            </div>
            <div class="col-md-6 text-end">
                <a href="admin_customer_add.php" class="btn btn-success">
                    + Add New Customer
                </a>
            </div>
        </div>
    </div>
    
    <!-- Success/Error Messages -->
    <?php if ($success_message): ?>
        <div class="alert alert-success alert-custom alert-dismissible fade show fade-in" role="alert">
            <strong>✓ Success!</strong> <?php echo htmlspecialchars($success_message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if ($error_message): ?>
        <div class="alert alert-danger alert-custom alert-dismissible fade show fade-in" role="alert">
            <strong>✗ Error!</strong> <?php echo htmlspecialchars($error_message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if (!$db_connected): ?>
        <div class="alert alert-danger alert-custom fade-in" role="alert">
            <strong>🚨 Database Connection Error:</strong> Unable to connect to the database.
        </div>
    <?php endif; ?>
    
    <!-- Statistics Cards -->
    <div class="row g-4 mb-4 fade-in">
        <div class="col-md-4">
            <div class="stats-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-2">Total Customers</h6>
                        <div class="stats-number"><?php echo count($customers); ?></div>
                    </div>
                    <div class="fs-1">👥</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stats-card" style="border-left-color: var(--primary);">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-2">Total Accounts</h6>
                        <div class="stats-number" style="color: var(--primary);">
                            <?php 
                            $total_accounts = 0;
                            foreach ($customers as $c) {
                                $total_accounts += $c['account_count'];
                            }
                            echo $total_accounts;
                            ?>
                        </div>
                    </div>
                    <div class="fs-1">🏦</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stats-card" style="border-left-color: #ffc107;">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-2">Total Balance</h6>
                        <div class="stats-number" style="color: #ffc107;">
                            $<?php 
                            $total_balance = 0;
                            foreach ($customers as $c) {
                                $total_balance += $c['total_balance'];
                            }
                            echo number_format($total_balance, 2);
                            ?>
                        </div>
                    </div>
                    <div class="fs-1">💰</div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Search Box -->
    <div class="search-box fade-in">
        <form method="GET" action="" class="row g-3">
            <div class="col-md-10">
                <input type="text" class="form-control form-control-lg" name="search" 
                       placeholder="🔍 Search by name, SSN, or city..." 
                       value="<?php echo htmlspecialchars($search_query); ?>">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary btn-lg w-100">Search</button>
            </div>
        </form>
        <?php if (!empty($search_query)): ?>
            <div class="mt-3">
                <a href="admin_customer_details.php" class="btn btn-sm btn-outline-secondary">Clear Search</a>
                <span class="text-muted ms-2">Found <?php echo count($customers); ?> result(s)</span>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Customers Table -->
    <div class="table-container fade-in">
        <h4 class="mb-4">All Customers</h4>
        
        <?php if (empty($customers)): ?>
            <div class="alert alert-info text-center">
                <h5>No customers found</h5>
                <p class="mb-0">Click "Add New Customer" to create your first customer record.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>SSN</th>
                            <th>City</th>
                            <th>Date of Birth</th>
                            <th>Phone</th>
                            <th>Accounts</th>
                            <th>Total Balance</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($customers as $customer): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($customer['cust_id']); ?></strong></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']); ?></strong>
                                </td>
                                <td><?php echo htmlspecialchars($customer['ssn'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($customer['city']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($customer['date_of_birth'])); ?></td>
                                <td><?php echo htmlspecialchars($customer['mobile_no']); ?></td>
                                <td>
                                    <span class="badge bg-primary badge-custom">
                                        <?php echo $customer['account_count']; ?> Account(s)
                                    </span>
                                </td>
                                <td>
                                    <strong class="text-success">
                                        $<?php echo number_format($customer['total_balance'], 2); ?>
                                    </strong>
                                </td>
                                <td>
                                    <!-- View Link -->
                                    <a href="admin_customer_view.php?cust_id=<?php echo $customer['cust_id']; ?>" 
                                        class="btn btn-sm btn-info btn-action">
                                        View
                                    </a>

                                    <!-- Edit Link -->
                                    <a href="admin_customer_edit.php?cust_id=<?php echo $customer['cust_id']; ?>" 
                                        class="btn btn-sm btn-warning btn-action">
                                        Edit
                                    </a>

                                    <!-- Delete Link -->
                                    <a href="admin_customer_delete.php?cust_id=<?php echo $customer['cust_id']; ?>" 
                                        class="btn btn-sm btn-danger btn-action"
                                        onclick="return confirm('Are you sure you want to delete <?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']); ?>?');">
                                        Delete
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
    
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>