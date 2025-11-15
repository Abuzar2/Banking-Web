<?php
session_start();

// Security Check: Only Managers should access this.
if (!isset($_SESSION['staff_id']) || $_SESSION['staff_position'] !== 'Manager') {
    // If a non-manager tries to access, redirect them.
    header("Location: staff_dashboard.php");
    exit();
}

// Database Connection Details
$servername = "localhost";
$username = "root";   
$password = ""; 
$dbname = "banking_sys";
$port = 3307;

$conn = new mysqli($servername, $username, $password, $dbname, $port);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$audit_entries = [];
$filter_staff_id = htmlspecialchars($_GET['filter_staff_id'] ?? '');
$filter_days = filter_var($_GET['filter_days'] ?? 30, FILTER_VALIDATE_INT);

// -----------------------------------------------------
// 1. Construct the Audit Log Query
// -----------------------------------------------------

$sql = "SELECT * FROM audit_log WHERE 1=1 ";
$params = [];
$types = '';

// Filter by Staff ID
if (!empty($filter_staff_id) && is_numeric($filter_staff_id)) {
    $sql .= " AND user_id = ? ";
    $types .= 'i';
    $params[] = $filter_staff_id;
}

// Filter by Date Range (Last N days)
if ($filter_days > 0) {
    // Calculate the date boundary
    $date_boundary = date('Y-m-d H:i:s', strtotime("-$filter_days days"));
    $sql .= " AND timestamp >= ? ";
    $types .= 's';
    $params[] = $date_boundary;
}

// Final ordering
$sql .= " ORDER BY timestamp DESC";

// --- Execute Query ---
$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $audit_entries[] = $row;
}

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Log - Manager Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        /* General Setup */
        body { font-family: 'Inter', sans-serif; background-color: #f0f4f8; color: #34495e; margin: 0; padding: 0; }
        
        /* Header & Navigation */
        .header { 
            background-color: #004c8c; 
            color: white; 
            padding: 15px 40px; 
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        }
        .logo h1 { font-size: 1.5em; font-weight: 700; margin: 0; }
        .nav-links a { 
            color: white; 
            text-decoration: none; 
            margin-left: 20px; 
            padding: 5px 10px;
            border-radius: 4px;
            transition: background-color 0.3s;
        }
        .nav-links a:hover { background-color: #0056b3; }

        .container { max-width: 1300px; margin: 30px auto; padding: 0 20px; }
        
        h2 { 
            color: #004c8c; 
            border-bottom: 3px solid #e0e6ed; 
            padding-bottom: 8px; 
            margin-bottom: 25px; 
            font-weight: 700;
            font-size: 1.8em;
        }
        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #007bff;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.2s;
        }
        .back-link:hover { color: #0056b3; text-decoration: underline; }

        /* Filter Form */
        .filter-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 30px;
        }
        .filter-form {
            display: flex;
            gap: 15px;
            align-items: center;
        }
        .filter-form label {
            font-weight: 600;
            color: #555;
            white-space: nowrap;
        }
        .filter-form input, .filter-form select {
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 1em;
        }
        .filter-form button {
            background-color: #ff9800;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: background-color 0.3s;
        }
        .filter-form button:hover { background-color: #e68900; }

        /* Table Styling */
        .audit-table { 
            width: 100%; 
            border-collapse: separate; 
            border-spacing: 0;
            margin-top: 20px; 
            overflow: hidden;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
            background-color: white;
        }
        .audit-table th, .audit-table td { 
            padding: 12px 15px; 
            text-align: left; 
            border-bottom: 1px solid #e0e6ed; 
            font-size: 0.9em;
        }
        .audit-table th { 
            background-color: #007bff; 
            color: white; 
            font-weight: 600;
            text-transform: uppercase;
        }
        .audit-table tbody tr:hover { 
            background-color: #f0f3f6; 
        }
        .action-cell { font-weight: 600; }
        .details-cell { white-space: normal; }

        /* Status colors */
        .ACTION_TYPE_DEPOSIT { color: #2ecc71; }
        .ACTION_TYPE_WITHDRAWAL { color: #e74c3c; }
        .ACTION_TYPE_ACCOUNT_OPEN { color: #3498db; }
        .ACTION_TYPE_LOAN_APPROVAL { color: #f39c12; }
    </style>
</head>
<body>

    <div class="header">
        <div class="logo">
            <h1>MANAGER PORTAL (AUDIT LOG)</h1>
        </div>
        <div class="nav-links">
            <a href="staff_dashboard.php">Dashboard</a>
        </div>
    </div>

    <div class="container">
        
        <a href="staff_dashboard.php" class="back-link">&leftarrow; Back to Dashboard</a>
        <h2>System Audit & Security Log</h2>

        <!-- Filter Card -->
        <div class="filter-card">
            <form method="GET" action="staff_audit_log.php" class="filter-form">
                
                <label for="filter_days">Show Logs from Last:</label>
                <select id="filter_days" name="filter_days">
                    <option value="7" <?php echo $filter_days == 7 ? 'selected' : ''; ?>>7 Days</option>
                    <option value="30" <?php echo $filter_days == 30 ? 'selected' : ''; ?>>30 Days</option>
                    <option value="90" <?php echo $filter_days == 90 ? 'selected' : ''; ?>>90 Days</option>
                    <option value="365" <?php echo $filter_days == 365 ? 'selected' : ''; ?>>1 Year</option>
                </select>

                <label for="filter_staff_id">Filter by Staff ID:</label>
                <input type="text" id="filter_staff_id" name="filter_staff_id" placeholder="e.g., 101" value="<?php echo htmlspecialchars($filter_staff_id); ?>">
                
                <button type="submit">Apply Filters</button>
                <a href="staff_audit_log.php" class="logout-btn" style="background-color: #95a5a6; color: white; padding: 10px 15px; border-radius: 6px; text-decoration: none; font-weight: 600;">Clear</a>
            </form>
        </div>

        <!-- Audit Log Table -->
        <?php if (!empty($audit_entries)): ?>
            <p style="font-style: italic; color: #7f8c8d;">Displaying <?php echo count($audit_entries); ?> entries, most recent first.</p>
            <table class="audit-table">
                <thead>
                    <tr>
                        <th style="width: 15%;">Timestamp</th>
                        <th style="width: 10%;">User ID</th>
                        <th style="width: 10%;">User Role</th>
                        <th style="width: 15%;">Action Type</th>
                        <th style="width: 35%;">Details</th>
                        <th style="width: 15%;">IP Address</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($audit_entries as $entry): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($entry['timestamp']); ?></td>
                            <td><?php echo htmlspecialchars($entry['user_id']); ?></td>
                            <td><?php echo htmlspecialchars($entry['user_role']); ?></td>
                            <td class="action-cell <?php echo 'ACTION_TYPE_' . str_replace('/', '_', strtoupper($entry['action_type'])); ?>">
                                <?php echo htmlspecialchars($entry['action_type']); ?>
                            </td>
                            <td class="details-cell"><?php echo htmlspecialchars($entry['details']); ?></td>
                            <td><?php echo htmlspecialchars($entry['ip_address']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="alert error" style="background-color: #fff3cd; color: #856404; border: 1px solid #ffeeba;">
                No audit log entries found based on the current filters.
            </div>
        <?php endif; ?>
        
        <div style="height: 40px;"></div>

    </div>

</body>
</html>
