<?php
// MUST BE THE VERY FIRST LINE
session_start();

// -----------------------------------------------------
// 1. SECURITY & CONNECTION SETUP
// -----------------------------------------------------
if (!isset($_GET['welcome'])) {
    header("Location: index.html");
    exit();
}

$servername = "localhost";
$username = "root";   
$password = ""; 
$dbname = "banking_sys";
$port = 3307;

$conn = new mysqli($servername, $username, $password, $dbname, $port);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$user_first_name = htmlspecialchars($_GET['welcome']);
$message = "";

// -----------------------------------------------------
// 2. HANDLE ADD BRANCH (INSERT)
// -----------------------------------------------------
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_branch'])) {
    $name = $_POST['branch_name'];
    $city = $_POST['branch_city'];
    $assets = $_POST['total_assets']; // PHP Variable remains 'total_assets' for the form input

    // *** FIX 1: Changed 'total_assets' to 'assets' in the INSERT query ***
    $stmt = $conn->prepare("INSERT INTO BRANCH (branch_name, branch_city, assets) VALUES (?, ?, ?)");
    $stmt->bind_param("ssd", $name, $city, $assets);

    if ($stmt->execute()) {
        $message = "<div style='color: green;'>✅ New branch **{$name}** in {$city} added successfully!</div>";
    } else {
        $message = "<div style='color: red;'>❌ Error adding branch: " . $stmt->error . "</div>";
    }
    $stmt->close();
}

// -----------------------------------------------------
// 3. HANDLE REMOVE BRANCH (DELETE)
// -----------------------------------------------------
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['remove_branch'])) {
    $branch_id_to_remove = $_POST['branch_id_remove'];
    
    $stmt = $conn->prepare("DELETE FROM BRANCH WHERE branch_id = ?");
    $stmt->bind_param("i", $branch_id_to_remove);

    if ($stmt->execute() && $conn->affected_rows > 0) {
        $message = "<div style='color: green;'>✅ Branch ID **{$branch_id_to_remove}** removed successfully.</div>";
    } else {
        $message = "<div style='color: red;'>❌ Error removing branch or ID not found.</div>";
    }
    $stmt->close();
}


// -----------------------------------------------------
// 4. SQL QUERY for Display (JOIN to count staff)
// -----------------------------------------------------
$sql = "
    SELECT 
        B.branch_id, 
        B.branch_name, 
        B.branch_city, 
        B.assets,               /* *** FIX 2: Changed 'total_assets' to 'assets' in SELECT *** */
        COUNT(S.staff_id) AS staff_count
    FROM 
        BRANCH B
    LEFT JOIN 
        STAFF S ON B.branch_id = S.branch_id
    GROUP BY
        B.branch_id, B.branch_name, B.branch_city, B.assets /* *** FIX 3: Changed 'total_assets' to 'assets' in GROUP BY *** */
    ORDER BY 
        B.branch_city, B.branch_name
";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Branch Oversight | Admin Panel</title>
    <style>
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
        .form-section input { width: 100%; padding: 8px; margin-bottom: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        .form-section label { font-weight: bold; display: block; margin-bottom: 5px; }
        .form-section button { padding: 10px 15px; background-color: #28a745; color: white; border: none; border-radius: 4px; cursor: pointer; }
        #remove-form button { background-color: #dc3545; }
    </style>
</head>
<body>

    <div class="header">
        <h1>Branch Oversight & Management</h1>
    </div>

    <div class="container">
        
        <?php echo $message; ?>

        <div class="form-section">
            
            <div>
                <h2>➕ Add New Branch Location</h2>
                <form method="POST" action="admin_branch_oversight.php?welcome=<?php echo $user_first_name; ?>">
                    <label for="branch_name">Branch Name:</label>
                    <input type="text" name="branch_name" required>

                    <label for="branch_city">City/Location:</label>
                    <input type="text" name="branch_city" required>

                    <label for="total_assets">Initial Total Assets ($):</label>
                    <input type="number" name="total_assets" step="1000.00" min="100000" required>
                    
                    <button type="submit" name="add_branch">Create Branch</button>
                </form>
            </div>
            
            <div id="remove-form">
                <h2>➖ Close Branch</h2>
                <form method="POST" action="admin_branch_oversight.php?welcome=<?php echo $user_first_name; ?>">
                    <p style="color: red; font-weight: bold;">⚠️ WARNING: This action is permanent and may leave staff/accounts unassigned.</p>
                    <label for="branch_id_remove">Branch ID to Close:</label>
                    <input type="number" name="branch_id_remove" required>
                    <button type="submit" name="remove_branch">Remove Branch</button>
                </form>
            </div>
            
        </div>
        
        <h2>Bank Branch List and Summary</h2>
        
        <?php
        if ($result->num_rows > 0) {
            echo "<table>";
            echo "<thead><tr><th>ID</th><th>Name</th><th>City</th><th>Total Assets</th><th>Staff Count</th></tr></thead>";
            echo "<tbody>";
            
            while($row = $result->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . $row["branch_id"] . "</td>";
                echo "<td>" . $row["branch_name"] . "</td>";
                echo "<td>" . $row["branch_city"] . "</td>";
                // Accessing the row data using the correct column name 'assets'
                echo "<td>$" . number_format($row["assets"], 2) . "</td>"; 
                echo "<td>" . $row["staff_count"] . "</td>";
                echo "</tr>";
            }
            
            echo "</tbody>";
            echo "</table>";
        } else {
            echo "<p>No branch records found in the database.</p>";
        }

        $conn->close();
        ?>

        <a class="back-link" href="admin_dashboard.php?welcome=<?php echo $user_first_name; ?>">← Back to Admin Dashboard</a>
    </div>

</body>
</html>