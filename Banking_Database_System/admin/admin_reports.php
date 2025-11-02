<?php
session_start();
// --- Configuration ---
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'banking_sys');
define('DB_PORT', 3307);

// --- Global Error Variable for Display ---
$display_error = '';

// --- Database Connection ---
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME, DB_PORT);

if ($conn->connect_error) {
    // CRITICAL: Log error and set display error message
    error_log("DB Connection Failed: " . $conn->connect_error);
    $display_error = "System Error: Database connection failed. Check DB_PORT and credentials.";
    // Do not die(), continue to display error in HTML body
}

$report_data = [];
$total_assets = 0;
$total_customers = 0;
$total_loans = 0;

// -------------------------------------------------------------------------
// 1. SQL Query to Generate Consolidated Report Data (Only run if connected)
// -------------------------------------------------------------------------
if (empty($display_error)) {
    $sql = "
    SELECT 
        b.branch_id,
        b.branch_name,
        b.branch_city,
        b.assets,
        -- Count distinct customers per branch
        (SELECT COUNT(cust_id) FROM CUSTOMER c WHERE c.branch_id = b.branch_id) AS total_branch_customers,
        -- Count loans per branch (assuming 'loan' table has branch_id or customer data links to branch)
        (SELECT COUNT(loan_number) FROM loan l JOIN CUSTOMER c ON l.cust_id = c.cust_id WHERE c.branch_id = b.branch_id) AS total_branch_loans
    FROM 
        BRANCH b
    ORDER BY 
        b.branch_id ASC;
    ";

    $result = $conn->query($sql);

    if ($result) {
        while($row = $result->fetch_assoc()) {
            $report_data[] = $row;
            // Calculate system-wide totals
            $total_assets += (float)$row['assets'];
            $total_customers += (int)$row['total_branch_customers'];
            $total_loans += (int)$row['total_branch_loans'];
        }
    } else {
        // CRITICAL: Capture and display SQL query error
        $sql_error_detail = $conn->error;
        error_log("SQL Error during report generation: " . $sql_error_detail);
        $display_error = "SQL Query Failed! The report relies on: <ul>
            <li>**BRANCH** table</li>
            <li>**CUSTOMER** table (must have `branch_id`)</li>
            <li>**loan** table (must join to `CUSTOMER` via `cust_id`)</li>
            <li>**Exact Error:** " . htmlspecialchars($sql_error_detail) . "</li>
        </ul>";
    }

    $conn->close();
}

// Helper function for secure output and currency formatting
function format_currency($value) {
    return 'PKR ' . number_format((float)$value, 2);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>System Reports | Admin</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <style>
        body { background-color: #f4f7f9; }
        .container { margin-top: 50px; }
        .back-link { margin-bottom: 20px; display: block; }
        h2 { color: #1f2a38; }
        .report-card { background: white; padding: 25px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05); }
        .report-table th { background-color: #0d6efd; color: white; }
        .total-row td { font-weight: 700; border-top: 3px solid #0d6efd; }
    </style>
</head>
<body>

<div class="container">
    <a href="admin_dashboard.php" class="btn btn-secondary back-link">← Back to Dashboard</a>
    <h2 class="mb-4">📈 Financial Report: Branch Performance Summary</h2>
    
    <?php if (!empty($display_error)): ?>
        <div class="alert alert-danger" role="alert">
            <h4 class="alert-heading">Report Generation Failed!</h4>
            <?php echo $display_error; ?>
            <hr>
            <p class="mb-0">Please use your database tool (like phpMyAdmin) to verify the table names and column relationships mentioned above.</p>
        </div>
    <?php endif; ?>
    
    <div class="report-card mb-5">
        <p class="text-muted">Report Generated: <?php echo date("Y-m-d H:i:s"); ?></p>
        
        <!-- System-Wide Key Metrics (Only display if no critical error) -->
        <?php if (empty($display_error)): ?>
        <div class="row text-center mb-4">
            <div class="col-md-4">
                <div class="p-3 bg-light rounded shadow-sm">
                    <h5 class="text-primary">Total System Assets</h5>
                    <p class="fs-4 fw-bold text-primary"><?php echo format_currency($total_assets); ?></p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="p-3 bg-light rounded shadow-sm">
                    <h5 class="text-success">Total Customers</h5>
                    <p class="fs-4 fw-bold text-success"><?php echo number_format($total_customers); ?></p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="p-3 bg-light rounded shadow-sm">
                    <h5 class="text-danger">Total Active Loans</h5>
                    <p class="fs-4 fw-bold text-danger"><?php echo number_format($total_loans); ?></p>
                </div>
            </div>
        </div>

        <h4 class="mt-4 mb-3">Breakdown by Branch</h4>
        
        <?php if (!empty($report_data)): ?>
        <table class="table table-bordered table-striped report-table">
            <thead>
                <tr>
                    <th>Branch ID</th>
                    <th>Branch Name (City)</th>
                    <th>Current Assets</th>
                    <th>Total Customers</th>
                    <th>Total Loans</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                foreach ($report_data as $branch):
                    // Secure output for all fields
                    $id = htmlspecialchars($branch['branch_id']);
                    $name = htmlspecialchars($branch['branch_name']);
                    $city = htmlspecialchars($branch['branch_city']);
                    $assets_display = format_currency($branch['assets']);
                    $customers = number_format(htmlspecialchars($branch['total_branch_customers']));
                    $loans = number_format(htmlspecialchars($branch['total_branch_loans']));
                ?>
                <tr>
                    <td><?php echo $id; ?></td>
                    <td><?php echo $name; ?> (<?php echo $city; ?>)</td>
                    <td><?php echo $assets_display; ?></td>
                    <td class="text-center"><?php echo $customers; ?></td>
                    <td class="text-center"><?php echo $loans; ?></td>
                </tr>
                <?php endforeach; ?>
                
                <!-- System Totals Row -->
                <tr class="total-row">
                    <td colspan="2">SYSTEM-WIDE TOTALS</td>
                    <td><?php echo format_currency($total_assets); ?></td>
                    <td class="text-center"><?php echo number_format($total_customers); ?></td>
                    <td class="text-center"><?php echo number_format($total_loans); ?></td>
                </tr>
                
            </tbody>
        </table>
        <?php else: ?>
            <div class="alert alert-warning">
                The report generated successfully but found no records.
            </div>
        <?php endif; ?>
        <?php endif; // End of check if !empty($display_error) ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
