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

// Fetch the Admin name for display
$admin_name = $_SESSION['admin_user'] ?? 'Admin';
$loan_data = [];

// Fetch ALL Loan Details
$sql_loan = "
    SELECT 
        L.loan_number, 
        L.loan_type,
        L.amount, 
        L.interest_rate,
        L.start_date,
        L.branch_id,    
        B.branch_name AS branch_name
    FROM 
        loan L
    LEFT JOIN 
        branch B ON L.branch_id = B.branch_id
    ORDER BY 
        L.loan_number ASC
";

$result = $conn->query($sql_loan);

if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $loan_data[] = $row;
    }
}

$conn->close();

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.html");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin - Loan Portfolio</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Roboto', sans-serif; background-color: #f0f4f8; color: #333; margin: 0; padding: 0; }
        .header { background-color: #dc3545; color: white; padding: 20px; text-align: center; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); }
        .container { max-width: 1200px; margin: 30px auto; padding: 20px; background: white; border-radius: 8px; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1); }
        h2 { color: #28a745; border-bottom: 2px solid #ccc; padding-bottom: 10px; margin-bottom: 20px; font-weight: 700; }
        .loan-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .loan-table th, .loan-table td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        .loan-table th { background-color: #28a745; color: white; font-weight: 700; }
        .loan-table tr:nth-child(even) { background-color: #f2f2f2; }
        .loan-table tr:hover { background-color: #e9ecef; }
        .action-link { 
            display: inline-block; 
            padding: 8px 15px; 
            background-color: #007bff; 
            color: white; 
            text-decoration: none; 
            border-radius: 4px; 
            font-size: 0.9em; 
            margin-right: 5px; 
            transition: background-color 0.3s;
            border: none;
            cursor: pointer;
        }
        .action-link:hover {
            background-color: #0056b3;
        }
        .back-link { display: block; margin-top: 20px; font-size: 1.1em; }
        .back-link a { color: #007bff; text-decoration: none; font-weight: 500;}
        .back-link a:hover { text-decoration: underline; }
        .admin-info { 
            background: #e9ecef; 
            padding: 15px; 
            border-radius: 6px; 
            margin-bottom: 20px; 
            font-size: 1.1em;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Loan Portfolio Oversight</h1>
    </div>
    <div class="container">
        <div class="admin-info">
            <strong>Admin:</strong> <?php echo htmlspecialchars($admin_name); ?>
        </div>
        
        <h2>💰 Active Loans</h2>
        
        <?php if (!empty($loan_data)): ?>
            <table class="loan-table">
                <thead>
                    <tr>
                        <th>Loan No.</th>
                        <th>Branch ID</th>
                        <th>Branch Name</th>
                        <th>Loan Type</th>
                        <th>Amount</th>
                        <th>Rate (%)</th>
                        <th>Start Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($loan_data as $loan): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($loan['loan_number']); ?></td>
                            <td><?php echo htmlspecialchars($loan['branch_id']); ?></td>
                            <td><?php echo htmlspecialchars($loan['branch_name'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($loan['loan_type']); ?></td>
                            <td>$<?php echo number_format($loan['amount'], 2); ?></td>
                            <td><?php echo ($loan['interest_rate'] * 100); ?>%</td>
                            <td><?php echo htmlspecialchars($loan['start_date']); ?></td>
                            <td>
                                <a href="admin_loan_view.php?loan_number=<?php echo htmlspecialchars($loan['loan_number']); ?>" 
                                   class="action-link">View Details</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p style="text-align: center; color: #6c757d; font-size: 1.1em;">No active loan records found in the database.</p>
        <?php endif; ?>
        
        <div class="back-link">
            <a href="admin_dashboard.php">← Back to Dashboard</a>
        </div>
    </div>
</body>
</html>