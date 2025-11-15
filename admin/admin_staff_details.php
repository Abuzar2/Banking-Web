<?php
// MUST BE THE VERY FIRST LINE
session_start();

// -----------------------------------------------------------------
// 1. Configuration & Constants for clarity and easier changes
// -----------------------------------------------------------------
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'banking_sys');
define('DB_PORT', 3307); 

// Check if admin is logged in, otherwise redirect to login page
if (!isset($_SESSION['admin_user'])) {
    header("Location: admin_login.php");
    exit();
}

// --- Database Connection ---
$conn = @new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME, DB_PORT);
$db_connected = !$conn->connect_error;

$admin_name = htmlspecialchars($_SESSION['admin_user'] ?? 'Administrator');
$staff_data = [];
$success_message = "";
$error_message = '';

if ($db_connected) {
    // Check for successful deletion message
    if (isset($_GET['message']) && $_GET['message'] == 'deleted' && isset($_GET['staff_id'])) {
        $deleted_id = htmlspecialchars($_GET['staff_id']);
        $success_message = "<div class='alert alert-success alert-dismissible fade show' role='alert'>
                            🗑️ Staff ID **$deleted_id** was successfully deleted.
                            <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
                        </div>";
    }

    // -----------------------------------------------------
    // 2. Fetch ALL Staff Details
    // -----------------------------------------------------
    $sql_staff = "
        SELECT 
            staff_id, 
            first_name, 
            last_name, 
            position,
            salary, 
            hire_date,
            branch_id
        FROM 
            STAFF
        ORDER BY 
            staff_id ASC
    ";

    try {
        $result = $conn->query($sql_staff);
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $staff_data[] = $row;
            }
            $result->free();
        }
    } catch (mysqli_sql_exception $e) {
        error_log("SQL Error in staff details query: " . $e->getMessage());
        $error_message = "SQL Error during staff data fetching.";
    }

    $conn->close();
} else {
    $error_message = "Database connection failed: " . $conn->connect_error;
}


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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Oversight - Admin</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --purple: #6f42c1; /* Deep Violet for the professional finance accent */
            --dark-header: #1f2a38;
            --light-bg: #f4f7f9;
        }
        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--light-bg);
            padding-top: 75px; /* Space for fixed navbar */
        }
        /* Top Navigation Bar */
        .navbar {
            background-color: var(--dark-header) !important; 
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        .user-info {
            color: #ced4da;
            margin-right: 15px;
            font-size: 0.95rem;
        }
        /* Main Content Wrapper */
        .main-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            padding: 30px;
            background-color: white;
        }
        .btn-purple {
            background-color: var(--purple);
            border-color: var(--purple);
            color: white;
            transition: background-color 0.2s;
        }
        .btn-purple:hover {
            background-color: #5a359c; 
            border-color: #5a359c;
            color: white;
        }
        
        /* Table Styling */
        .staff-table th {
            background-color: var(--purple); /* Purple Header for the table */
            color: white;
            font-weight: 600;
            vertical-align: middle;
            border-bottom: 2px solid #dee2e6;
            padding: 15px 10px;
            font-size: 0.95rem;
        }
        .staff-table td {
            vertical-align: middle;
            padding: 15px 10px;
        }
        .staff-table tr:hover {
            background-color: #f8f9fa;
        }
        .staff-table tbody tr:last-child td {
            border-bottom: none; /* Clean look for the last row */
        }


        /* Focus on Title */
        .page-title {
            color: var(--dark-header);
            border-left: 5px solid var(--purple); /* Purple accent bar */
            padding-left: 15px;
            font-weight: 700;
        }
    </style>
</head>
<body>

<!-- Fixed Dark Navigation Bar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
    <div class="container-fluid">
        <a class="navbar-brand" href="admin_dashboard.php">
            <span class="fs-4">🏦</span> BANKING ADMIN SYSTEM
        </a>
        <div class="d-flex align-items-center">
            <span class="user-info">Hello, **<?php echo $admin_name; ?>**</span>
            <a href="admin_dashboard.php?logout=true" class="btn btn-outline-light btn-sm">
                <small>Logout</small>
            </a>
        </div>
    </div>
</nav>

<div class="container py-5"> 
    
    <h2 class="page-title mb-5">👥 Staff Oversight & Management</h2>
    
    <?php if (!$db_connected): ?>
        <div class="alert alert-danger mb-4" role="alert">
            🚨 **Connection Alert:** Failed to connect to the database. (<?php echo htmlspecialchars($conn->connect_error ?? $error_message); ?>)
        </div>
    <?php elseif ($error_message): ?>
        <div class="alert alert-warning mb-4" role="alert">
            ⚠️ **Data Alert:** Data could not be fully fetched. <?php echo htmlspecialchars($error_message); ?>
        </div>
    <?php endif; ?>

    <div class="main-card">
        
        <?php echo $success_message; ?>

        <!-- Controls: Search and Add New -->
        <div class="row mb-4 align-items-center">
            <div class="col-md-8">
                <input type="text" class="form-control rounded-pill" placeholder="Search staff by ID, Name, or Position...">
            </div>
            <div class="col-md-4 text-end">
                <a href="add_staff.php" class="btn btn-purple rounded-pill px-4">
                    ➕ Add New Staff
                </a>
            </div>
        </div>
        
        <!-- Staff Data Table -->
        <div class="table-responsive">
            <?php if (!empty($staff_data)): ?>
                <table class="table table-hover align-middle staff-table">
                    <thead>
                        <tr>
                            <th scope="col">ID</th>
                            <th scope="col">First Name</th>
                            <th scope="col">Last Name</th>
                            <th scope="col">Position</th>
                            <th scope="col" class="text-end">Salary</th>
                            <th scope="col">Hire Date</th>
                            <th scope="col" class="text-center">Branch ID</th>
                            <th scope="col" class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($staff_data as $staff): ?>
                            <tr>
                                <td><a href="#" class='text-decoration-none text-primary fw-bold'>**<?php echo htmlspecialchars($staff['staff_id']); ?>**</a></td>
                                <td><?php echo htmlspecialchars($staff['first_name']); ?></td>
                                <td><?php echo htmlspecialchars($staff['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($staff['position']); ?></td>
                                <td class="text-end fw-semibold">$<?php echo number_format($staff['salary'], 2); ?></td>
                                <td><?php echo htmlspecialchars($staff['hire_date']); ?></td>
                                <td class="text-center"><?php echo htmlspecialchars($staff['branch_id']); ?></td>
                                <td class="text-center">
                                    <!-- Edit Button -->
                                    <a href="edit_staff.php?staff_id=<?php echo urlencode($staff['staff_id']); ?>" class="btn btn-sm btn-outline-info" title="Edit Staff Record">
                                        Edit
                                    </a>
                                    
                                    <!-- Delete Button with Confirmation -->
                                    <a href="delete_staff.php?staff_id=<?php echo urlencode($staff['staff_id']); ?>" 
                                       class="btn btn-sm btn-danger" 
                                       title="Delete Staff Record"
                                       onclick="return confirm('Are you sure you want to delete Staff ID: <?php echo htmlspecialchars($staff['staff_id']); ?>? This action is irreversible.');">
                                        Delete
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="text-center text-muted py-5">
                    No staff records found in the database. Use the "Add New Staff" button to create one.
                </p>
            <?php endif; ?>
        </div>
        
        <div class="mt-4 text-center">
            <a href="admin_dashboard.php" class="text-decoration-none text-secondary fw-semibold">← Back to Dashboard</a>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
