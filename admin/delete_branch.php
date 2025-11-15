<?php
session_start();
// --- Configuration ---
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'banking_sys');
define('DB_PORT', 3307);

$branch_id = $_GET['id'] ?? null;
$message = '';
$message_type = '';

if (!$branch_id) {
    $message = "Error: No branch ID provided for deletion.";
    $message_type = 'danger';
} else {
    // --- Database Connection ---
    $conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME, DB_PORT);
    if ($conn->connect_error) {
        error_log("DB Connection Failed: " . $conn->connect_error);
        $message = "System Error: Database connection failed.";
        $message_type = 'danger';
    } else {
        // Use prepared statement for secure deletion
        $sql = "DELETE FROM BRANCH WHERE branch_id = ?";
        
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("i", $branch_id); // i=integer
            
            if ($stmt->execute()) {
                // If a row was affected, deletion was successful
                if ($stmt->affected_rows > 0) {
                    // Redirect back to the list after successful deletion
                    header("Location: admin_branch_details.php?status=deleted");
                    exit();
                } else {
                    $message = "Error: Branch ID {$branch_id} not found.";
                    $message_type = 'warning';
                }
            } else {
                $message = "Error: Could not execute deletion query. (" . $stmt->error . ")";
                $message_type = 'danger';
            }
            $stmt->close();
        } else {
            $message = "Error: Could not prepare statement. (" . $conn->error . ")";
            $message_type = 'danger';
        }
        $conn->close();
    }
}

// If deletion failed or ID was missing, show an error page
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Delete Branch Status | Admin</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <style>
        body { background-color: #f4f7f9; }
        .container { margin-top: 50px; max-width: 600px; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1); }
    </style>
</head>
<body>

<div class="container text-center">
    <h2 class="mb-4">Branch Deletion Status</h2>
    
    <?php if ($message): ?>
        <div class="alert alert-<?php echo $message_type; ?>" role="alert">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>
    
    <a href="admin_branch_details.php" class="btn btn-primary mt-3">Go Back to Branch List</a>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
