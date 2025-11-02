<?php
session_start();
// Database connection details
$servername = "localhost";
$username = "root";
$password = ""; 
$dbname = "banking_sys";
$port = 3307;

$conn = new mysqli($servername, $username, $password, $dbname, $port);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$admin_name = $_SESSION['admin_user'] ?? 'Admin';
$loan = null;
$message = '';

if (isset($_GET['loan_number'])) {
    $loan_number = $_GET['loan_number'];

    // Query to fetch loan, branch, and associated customer details
    // ASSUMPTION: A BORROWER table links LOAN (loan_number) to CUSTOMER (cust_id)
    $sql = "
        SELECT 
            L.*,
            B.branch_name,
            C.cust_id,
            C.first_name,
            C.last_name,
            C.mobile_no,
            C.city
        FROM 
            LOAN L
        LEFT JOIN 
            BRANCH B ON L.branch_id = B.branch_id
        LEFT JOIN 
            BORROWER BOR ON L.loan_number = BOR.loan_number
        LEFT JOIN 
            CUSTOMER C ON BOR.cust_id = C.cust_id
        WHERE 
            L.loan_number = ?
    ";
    
    // Use prepared statement to fetch data securely
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        $stmt->bind_param("s", $loan_number);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows >= 1) { // A loan might have multiple borrowers, but we fetch the first row
            $loan = $result->fetch_assoc();
        } else {
            $message = "Error: Loan number **" . htmlspecialchars($loan_number) . "** not found.";
        }
        $stmt->close();
    } else {
        $message = "Database query preparation failed.";
    }
} else {
    $message = "Invalid or missing Loan Number.";
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin - Loan #<?php echo htmlspecialchars($loan_number ?? 'N/A'); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Roboto', sans-serif; background-color: #f0f4f8; color: #333; margin: 0; padding: 0; }
        .header { background-color: #dc3545; color: white; padding: 20px; text-align: center; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); }
        .container { max-width: 900px; margin: 30px auto; padding: 30px; background: white; border-radius: 8px; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1); }
        h2 { color: #28a745; border-bottom: 2px solid #eee; padding-bottom: 10px; margin-bottom: 20px; font-weight: 700; }
        h3 { color: #007bff; border-bottom: 1px solid #eee; padding-bottom: 5px; margin-top: 25px; margin-bottom: 15px; font-weight: 500; }
        
        .detail-group { 
            margin-bottom: 10px; 
            display: flex; 
            padding: 8px 0;
        }
        .detail-group strong { 
            display: inline-block; 
            width: 180px; 
            font-weight: 700; 
            color: #555; 
        }
        .detail-value { 
            font-size: 1.1em; 
            color: #333; 
        }

        .message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            font-weight: 500;
            background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb;
        }

        .action-links { margin-top: 30px; }
        .action-links a {
            display: inline-block;
            padding: 10px 15px;
            margin-right: 10px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 700;
            transition: opacity 0.3s;
        }
        .back-link a { color: #007bff; text-decoration: none; font-weight: 500;}
        .back-link a:hover { text-decoration: underline; }
        .customer-link { color: #007bff; text-decoration: underline; }
    </style>
</head>
<body>

    <div class="header">
        <h1>Loan Detail View</h1>
    </div>

    <div class="container">
        <div style="font-size: 1.2em; margin-bottom: 20px; color: #495057;">
            **Admin: <?php echo htmlspecialchars($admin_name); ?>**
        </div>
        
        <?php if ($loan): ?>
            <h2>Loan Number: <?php echo htmlspecialchars($loan['loan_number']); ?></h2>
            
            <!-- Loan Details Section -->
            <h3>Loan Information</h3>
            <div class="detail-group">
                <strong>Loan Type:</strong> 
                <span class="detail-value"><?php echo htmlspecialchars($loan['loan_type']); ?></span>
            </div>
            <div class="detail-group">
                <strong>Principal Amount:</strong> 
                <span class="detail-value">$<?php echo number_format($loan['amount'] ?? 0, 2); ?></span>
            </div>
            <div class="detail-group">
                <strong>Interest Rate:</strong> 
                <span class="detail-value"><?php echo htmlspecialchars($loan['interest_rate']); ?>%</span>
            </div>
            <div class="detail-group">
                <strong>Start Date:</strong> 
                <span class="detail-value"><?php echo htmlspecialchars($loan['start_date']); ?></span>
            </div>
            
            <!-- Branch Details Section -->
            <h3>Branch Details</h3>
            <div class="detail-group">
                <strong>Branch ID:</strong> 
                <span class="detail-value"><?php echo htmlspecialchars($loan['branch_id']); ?></span>
            </div>
            <div class="detail-group">
                <strong>Branch Name:</strong> 
                <span class="detail-value"><?php echo htmlspecialchars($loan['branch_name'] ?? 'N/A'); ?></span>
            </div>

            <!-- Customer/Borrower Details Section -->
            <h3>Borrower Information</h3>
            <?php if ($loan['cust_id']): ?>
                <div class="detail-group">
                    <strong>Customer Name:</strong> 
                    <span class="detail-value"><?php echo htmlspecialchars($loan['first_name'] . ' ' . $loan['last_name']); ?></span>
                </div>
                <div class="detail-group">
                    <strong>Customer ID:</strong> 
                    <span class="detail-value">
                        <!-- Link to Customer View Page if available -->
                        <a href="admin_customer_view.php?cust_id=<?php echo htmlspecialchars($loan['cust_id']); ?>" class="customer-link">
                            <?php echo htmlspecialchars($loan['cust_id']); ?>
                        </a>
                    </span>
                </div>
                <div class="detail-group">
                    <strong>Mobile No.:</strong> 
                    <span class="detail-value"><?php echo htmlspecialchars($loan['mobile_no']); ?></span>
                </div>
                <div class="detail-group">
                    <strong>City:</strong> 
                    <span class="detail-value"><?php echo htmlspecialchars($loan['city']); ?></span>
                </div>
            <?php else: ?>
                <p>No customer linked to this loan in the system (missing **BORROWER** entry).</p>
            <?php endif; ?>

        <?php else: ?>
            <div class="message">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="back-link" style="margin-top: 30px;">
            <a href="admin_loan_portfolio.php">← Back to Loan Portfolio</a>
        </div>
    </div>

</body>
</html>
