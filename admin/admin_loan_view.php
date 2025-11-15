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

$loan_number = $_GET['loan_number'] ?? null;
$loan_details = [];
$customer_details = [];

if ($loan_number) {
    // Fetch loan details
    $sql_loan = "SELECT L.*, B.branch_name, B.branch_city 
                 FROM loan L 
                 LEFT JOIN branch B ON L.branch_id = B.branch_id 
                 WHERE L.loan_number = ?";
    $stmt = $conn->prepare($sql_loan);
    $stmt->bind_param("i", $loan_number);
    $stmt->execute();
    $loan_details = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    // Fetch customer details for this loan
    if ($loan_details) {
        $sql_customers = "SELECT C.cust_id, C.first_name, C.last_name, C.city, C.mobile_no
                          FROM customer C
                          JOIN customer_loan CL ON C.cust_id = CL.cust_id
                          WHERE CL.loan_number = ?";
        $stmt_cust = $conn->prepare($sql_customers);
        $stmt_cust->bind_param("i", $loan_number);
        $stmt_cust->execute();
        $customer_details = $stmt_cust->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt_cust->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Loan Details - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Roboto', sans-serif; background-color: #f0f4f8; margin: 0; padding: 0; }
        .header { background-color: #dc3545; color: white; padding: 20px; text-align: center; }
        .container { max-width: 1000px; margin: 30px auto; padding: 20px; background: white; border-radius: 8px; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1); }
        .loan-card, .customer-card { background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #007bff; }
        .back-link { display: inline-block; margin-bottom: 20px; color: #007bff; text-decoration: none; font-weight: 500; }
        .back-link:hover { text-decoration: underline; }
        .detail-row { display: flex; margin-bottom: 10px; }
        .detail-label { font-weight: bold; width: 150px; color: #495057; }
        .detail-value { color: #212529; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        th { background-color: #28a745; color: white; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Loan Details</h1>
    </div>
    
    <div class="container">
        <a href="admin_loan_details.php" class="back-link">← Back to Loan Portfolio</a>
        
        <?php if ($loan_details): ?>
            <div class="loan-card">
                <h2>Loan Information</h2>
                <div class="detail-row">
                    <div class="detail-label">Loan Number:</div>
                    <div class="detail-value"><?php echo htmlspecialchars($loan_details['loan_number']); ?></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Loan Type:</div>
                    <div class="detail-value"><?php echo htmlspecialchars($loan_details['loan_type']); ?></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Amount:</div>
                    <div class="detail-value">$<?php echo number_format($loan_details['amount'], 2); ?></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Interest Rate:</div>
                    <div class="detail-value"><?php echo htmlspecialchars($loan_details['interest_rate']); ?>%</div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Start Date:</div>
                    <div class="detail-value"><?php echo htmlspecialchars($loan_details['start_date']); ?></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Branch:</div>
                    <div class="detail-value"><?php echo htmlspecialchars($loan_details['branch_name'] . ' - ' . $loan_details['branch_city']); ?></div>
                </div>
            </div>

            <div class="customer-card">
                <h2>Associated Customers</h2>
                <?php if (!empty($customer_details)): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Customer ID</th>
                                <th>Name</th>
                                <th>City</th>
                                <th>Mobile</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($customer_details as $customer): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($customer['cust_id']); ?></td>
                                    <td><?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']); ?></td>
                                    <td><?php echo htmlspecialchars($customer['city']); ?></td>
                                    <td><?php echo htmlspecialchars($customer['mobile_no']); ?></td>
                                    <td>
                                        <a href="admin_customer_view.php?cust_id=<?php echo $customer['cust_id']; ?>" 
                                           style="color: #007bff; text-decoration: none;">View Customer</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>No customers associated with this loan.</p>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <p>Loan not found.</p>
        <?php endif; ?>
    </div>
</body>
</html>