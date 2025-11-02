<?php
session_start();
// --- Configuration ---
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'banking_sys');
define('DB_PORT', 3307);

$message = '';
$message_type = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Validate and sanitize inputs
    $branch_name = trim($_POST['branch_name']);
    $branch_city = trim($_POST['branch_city']);
    // Assets must be numeric and non-negative
    $assets = filter_var(trim($_POST['assets']), FILTER_VALIDATE_FLOAT, ['options' => ['min_range' => 0]]);

    if (empty($branch_name) || empty($branch_city) || $assets === false) {
        $message = "Error: Please fill in all fields correctly. Assets must be a valid non-negative number.";
        $message_type = 'danger';
    } else {
        // --- Database Connection ---
        $conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME, DB_PORT);
        if ($conn->connect_error) {
            error_log("DB Connection Failed: " . $conn->connect_error);
            $message = "System Error: Database connection failed.";
            $message_type = 'danger';
        } else {
            // Use prepared statement for secure insertion
            // Note: Using the column name 'assets' as confirmed previously
            $sql = "INSERT INTO BRANCH (branch_name, branch_city, assets) VALUES (?, ?, ?)";
            
            if ($stmt = $conn->prepare($sql)) {
                $stmt->bind_param("ssd", $branch_name, $branch_city, $assets); // s=string, s=string, d=double (for float/decimal)
                
                if ($stmt->execute()) {
                    $message = "Success! New branch '{$branch_name}' added successfully.";
                    $message_type = 'success';
                } else {
                    $message = "Error: Could not execute insertion query. (" . $stmt->error . ")";
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
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Branch | Admin</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <style>
        body { background-color: #f4f7f9; }
        .container { margin-top: 50px; max-width: 600px; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1); }
        h2 { color: #1f2a38; margin-bottom: 25px; }
    </style>
</head>
<body>

<div class="container">
    <a href="admin_branch_details.php" class="btn btn-secondary back-link mb-4">← Back to Branch List</a>
    <h2>➕ Add New Bank Branch</h2>
    
    <?php if ($message): ?>
        <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
            <?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
        <div class="mb-3">
            <label for="branch_name" class="form-label">Branch Name</label>
            <input type="text" class="form-control" id="branch_name" name="branch_name" required>
        </div>
        <div class="mb-3">
            <label for="branch_city" class="form-label">City</label>
            <input type="text" class="form-control" id="branch_city" name="branch_city" required>
        </div>
        <div class="mb-3">
            <label for="assets" class="form-label">Initial Assets (PKR)</label>
            <input type="number" step="0.01" min="0" class="form-control" id="assets" name="assets" required>
        </div>
        <button type="submit" class="btn btn-primary w-100">Create Branch</button>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
