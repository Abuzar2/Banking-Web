<?php
session_start();
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
$admin_name = $_SESSION['admin_user'] ?? htmlspecialchars($_GET['welcome'] ?? 'Admin');
$transaction_data = [];

// -----------------------------------------------------
// 1. Fetch ALL Transaction Details (Using corrected schema)
// NOTE: Assuming the table name is 'transaction'
// -----------------------------------------------------
$sql_tran = "
    SELECT 
        T.trans_id,               /* Corrected Column Name */
        T.account_number,         /* Corrected Column Name */
        T.trans_date,             /* Corrected Column Name */
        T.trans_type,             /* Corrected Column Name */
        T.amount,                 /* Corrected Column Name */
        T.description             /* Added description */
    FROM 
        transaction T             /* <<<--- CHECK THIS TABLE NAME: 'transaction' */
    ORDER BY 
        T.trans_date DESC
    LIMIT 100 
";

$result = $conn->query($sql_tran);

if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $transaction_data[] = $row;
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
    <title>Admin - Transaction History</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f0f4f8; color: #333; margin: 0; padding: 0; }
        .header { background-color: #dc3545; color: white; padding: 20px; text-align: center; }
        .container { max-width: 1200px; margin: 30px auto; padding: 20px; background: white; border-radius: 8px; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1); }
        h2 { color: #007bff; border-bottom: 2px solid #ccc; padding-bottom: 10px; margin-bottom: 20px; }
        .tran-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .tran-table th, .tran-table td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        .tran-table th { background-color: #007bff; color: white; }
        .tran-table tr:nth-child(even) { background-color: #f2f2f2; }
        .type-deposit { color: green; font-weight: bold; }
        .type-withdrawal { color: #dc3545; font-weight: bold; }
        .type-transfer { color: #ffc107; font-weight: bold; }
        .back-link { display: block; margin-top: 20px; font-size: 1.1em; }
    </style>
</head>
<body>

    <div class="header">
        <h1>Global Transaction History</h1>
    </div>

    <div class="container">
        <div style="font-size: 1.2em; margin-bottom: 20px;">
            **Admin: <?php echo htmlspecialchars($admin_name); ?>**
        </div>
        
        <h2>📊 Recent Transactions (Global)</h2>

        <?php if (!empty($transaction_data)): ?>
            <table class="tran-table">
                <thead>
                    <tr>
                        <th>Tran ID</th>
                        <th>Account No.</th>
                        <th>Date & Time</th>
                        <th>Type</th>
                        <th>Amount</th>
                        <th>Description</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($transaction_data as $tran): ?>
                        <?php 
                            $tran_type = htmlspecialchars($tran['trans_type']);
                            $amount_class = '';
                            if (strtolower($tran_type) == 'deposit') {
                                $amount_class = 'type-deposit';
                            } elseif (strtolower($tran_type) == 'withdrawal') {
                                $amount_class = 'type-withdrawal';
                            } else {
                                $amount_class = 'type-transfer';
                            }
                        ?>
                        <tr>
                            <td>**<?php echo htmlspecialchars($tran['trans_id']); ?>**</td>
                            <td><?php echo htmlspecialchars($tran['account_number']); ?></td>
                            <td><?php echo htmlspecialchars($tran['trans_date']); ?></td>
                            <td class="<?php echo $amount_class; ?>"><?php echo $tran_type; ?></td>
                            <td class="<?php echo $amount_class; ?>">
                                $<?php echo number_format($tran['amount'] ?? 0, 2); ?>
                            </td>
                            <td><?php echo htmlspecialchars($tran['description'] ?? 'N/A'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No recent transaction records found in the database. Please check if the table is named **'transaction'**.</p>
        <?php endif; ?>

        <div class="back-link">
            <a href="admin_dashboard.php">← Back to Dashboard</a>
        </div>
    </div>

</body>
</html>