<?php
// MUST BE THE VERY FIRST LINE
session_start();

// -----------------------------------------------------
// 1. SECURITY & CONNECTION SETUP
// -----------------------------------------------------
if (!isset($_GET['welcome'])) {
    header("Location: index.html");
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

$user_first_name = htmlspecialchars($_GET['welcome']);
$message = "";
$default_threshold = 10000.00; 

$threshold = $default_threshold;
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['set_threshold'])) {
    $threshold = floatval($_POST['threshold_amount']);
} elseif (isset($_GET['threshold'])) {
    $threshold = floatval($_GET['threshold']);
}

// -----------------------------------------------------
// 2. SQL QUERY for High-Value Transactions
// -----------------------------------------------------
// Joining TRANSACTION -> ACCOUNT -> customer_account -> CUSTOMER
$sql = "
    SELECT 
        T.trans_id,
        T.trans_type,
        T.amount,
        T.trans_date,
        T.description,
        A.account_number,
        C.first_name,
        C.last_name
    FROM 
        TRANSACTION T
    JOIN 
        ACCOUNT A ON T.account_number = A.account_number
    JOIN
        customer_account CA ON A.account_number = CA.account_number /* <--- NEW: Join to the linking table */
    JOIN
        CUSTOMER C ON CA.cust_id = C.cust_id /* <--- NEW: Join from linking table to CUSTOMER */
    WHERE 
        T.amount >= ?
    ORDER BY 
        T.amount DESC, T.trans_date DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("d", $threshold);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>High-Value Transactions | Admin Panel</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f0f4f8; color: #333; margin: 0; padding: 0; }
        .header { background-color: #dc3545; color: white; padding: 15px; text-align: center; }
        .container { max-width: 1200px; margin: 30px auto; padding: 20px; background: white; border-radius: 8px; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1); }
        h2 { color: #dc3545; margin-bottom: 20px; border-bottom: 2px solid #ccc; padding-bottom: 5px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background-color: #007bff; color: white; font-weight: bold; }
        tr:nth-child(even) { background-color: #f2f2f2; }
        .back-link { display: block; margin-top: 20px; color: #007bff; text-decoration: none; font-weight: bold; }
        
        .form-area { padding: 15px; background-color: #ffe0e0; border: 1px solid #dc3545; border-radius: 6px; margin-bottom: 20px; }
        .form-area label { font-weight: bold; }
        .form-area input[type="number"] { padding: 8px; margin-left: 10px; margin-right: 10px; border: 1px solid #ccc; border-radius: 4px; }
        .form-area button { padding: 8px 15px; background-color: #dc3545; color: white; border: none; border-radius: 4px; cursor: pointer; }
        .warning-row { background-color: #fff3cd; color: #856404; font-weight: bold; }
    </style>
</head>
<body>

    <div class="header">
        <h1>High-Value Transaction Monitoring</h1>
    </div>

    <div class="container">
        
        <?php echo $message; ?>

        <div class="form-area">
            <form method="POST" action="admin_high_value_trans.php?welcome=<?php echo $user_first_name; ?>">
                <label for="threshold_amount">Set High-Value Threshold:</label>
                <input type="number" name="threshold_amount" step="1.00" min="1" value="<?php echo number_format($threshold, 2, '.', ''); ?>" required>
                <button type="submit" name="set_threshold">Apply Filter</button>
                <span style="margin-left: 20px; color: #dc3545;">
                    *Showing transactions of **$<?php echo number_format($threshold, 2); ?>** or more.
                </span>
            </form>
        </div>
        
        <h2>Flagged Transactions (Amount >= $<?php echo number_format($threshold, 2); ?>)</h2>
        
        <?php
        if ($result->num_rows > 0) {
            echo "<table>";
            echo "<thead><tr><th>Date</th><th>ID</th><th>Type</th><th>Amount</th><th>Account No.</th><th>Customer Name</th><th>Description</th></tr></thead>";
            echo "<tbody>";
            
            while($row = $result->fetch_assoc()) {
                $amount_display = "$" . number_format($row["amount"], 2);
                $class = 'warning-row'; 

                echo "<tr class='{$class}'>";
                echo "<td>" . $row["trans_date"] . "</td>";
                echo "<td>" . $row["trans_id"] . "</td>";
                echo "<td>" . $row["trans_type"] . "</td>"; 
                echo "<td>" . $amount_display . "</td>";
                echo "<td>" . $row["account_number"] . "</td>";
                echo "<td>" . $row["last_name"] . ", " . $row["first_name"] . "</td>";
                echo "<td>" . $row["description"] . "</td>";
                echo "</tr>";
            }
            
            echo "</tbody>";
            echo "</table>";
        } else {
            echo "<p>No transactions found exceeding the **$<?php echo number_format($threshold, 2); ?>** threshold.</p>";
        }

        $stmt->close();
        $conn->close();
        ?>

        <a class="back-link" href="admin_dashboard.php?welcome=<?php echo $user_first_name; ?>">← Back to Admin Dashboard</a>
    </div>

</body>
</html>