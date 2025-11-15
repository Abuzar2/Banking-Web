<?php
// -----------------------------------------------------
// 1. SESSION START AND SECURITY CHECK
// -----------------------------------------------------
session_start();

if (!isset($_GET['welcome'])) {
    header("Location: index.html");
    exit();
}

// Database Connection Details
$servername = "localhost";
$username = "root";   // UPDATE THIS
$password = ""; // UPDATE THIS
$dbname = "banking_sys";
$port = 3307;

$conn = new mysqli($servername, $username, $password, $dbname, $port);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$user_first_name = htmlspecialchars($_GET['welcome']);
$message = "";

// -----------------------------------------------------
// 2. HANDLE ADD STAFF (INSERT)
// -----------------------------------------------------
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_staff'])) {
    $fn = $_POST['first_name'];
    $ln = $_POST['last_name'];
    $pos = $_POST['position'];
    $sal = $_POST['salary'];
    $hd = $_POST['hire_date'];
    $bid = $_POST['branch_id'];

    // Use prepared statements for security
    $stmt = $conn->prepare("INSERT INTO STAFF (first_name, last_name, position, salary, hire_date, branch_id) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssdsi", $fn, $ln, $pos, $sal, $hd, $bid);

    if ($stmt->execute()) {
        $message = "<div style='color: green;'>✅ Staff member **{$fn} {$ln}** added successfully!</div>";
    } else {
        $message = "<div style='color: red;'>❌ Error adding staff: " . $stmt->error . "</div>";
    }
    $stmt->close();
}

// -----------------------------------------------------
// 3. HANDLE REMOVE STAFF (DELETE)
// -----------------------------------------------------
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['remove_staff'])) {
    $staff_id_to_remove = $_POST['staff_id_remove'];
    
    // Use prepared statements for security
    $stmt = $conn->prepare("DELETE FROM STAFF WHERE staff_id = ?");
    $stmt->bind_param("i", $staff_id_to_remove);

    if ($stmt->execute() && $conn->affected_rows > 0) {
        $message = "<div style='color: green;'>✅ Staff ID **{$staff_id_to_remove}** removed successfully.</div>";
    } else {
        $message = "<div style='color: red;'>❌ Error removing staff or ID not found.</div>";
    }
    $stmt->close();
}


// -----------------------------------------------------
// 4. SQL QUERY for Display (Existing JOIN)
// -----------------------------------------------------
$sql = "
    SELECT 
        S.staff_id, 
        S.first_name, 
        S.last_name, 
        S.position, 
        S.salary, 
        S.hire_date, 
        B.branch_name, 
        B.branch_city
    FROM 
        STAFF S
    JOIN 
        BRANCH B ON S.branch_id = B.branch_id
    ORDER BY 
        B.branch_name, S.last_name
";

$result = $conn->query($sql);

