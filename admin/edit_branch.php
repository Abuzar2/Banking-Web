<?php
session_start();
// --- Configuration ---
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'banking_sys');
define('DB_PORT', 3307);

$branch_id = $_GET['id'] ?? null;
$branch = null;
$message = '';
$message_type = '';

if (!$branch_id) {
    die("Error: No branch ID provided.");
}

// --- Database Connection ---
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME, DB_PORT);

if ($conn->connect_error) {
    error_log("DB Connection Failed: " . $conn->connect_error);
    die("<h1>System Error: Database connection failed.</h1>");
}

// -----------------------------------------------------------
// A. HANDLE FORM SUBMISSION (UPDATE)
// -----------------------------------------------------------
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $branch_id_post = $_POST['branch_id'];
    $branch_name = trim($_POST['branch_name']);
    $branch_city = trim($_POST['branch_city']);
    $assets = filter_var(trim($_POST['assets']), FILTER_VALIDATE_FLOAT, ['options' => ['min_range' => 0]]);

    if (empty($branch_name) || empty($branch_city) || $assets === false) {
        $message = "Error: Please fill in all fields correctly.";
        $message_type = 'danger';
    } else {
        $sql = "UPDATE BRANCH SET branch_name = ?, branch_city = ?, assets = ? WHERE branch_id = ?";
        
        if ($stmt = $conn->prepare($sql)) {
            // Note the types: s=string, s=string, d=double, i=integer
            $stmt->bind_param("ssdi", $branch_name, $branch_city, $assets, $branch_id_post);
            
            if ($stmt->execute()) {
                $message = "Success! Branch ID {$branch_id_post} updated successfully.";
                $message_type = 'success';
            } else {
                $message = "Error: Could not execute update query. (" . $stmt->error . ")";
                $message_type = 'danger';
            }
            $stmt->close();
        }
    }
}

// -----------------------------------------------------------
// B. FETCH CURRENT DATA FOR DISPLAY (Initial Load / After Update)
// -----------------------------------------------------------
$sql_fetch = "SELECT branch_id, branch_name, branch_city, assets FROM BRANCH WHERE branch_id = ?";
if ($stmt_fetch = $conn->prepare($sql_fetch)) {
    $stmt_fetch->bind_param("i", $branch_id);
    $stmt_fetch->execute();
    $result = $stmt_fetch->get_result();
    
    if ($result->num_rows == 1) {
        $branch = $result->fetch_assoc();
    } else {
        die("Error: Branch not found.");
    }
    $stmt_fetch->close();
} else {
    die("Error: Could not prepare fetch statement.");
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Branch | Admin</title>
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
    <h2>✏️ Edit Branch: <?php echo htmlspecialchars($branch['branch_name'] ?? 'N/A'); ?></h2>
    
    <?php if ($message): ?>
        <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
            <?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . '?id=' . htmlspecialchars($branch_id); ?>" method="post">
        <!-- Hidden input for the branch ID -->
        <input type="hidden" name="branch_id" value="<?php echo htmlspecialchars($branch['branch_id'] ?? ''); ?>">

        <div class="mb-3">
            <label for="branch_name" class="form-label">Branch Name</label>
            <input type="text" class="form-control" id="branch_name" name="branch_name" 
                   value="<?php echo htmlspecialchars($branch['branch_name'] ?? ''); ?>" required>
        </div>
        <div class="mb-3">
            <label for="branch_city" class="form-label">City</label>
            <input type="text" class="form-control" id="branch_city" name="branch_city" 
                   value="<?php echo htmlspecialchars($branch['branch_city'] ?? ''); ?>" required>
        </div>
        <div class="mb-3">
            <label for="assets" class="form-label">Current Assets (PKR)</label>
            <input type="number" step="0.01" min="0" class="form-control" id="assets" name="assets" 
                   value="<?php echo htmlspecialchars($branch['assets'] ?? ''); ?>" required>
        </div>
        <button type="submit" class="btn btn-success w-100">Save Changes</button>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
