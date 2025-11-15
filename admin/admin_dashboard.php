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

// Safe metric fetching with whitelist protection
function fetch_metric($conn, $table, $id_column) {
    if (!$conn) return 'N/A';
    
    // Whitelist protection
    $allowed = [
        'staff' => 'staff_id',
        'customer' => 'cust_id',
        'loan' => 'loan_number',
        'branch' => 'branch_id',
        'account' => 'account_number',
        'transaction' => 'trans_id'
    ];
    
    $table_lower = strtolower($table);
    if (!isset($allowed[$table_lower]) || $allowed[$table_lower] !== $id_column) {
        return 'N/A';
    }
    
    $stmt = $conn->prepare("SELECT COUNT(`{$id_column}`) AS total_count FROM `{$table}`");
    if ($stmt) {
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $stmt->close();
            return htmlspecialchars($row['total_count']);
        }
        $stmt->close();
    }
    return 'N/A';
}

// Fetch total balance
function fetch_total_balance($conn) {
    if (!$conn) return 'N/A';
    $stmt = $conn->prepare("SELECT SUM(balance) AS total FROM account");
    if ($stmt) {
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $stmt->close();
            return '$' . number_format($row['total'], 2);
        }
        $stmt->close();
    }
    return 'N/A';
}

// Fetch today's transactions
function fetch_today_transactions($conn) {
    if (!$conn) return 'N/A';
    $stmt = $conn->prepare("SELECT COUNT(*) AS count FROM transaction WHERE DATE(trans_date) = CURDATE()");
    if ($stmt) {
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $stmt->close();
            return htmlspecialchars($row['count']);
        }
        $stmt->close();
    }
    return 'N/A';
}

// Database Connection
$conn = get_db_connection();
$db_connected = ($conn !== null);

// Fetch Dashboard Metrics
$metrics = [
    'staff_count' => $db_connected ? fetch_metric($conn, 'staff', 'staff_id') : 'N/A',
    'customer_count' => $db_connected ? fetch_metric($conn, 'customer', 'cust_id') : 'N/A',
    'loan_count' => $db_connected ? fetch_metric($conn, 'loan', 'loan_number') : 'N/A',
    'branch_count' => $db_connected ? fetch_metric($conn, 'branch', 'branch_id') : 'N/A',
    'account_count' => $db_connected ? fetch_metric($conn, 'account', 'account_number') : 'N/A',
    'total_balance' => $db_connected ? fetch_total_balance($conn) : 'N/A',
    'transactions_today' => $db_connected ? fetch_today_transactions($conn) : 'N/A',
];

// Fetch Recent Activity
$recent_activities = [];
if ($db_connected) {
    $stmt = $conn->prepare("SELECT * FROM audit_log ORDER BY timestamp DESC LIMIT 5");
    if ($stmt) {
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $recent_activities[] = $row;
        }
        $stmt->close();
    }
}

// Close connection
if ($db_connected) {
    $conn->close();
}

// Logout Logic
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.html");
    exit();
}

// Dashboard Cards Definition
$dashboard_cards = [
    [
        'title' => 'Staff Management',
        'icon' => '👥',
        'description' => 'View, onboard, and manage employee records and roles.',
        'link' => 'admin_staff_details.php',
        'color' => 'primary',
        'metric_key' => 'staff_count',
        'metric_label' => 'Total Staff',
    ],
    [
        'title' => 'Customer Accounts',
        'icon' => '👤',
        'description' => 'Access and manage all customer profiles and linked accounts.',
        'link' => 'admin_customer_details.php',
        'color' => 'success',
        'metric_key' => 'customer_count',
        'metric_label' => 'Active Customers',
    ],
    [
        'title' => 'Loan Portfolio',
        'icon' => '💰',
        'description' => 'Monitor, approve, and review all active bank loan applications.',
        'link' => 'admin_loan_details.php',
        'color' => 'purple',
        'metric_key' => 'loan_count',
        'metric_label' => 'Open Loans',
    ],
    [
        'title' => 'Branch Management',
        'icon' => '🏛️',
        'description' => 'Administer bank branch locations and infrastructure details.',
        'link' => 'admin_branch_details.php',
        'color' => 'info',
        'metric_key' => 'branch_count',
        'metric_label' => 'Total Branches',
    ],
    [
        'title' => 'Transaction History',
        'icon' => '📊',
        'description' => 'Review global records of all financial transactions across the bank.',
        'link' => 'admin_transactions.php',
        'color' => 'danger',
        'metric_key' => 'transactions_today',
        'metric_label' => 'Today\'s Transactions',
    ],
    [
        'title' => 'Log Audits',
        'icon' => '📝',
        'description' => 'Inspect and filter system access and activity logs for compliance.',
        'link' => 'admin_log_audits.php',
        'color' => 'secondary',
        'metric_key' => null,
        'metric_label' => null,
    ],
];

