<?php
// MUST BE THE VERY FIRST LINE
session_start();

// Check if admin is logged in, otherwise redirect to login page
if (!isset($_SESSION['admin_user'])) {
    header("Location: admin_login.php");
    exit();
}

// Retrieve admin name from the session
$admin_name = htmlspecialchars($_SESSION['admin_user'] ?? 'Administrator');

// -----------------------------------------------------------------
// 1. DATABASE CONNECTION & DATA FETCHING (Using the logic you provided)
// -----------------------------------------------------------------
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'banking_sys');
define('DB_PORT', 3307);

$conn = @new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME, DB_PORT);
$db_connected = !$conn->connect_error;

$loan_output = "<tr><td colspan='6' class='text-center py-4 text-muted'>No loan records found in the system.</td></tr>";
$error_message = '';

if ($db_connected) {
    // Query based on the fields you need
    $sql = "SELECT loan_number, loan_type, amount, interest_rate, start_date, branch_id FROM loan ORDER BY loan_number DESC";
    
    // Use a try-catch for better error reporting in development
    try {
        $result = $conn->query($sql);
        
        if ($result && $result->num_rows > 0) {
            $loan_output = "";
            while($row = $result->fetch_assoc()) {
                // Data Sanitization and Formatting
                $loan_num = htmlspecialchars($row["loan_number"]);
                $loan_type = htmlspecialchars($row["loan_type"]);
                // Format amount with commas
                $amount_formatted = number_format((float)$row["amount"], 2); 
                $interest_rate = htmlspecialchars($row["interest_rate"]);
                $start_date = htmlspecialchars($row["start_date"]);
                $branch_id = htmlspecialchars($row["branch_id"]);
                
                // Build the HTML row
                $loan_output .= "
                    <tr>
                        <td><a href='#' class='text-decoration-none text-primary fw-bold'>{$loan_num}</a></td>
                        <td>{$loan_type}</td>
                        <td class='text-end fw-semibold'>PKR {$amount_formatted}</td>
                        <td>{$interest_rate}%</td>
                        <td>{$start_date}</td>
                        <td class='text-center'>{$branch_id}</td>
                        <td class='text-center'>
                            <button class='btn btn-sm btn-outline-secondary' title='View/Edit Details'>
                                View
                            </button>
                        </td>
                    </tr>";
            }
        }
        if ($result) $result->free();
    } catch (mysqli_sql_exception $e) {
        error_log("SQL Error in loan details query: " . $e->getMessage());
        $error_message = "SQL Error during data fetching.";
    }
    $conn->close();
} else {
    $error_message = "Database connection failed: " . $conn->connect_error;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Loan Portfolio - Admin</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --purple: #6f42c1; /* Deep Violet for the professional finance accent */
            --dark-header: #1f2a38;
            --light-bg: #f4f7f9;
        }
        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--light-bg);
            padding-top: 75px; /* Space for fixed navbar */
        }
        /* Top Navigation Bar */
        .navbar {
            background-color: var(--dark-header) !important; 
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        .user-info {
            color: #ced4da;
            margin-right: 15px;
            font-size: 0.95rem;
        }
        /* Main Content Wrapper */
        .main-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            padding: 30px;
            background-color: white;
        }
        .btn-purple {
            background-color: var(--purple);
            border-color: var(--purple);
            color: white;
            transition: background-color 0.2s;
        }
        .btn-purple:hover {
            background-color: #5a359c; 
            border-color: #5a359c;
            color: white;
        }
        
        /* Table Styling */
        .loan-table th {
            background-color: var(--purple); /* Purple Header for the table */
            color: white;
            font-weight: 600;
            vertical-align: middle;
            border-bottom: 2px solid #dee2e6;
            padding: 15px 10px; /* Added padding to make headers look better */
        }
        .loan-table td {
            vertical-align: middle;
            padding: 15px 10px;
        }
        .loan-table tr:hover {
            background-color: #f8f9fa;
        }

        /* Focus on Title */
        .page-title {
            color: var(--dark-header);
            border-left: 5px solid var(--purple); /* Purple accent bar */
            padding-left: 15px;
            font-weight: 700;
        }
    </style>
</head>
<body>

<!-- Fixed Dark Navigation Bar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
    <div class="container-fluid">
        <a class="navbar-brand" href="admin_dashboard.php">
            <span class="fs-4">🏦</span> BANKING ADMIN SYSTEM
        </a>
        <div class="d-flex align-items-center">
            <span class="user-info">Hello, **<?php echo $admin_name; ?>**</span>
            <a href="admin_dashboard.php?logout=true" class="btn btn-outline-light btn-sm">
                <small>Logout</small>
            </a>
        </div>
    </div>
</nav>

<div class="container py-5"> 
    
    <h2 class="page-title mb-5">Loan Portfolio Management</h2>
    
    <?php if (!$db_connected): ?>
        <div class="alert alert-danger mb-4" role="alert">
            🚨 **Connection Alert:** Failed to connect to the database. (<?php echo htmlspecialchars($error_message); ?>)
        </div>
    <?php elseif ($error_message): ?>
        <div class="alert alert-warning mb-4" role="alert">
            ⚠️ **Data Alert:** Data could not be fetched. <?php echo htmlspecialchars($error_message); ?>
        </div>
    <?php endif; ?>

    <div class="main-card">
        <!-- Controls: Search and Add New -->
        <div class="row mb-4 align-items-center">
            <div class="col-md-8">
                <input type="text" class="form-control rounded-pill" placeholder="Search loans by ID, Type, or Branch ID...">
            </div>
            <div class="col-md-4 text-end">
                <button class="btn btn-purple rounded-pill px-4">
                    + Register New Loan
                </button>
            </div>
        </div>
        
        <!-- Loan Data Table -->
        <div class="table-responsive">
            <table class="table table-hover align-middle loan-table">
                <thead>
                    <tr>
                        <th scope="col">Loan Number</th>
                        <th scope="col">Loan Type</th>
                        <th scope="col" class="text-end">Amount (PKR)</th>
                        <th scope="col">Interest Rate</th>
                        <th scope="col">Start Date</th>
                        <th scope="col" class="text-center">Branch ID</th>
                        <th scope="col" class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php echo $loan_output; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination Placeholder -->
        <nav class="d-flex justify-content-center mt-4">
            <ul class="pagination">
                <li class="page-item disabled"><a class="page-link" href="#">Previous</a></li>
                <li class="page-item active"><a class="page-link" href="#">1</a></li>
                <li class="page-item"><a class="page-link" href="#">2</a></li>
                <li class="page-item"><a class="page-link" href="#">3</a></li>
                <li class="page-item"><a class="page-link" href="#">Next</a></li>
            </ul>
        </nav>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
