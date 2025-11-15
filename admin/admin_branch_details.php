<?php
session_start();
// --- Configuration ---
// Define constants for robust connection settings
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'banking_sys');
define('DB_PORT', 3307);

// --- Database Connection ---
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME, DB_PORT);

if ($conn->connect_error) {
    // Log error securely and display a generic message
    error_log("DB Connection Failed: " . $conn->connect_error);
    die("<h1>System Error: Database connection failed.</h1>");
}

// -----------------------------------------------------------
// 1. Fetch Branch Records (Using the corrected 'assets' column)
// -----------------------------------------------------------
// FIX APPLIED: Changed 'branch_assets' to the correct column name 'assets'
$sql = "SELECT branch_id, branch_name, branch_city, assets FROM BRANCH"; 
$result = $conn->query($sql);

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Branch Management | Admin</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <style>
        body { background-color: #f4f7f9; }
        .container { margin-top: 50px; }
        .back-link { margin-bottom: 20px; display: block; }
        h2 { color: #1f2a38; }
        table { 
            background: white; 
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }
    </style>
</head>
<body>

<div class="container">
    <a href="admin_dashboard.php" class="btn btn-secondary back-link">← Back to Dashboard</a>
    <h2 class="mb-4">🏛️ Bank Branch Management</h2>
    
    <table class="table table-hover table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>City</th>
                <th>Assets (PKR)</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php
            if ($result && $result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    // Use htmlspecialchars() on ALL fetched data for XSS protection
                    $branch_id = htmlspecialchars($row['branch_id']);
                    $branch_name = htmlspecialchars($row['branch_name']);
                    $branch_city = htmlspecialchars($row['branch_city']);
                    
                    // Format the assets value using the CORRECT key 'assets'
                    $assets_formatted = number_format((float)$row['assets'], 2);
                    
                    echo "<tr>
                            <td>{$branch_id}</td>
                            <td>{$branch_name}</td>
                            <td>{$branch_city}</td>
                            <td>PKR {$assets_formatted}</td>
                            <td>
                                <a href='edit_branch.php?id={$branch_id}' class='btn btn-sm btn-info me-2'>Edit</a>
                                <a href='delete_branch.php?id={$branch_id}' class='btn btn-sm btn-danger'>Delete</a>
                            </td>
                          </tr>";
                }
            } else {
                echo "<tr><td colspan='5' class='text-center'>No branch records found.</td></tr>";
            }
            ?>
        </tbody>
    </table>
    
    <div class="mt-4">
        <a href="add_branch.php" class="btn btn-primary">➕ Add New Branch</a>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>