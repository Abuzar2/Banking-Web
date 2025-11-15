<?php
// MUST BE THE VERY FIRST LINE
session_start();

// -----------------------------------------------------
// 1. SECURITY & CONNECTION SETUP
// -----------------------------------------------------
// Check if the admin is logged in or if a welcome name is provided
if (!isset($_SESSION['admin_user']) && !isset($_GET['welcome'])) {
    header("Location: index.html");
    exit();
}

// Database Connection Details (Using your established credentials)
$servername = "localhost";
$username = "root";   
$password = ""; 
$dbname = "banking_sys";
$port = 3307;

$conn = new mysqli($servername, $username, $password, $dbname, $port);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Retrieve admin name for the dashboard link
$user_first_name = $_SESSION['admin_user'] ?? htmlspecialchars($_GET['welcome']);

// -----------------------------------------------------
// 2. SQL QUERY (With Server-Side Filtering)
// -----------------------------------------------------

$search_term = trim($_GET['search'] ?? '');
$where_clause = '';

if (!empty($search_term)) {
    // Escape the search term for security and wrap it with wildcards
    $safe_search = "%" . $conn->real_escape_string($search_term) . "%";
    
    // Construct the WHERE clause to search across multiple columns
    $where_clause = "
        WHERE 
            user_role LIKE '{$safe_search}' OR 
            action_type LIKE '{$safe_search}' OR
            details LIKE '{$safe_search}' OR
            user_id LIKE '{$safe_search}'
    ";
}

$sql = "
    SELECT 
        log_id,
        timestamp,
        user_role,
        user_id, 
        action_type,
        details
    FROM 
        AUDIT_LOG
    {$where_clause}
    ORDER BY 
        timestamp DESC
    LIMIT 500
";

$result = $conn->query($sql);
$log_error = $conn->error;

// Function to map action type to Bootstrap badge class for better visualization
function get_action_badge($action) {
    $action = strtolower($action);
    if (strpos($action, 'login') !== false || strpos($action, 'read') !== false || strpos($action, 'view') !== false) {
        return 'bg-success'; // Success for simple logins/reads
    } elseif (strpos($action, 'create') !== false || strpos($action, 'add') !== false || strpos($action, 'new') !== false) {
        return 'bg-primary'; // Primary for creation
    } elseif (strpos($action, 'update') !== false || strpos($action, 'edit') !== false) {
        return 'bg-info'; // Info for updates
    } elseif (strpos($action, 'delete') !== false || strpos($action, 'remove') !== false) {
        return 'bg-danger'; // Danger for deletions
    } elseif (strpos($action, 'fail') !== false || strpos($action, 'error') !== false) {
        return 'bg-warning text-dark'; // Warning for errors/failures
    } else {
        return 'bg-secondary'; // Default
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Audit Log | Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    
    <style>
        body { 
            font-family: 'Poppins', sans-serif; 
            background-color: #f4f7f9; 
            padding-top: 56px; 
        }
        .navbar {
            background-color: #1f2a38 !important; /* Dark header */
        }
        .container-fluid {
            padding: 20px;
        }
        .table-responsive {
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            padding: 15px;
        }
        .table thead th {
            background-color: #0d6efd;
            color: white;
            position: sticky;
            top: 0;
            z-index: 10;
        }
        .table tbody tr:hover {
            background-color: #e9ecef;
        }
        .back-link { 
            font-weight: 600; 
            margin-top: 20px;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
    <div class="container-fluid">
        <a class="navbar-brand" href="admin_dashboard.php?welcome=<?php echo $user_first_name; ?>">
            <span class="fs-4">🏦</span> ADMIN DASHBOARD
        </a>
    </div>
</nav>

<div class="container-fluid">
    <h1 class="mt-4 mb-4 text-primary">System Audit Log</h1>

    <!-- Search/Filter Form -->
    <div class="card mb-4 shadow-sm">
        <div class="card-body">
            <form class="d-flex" method="GET" action="admin_log_audits.php">
                <input 
                    type="search" 
                    name="search" 
                    class="form-control form-control-lg me-2" 
                    placeholder="Search logs by Role, Action, or Details..." 
                    value="<?php echo htmlspecialchars($search_term); ?>"
                >
                <button class="btn btn-primary" type="submit">Search</button>
                <a href="admin_log_audits.php" class="btn btn-outline-secondary ms-2">Clear</a>
                <input type="hidden" name="welcome" value="<?php echo $user_first_name; ?>">
            </form>
        </div>
    </div>

    <div class="table-responsive">
        <?php
        // Check if the query executed successfully
        if ($log_error) {
            echo "<div class='alert alert-danger' role='alert'>";
            echo "❌ <strong>Database Error:</strong> The `AUDIT_LOG` table could not be queried. Please ensure it exists. <br>";
            echo "Error details: " . htmlspecialchars($log_error);
            echo "</div>";
        } elseif ($result && $result->num_rows > 0) {
            echo "<p class='text-muted'>Displaying " . $result->num_rows . " recent logs (max 500 records).</p>";
            echo "<table class='table table-striped table-hover align-middle'>";
            echo "<thead><tr><th>Log ID</th><th>Timestamp</th><th>Role</th><th>User ID</th><th>Action Type</th><th>Details</th></tr></thead>";
            echo "<tbody>";
            
            while($row = $result->fetch_assoc()) {
                $badge_class = get_action_badge($row['action_type']);
                
                echo "<tr>";
                echo "<td>" . htmlspecialchars($row["log_id"]) . "</td>";
                echo "<td>" . htmlspecialchars($row["timestamp"]) . "</td>";
                echo "<td><span class='badge {$badge_class}'>" . htmlspecialchars($row["user_role"]) . "</span></td>";
                echo "<td>" . htmlspecialchars($row["user_id"]) . "</td>";
                echo "<td><span class='badge {$badge_class}'>" . htmlspecialchars($row["action_type"]) . "</span></td>";
                echo "<td><code class='text-break small'>" . htmlspecialchars($row["details"]) . "</code></td>";
                echo "</tr>";
            }
            
            echo "</tbody>";
            echo "</table>";
        } else {
            echo "<div class='alert alert-info' role='alert'>";
            echo "ℹ️ No audit log records found.";
            if (!empty($search_term)) {
                echo " (Check your search term: <strong>" . htmlspecialchars($search_term) . "</strong>)";
            }
            echo "</div>";
        }
        ?>
    </div>

    <a class="back-link btn btn-outline-primary mt-3" href="admin_dashboard.php?welcome=<?php echo $user_first_name; ?>">← Back to Admin Dashboard</a>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