// Summary Cards for Quick Overview
$summary_cards = [
    [
        'title' => 'Total Accounts',
        'value' => $metrics['account_count'],
        'icon' => '🏦',
        'color' => 'primary',
    ],
    [
        'title' => 'Total Balance',
        'value' => $metrics['total_balance'],
        'icon' => '💵',
        'color' => 'success',
    ],
    [
        'title' => 'Today\'s Transactions',
        'value' => $metrics['transactions_today'],
        'icon' => '📈',
        'color' => 'warning',
    ],
    [
        'title' => 'Active Branches',
        'value' => $metrics['branch_count'],
        'icon' => '🏢',
        'color' => 'info',
    ],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Banking Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary: #0d6efd; 
            --success: #198754; 
            --warning: #ffc107;
            --info: #0dcaf0;
            --danger: #dc3545;
            --secondary: #6c757d;
            --purple: #6f42c1;
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
        
        /* Summary Cards */
        .summary-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s, box-shadow 0.3s;
            border-left: 5px solid;
            height: 100%;
        }
        
        .summary-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.12);
        }
        
        .summary-card-icon {
            font-size: 2.5rem;
            opacity: 0.8;
        }
        
        .summary-card-value {
            font-size: 2rem;
            font-weight: 700;
            margin: 10px 0 5px 0;
        }
        
        .summary-card-title {
            color: #6c757d;
            font-size: 0.9rem;
            font-weight: 500;
        }
        
        /* Dashboard Cards */
        .dashboard-card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s, box-shadow 0.3s;
            text-decoration: none;
            color: inherit;
            display: flex;
            flex-direction: column;
            height: 100%;
            background-color: white;
            border-left: 6px solid;
            overflow: hidden;
        }
        
        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }
        
        .card-body {
            padding: 20px;
        }
        
        .card-header-icon {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }
        
        .card-title-custom {
            font-weight: 600;
            font-size: 1.25rem;
            margin-bottom: 5px;
        }
        
        .card-footer-action {
            padding: 12px 20px;
            color: white;
            text-align: center;
            font-weight: 600;
            transition: all 0.3s ease;
            text-transform: uppercase;
            font-size: 0.9rem;
        }
        
        .card-footer-action:hover {
            opacity: 0.9;
        }
        
        /* Color Classes */
        .card-border-primary { border-left-color: var(--primary) !important; }
        .card-text-primary { color: var(--primary); }
        .card-action-primary { background-color: var(--primary); }
        
        .card-border-success { border-left-color: var(--success) !important; }
        .card-text-success { color: var(--success); }
        .card-action-success { background-color: var(--success); }
        
        .card-border-warning { border-left-color: var(--warning) !important; }
        .card-text-warning { color: var(--warning); }
        .card-action-warning { background-color: var(--warning); color: #333; }
        
        .card-border-info { border-left-color: var(--info) !important; }
        .card-text-info { color: var(--info); }
        .card-action-info { background-color: var(--info); }
        
        .card-border-danger { border-left-color: var(--danger) !important; }
        .card-text-danger { color: var(--danger); }
        .card-action-danger { background-color: var(--danger); }
        
        .card-border-secondary { border-left-color: var(--secondary) !important; }
        .card-text-secondary { color: var(--secondary); }
        .card-action-secondary { background-color: var(--secondary); }
        
        .card-border-purple { border-left-color: var(--purple) !important; }
        .card-text-purple { color: var(--purple); }
        .card-action-purple { background-color: var(--purple); }
        
        /* Activity Log */
        .activity-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        }
        
        .activity-item {
            padding: 15px;
            border-bottom: 1px solid #f0f0f0;
            transition: background-color 0.2s;
        }
        
        .activity-item:last-child {
            border-bottom: none;
        }
        
        .activity-item:hover {
            background-color: #f8f9fa;
        }
        
        .section-title {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 3px solid var(--primary);
            display: inline-block;
        }
        
        /* Alert Styling */
        .alert-custom {
            border-radius: 12px;
            border: none;
            box-shadow: 0 4px 15px rgba(220, 53, 69, 0.2);
        }
        
        /* Animations */
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

