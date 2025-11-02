<?php
// Database Configuration
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'banking_sys');
define('DB_PORT', 3307);

// Connect to Database
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME, DB_PORT);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cust_id = $_POST['cust_id'] ?? '';

    $stmt = $conn->prepare("DELETE FROM customers WHERE cust_id = ?");
    $stmt->bind_param("i", $cust_id);

    if ($stmt->execute()) {
        header("Location: admin_customer_management.php?success=Customer+deleted+successfully");
    } else {
        header("Location: admin_customer_management.php?error=Error+deleting+customer");
    }

    $stmt->close();
}
$conn->close();
?>
