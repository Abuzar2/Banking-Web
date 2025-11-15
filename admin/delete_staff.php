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

$message = "";
$staff_id = $_GET['staff_id'] ?? null; // Get staff ID from URL

if ($staff_id) {
    // 1. Prepare the DELETE SQL statement
    $sql_delete = "DELETE FROM STAFF WHERE staff_id = ?";
    $stmt_delete = $conn->prepare($sql_delete);
    $stmt_delete->bind_param("i", $staff_id); // 'i' for integer staff_id

    // 2. Execute the query
    if ($stmt_delete->execute()) {
        // Successful deletion, redirect immediately
        $conn->close();
        header("Location: admin_staff_details.php?message=deleted&staff_id=" . urlencode($staff_id));
        exit();
    } else {
        $message = "<p class='error'>❌ Error deleting staff ID **$staff_id**: " . $conn->error . "</p>";
    }
} else {
    $message = "<p class='error'>❌ No Staff ID provided for deletion.</p>";
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin - Delete Staff</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Roboto', sans-serif; background-color: #f0f4f8; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 50px auto; padding: 30px; background: white; border-radius: 12px; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1); }
        h2 { color: #dc3545; border-bottom: 2px solid #eee; padding-bottom: 10px; margin-bottom: 20px; font-weight: 700; }
        .error { color: red; font-weight: bold; background-color: #fcebeb; padding: 10px; border-radius: 4px; }
        .back-link { display: block; margin-top: 25px; font-size: 1em; }
        .back-link a { color: #007bff; text-decoration: none; }
        .back-link a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="container">
        <h2>⚠️ Staff Deletion Status</h2>
        <?php echo $message; ?>
        <div class="back-link">
            <a href="admin_staff_details.php">← Back to Staff List</a>
        </div>
    </div>
</body>
</html>