// Query to get branch IDs for the Add Staff dropdown
$branch_query = "SELECT branch_id, branch_name, branch_city FROM BRANCH";
$branch_result = $conn->query($branch_query);
$branches = [];
while ($row = $branch_result->fetch_assoc()) {
    $branches[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Management | Admin Panel</title>
    <style>
        /* ... (Styling remains the same, but adding form/message styles) ... */
        body { font-family: Arial, sans-serif; background-color: #f0f4f8; color: #333; margin: 0; padding: 0; }
        .header { background-color: #dc3545; color: white; padding: 15px; text-align: center; }
        .container { max-width: 1100px; margin: 30px auto; padding: 20px; background: white; border-radius: 8px; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1); }
        h2 { color: #dc3545; margin-bottom: 20px; border-bottom: 2px solid #ccc; padding-bottom: 5px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        th { background-color: #007bff; color: white; font-weight: bold; }
        tr:nth-child(even) { background-color: #f2f2f2; }
        .back-link { display: block; margin-top: 20px; color: #007bff; text-decoration: none; font-weight: bold; }
        
        .form-section { display: flex; gap: 30px; margin-bottom: 30px; padding: 20px; background-color: #fff; border: 1px solid #ddd; border-radius: 6px; }
        .form-section > div { flex: 1; }
        .form-section input, .form-section select { width: 100%; padding: 8px; margin-bottom: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        .form-section label { font-weight: bold; display: block; margin-bottom: 5px; }
        .form-section button { padding: 10px 15px; background-color: #28a745; color: white; border: none; border-radius: 4px; cursor: pointer; }
        #remove-form button { background-color: #dc3545; }
        .message-box { margin-bottom: 20px; padding: 10px; border-radius: 4px; font-weight: bold; border: 1px solid;}

    </style>
</head>
<body>

    <div class="header">
        <h1>Staff Management</h1>
    </div>

    <div class="container">
        
        <?php echo $message; // Display success/error message ?>

        <div class="form-section">
            
            <div>
                <h2>➕ Add New Staff Member</h2>
                <form method="POST" action="admin_staff_mgmt.php?welcome=<?php echo $user_first_name; ?>">
                    <label for="first_name">First Name:</label>
                    <input type="text" name="first_name" required>

                    <label for="last_name">Last Name (Password):</label>
                    <input type="text" name="last_name" required>

                    <label for="position">Position:</label>
                    <select name="position" required>
                        <option value="Teller">Teller</option>
                        <option value="Loan Officer">Loan Officer</option>
                        <option value="Manager">Manager</option>
                    </select>

                    <label for="salary">Salary:</label>
                    <input type="number" name="salary" step="0.01" required>
                    
                    <label for="hire_date">Hire Date:</label>
                    <input type="date" name="hire_date" value="<?php echo date('Y-m-d'); ?>" required>

                    <label for="branch_id">Branch:</label>
                    <select name="branch_id" required>
                        <?php foreach ($branches as $branch): ?>
                            <option value="<?php echo $branch['branch_id']; ?>">
                                <?php echo $branch['branch_name'] . " (" . $branch['branch_city'] . ")"; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    
                    <button type="submit" name="add_staff">Add Staff</button>
                </form>
            </div>
            
            <div id="remove-form">
                <h2>➖ Remove Staff Member</h2>
                <form method="POST" action="admin_staff_mgmt.php?welcome=<?php echo $user_first_name; ?>">
                    <p style="color: red; font-weight: bold;">⚠️ WARNING: This action is permanent.</p>
                    <label for="staff_id_remove">Staff ID to Remove:</label>
                    <input type="number" name="staff_id_remove" required>
                    <button type="submit" name="remove_staff">Remove Staff</button>
                </form>
            </div>
            
        </div>
        
        <h2>All Bank Employees and Assignments</h2>
        
        <?php
        if ($result->num_rows > 0) {
            echo "<table>";
            echo "<thead><tr><th>ID</th><th>Name</th><th>Position</th><th>Salary</th><th>Hire Date</th><th>Branch Name</th><th>Branch City</th></tr></thead>";
            echo "<tbody>";
            
            while($row = $result->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . $row["staff_id"] . "</td>";
                echo "<td>" . $row["first_name"] . " " . $row["last_name"] . "</td>";
                echo "<td>" . $row["position"] . "</td>";
                echo "<td>$" . number_format($row["salary"], 2) . "</td>";
                echo "<td>" . $row["hire_date"] . "</td>";
                echo "<td>" . $row["branch_name"] . "</td>";
                echo "<td>" . $row["branch_city"] . "</td>";
                echo "</tr>";
            }
            
            echo "</tbody>";
            echo "</table>";
        } else {
            echo "<p>No staff records found in the database.</p>";
        }

        $conn->close();
        ?>

        <a class="back-link" href="admin_dashboard.php?welcome=<?php echo $user_first_name; ?>">← Back to Admin Dashboard</a>
    </div>

</body>
</html>