<div class="container py-5">
    
    <?php if (!$db_connected): ?>
        <div class="alert alert-danger alert-custom fade-in" role="alert">
            <strong>🚨 Database Connection Error:</strong> Could not connect to the database. Please check your connection settings.
        </div>
    <?php endif; ?>
    
    <!-- Summary Overview Section -->
    <h2 class="section-title">📊 Quick Overview</h2>
    <div class="row g-4 mb-5 fade-in">
        <?php foreach ($summary_cards as $card): ?>
            <div class="col-md-6 col-lg-3">
                <div class="summary-card border-<?php echo $card['color']; ?>">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="summary-card-title"><?php echo $card['title']; ?></div>
                            <div class="summary-card-value text-<?php echo $card['color']; ?>">
                                <?php echo $card['value']; ?>
                            </div>
                        </div>
                        <div class="summary-card-icon">
                            <?php echo $card['icon']; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    
    <!-- Main Dashboard Modules -->
    <h2 class="section-title">🎯 Core Modules</h2>
    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4 mb-5 fade-in">
        <?php foreach ($dashboard_cards as $card): ?>
            <?php
                $border_class = 'card-border-' . $card['color'];
                $icon_text_class = 'card-text-' . $card['color'];
                $action_bg_class = 'card-action-' . $card['color'];
                $metric_value = $card['metric_key'] ? $metrics[$card['metric_key']] : null;
                $has_metric = $metric_value !== null;
            ?>
            <div class="col">
                <a href="<?php echo $card['link']; ?>" class="dashboard-card <?php echo $border_class; ?>">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div class="card-header-icon <?php echo $icon_text_class; ?>">
                                <?php echo $card['icon']; ?>
                            </div>
                            <?php if ($has_metric): ?>
                                <div class="text-end">
                                    <div class="metric-value display-6 lh-1 <?php echo $icon_text_class; ?>">
                                        <?php echo $metric_value; ?>
                                    </div>
                                    <small class="text-muted fw-bold"><?php echo $card['metric_label']; ?></small>
                                </div>
                            <?php endif; ?>
                        </div>
                        <h5 class="card-title-custom"><?php echo $card['title']; ?></h5>
                        <p class="card-text text-muted small"><?php echo $card['description']; ?></p>
                    </div>
                    <div class="card-footer-action <?php echo $action_bg_class; ?>">
                        <?php echo $has_metric ? 'View Details →' : 'Manage Module →'; ?>
                    </div>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
    
    <!-- Recent Activity Section -->
    <?php if ($db_connected && !empty($recent_activities)): ?>
    <h2 class="section-title">🕒 Recent System Activity</h2>
    <div class="row fade-in">
        <div class="col-12">
            <div class="activity-card">
                <div class="p-3 bg-dark text-white" style="border-radius: 12px 12px 0 0;">
                    <h5 class="mb-0">Latest Audit Logs</h5>
                </div>
                <div class="p-0">
                    <?php foreach ($recent_activities as $activity): ?>
                        <div class="activity-item">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <strong class="text-primary"><?php echo htmlspecialchars($activity['action_type']); ?></strong>
                                    <br>
                                    <small class="text-muted">
                                        by <?php echo htmlspecialchars($activity['user_role']); ?>
                                        <?php if ($activity['user_id']): ?>
                                            (ID: <?php echo htmlspecialchars($activity['user_id']); ?>)
                                        <?php endif; ?>
                                    </small>
                                    <?php if ($activity['details']): ?>
                                        <br>
                                        <small><?php echo htmlspecialchars($activity['details']); ?></small>
                                    <?php endif; ?>
                                </div>
                                <div class="text-end">
                                    <small class="text-muted">
                                        <?php echo date('M d, Y', strtotime($activity['timestamp'])); ?>
                                        <br>
                                        <?php echo date('h:i A', strtotime($activity['timestamp'])); ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="p-3 text-center border-top">
                    <a href="admin_log_audits.php" class="btn btn-sm btn-outline-primary">View All Logs →</a>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>