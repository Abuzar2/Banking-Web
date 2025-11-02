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
$staff = null;
$staff_id = $_GET['staff_id'] ?? null; // Get staff ID from URL

if ($staff_id) {
    // Fetch current staff details
    $sql_fetch = "SELECT * FROM STAFF WHERE staff_id = ?";
    $stmt_fetch = $conn->prepare($sql_fetch);
    $stmt_fetch->bind_param("i", $staff_id); // 'i' for integer staff_id
    $stmt_fetch->execute();
    $result = $stmt_fetch->get_result();
    $staff = $result->fetch_assoc();

    if (!$staff) {
        $message = "<p class='error'>❌ Staff member not found.</p>";
        $staff_id = null; // Clear ID if not found
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && $staff_id) {
    // 1. Sanitize and collect form data
    $first_name = $conn->real_escape_string($_POST['first_name']);
    $last_name = $conn->real_escape_string($_POST['last_name']);
    $position = $conn->real_escape_string($_POST['position']);
    $salary = $conn->real_escape_string($_POST['salary']);
    $hire_date = $conn->real_escape_string($_POST['hire_date']);
    $branch_id = $conn->real_escape_string($_POST['branch_id']);

    // 2. Prepare the UPDATE SQL statement
    $sql_update = "
        UPDATE STAFF 
        SET first_name=?, last_name=?, position=?, salary=?, hire_date=?, branch_id=? 
        WHERE staff_id=?
    ";
    $stmt_update = $conn->prepare($sql_update);
    // Bind parameters: sssdsii (string, string, string, double, string, integer, integer)
    $stmt_update->bind_param("sssdsii", $first_name, $last_name, $position, $salary, $hire_date, $branch_id, $staff_id);

    // 3. Execute the query
    if ($stmt_update->execute()) {
        $message = "<p class='success'>✅ Staff details for ID **$staff_id** updated successfully!</p>";
        // Re-fetch updated data
        $result = $conn->query("SELECT * FROM STAFF WHERE staff_id = '$staff_id'");
        $staff = $result->fetch_assoc();
    } else {
        $message = "<p class='error'>❌ Error updating staff: " . $conn->error . "</p>";
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin - Edit Staff</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Roboto', sans-serif; background-color: #f0f4f8; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 50px auto; padding: 30px; background: white; border-radius: 12px; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1); }
        h2 { color: #ffc107; border-bottom: 2px solid #eee; padding-bottom: 10px; margin-bottom: 20px; font-weight: 700; }
        label { display: block; margin-top: 15px; font-weight: 500; color: #495057; }
        input[type="text"], input[type="date"], select { 
            width: 100%; 
            padding: 12px; 
            margin-top: 5px; 
            border: 1px solid #ddd; 
            border-radius: 6px; 
            box-sizing: border-box; 
            font-size: 1em;
        }
        input[type="submit"] { 
            background-color: #ffc107; 
            color: #333; 
            padding: 12px 20px; 
            border: none; 
            border-radius: 6px; 
            cursor: pointer; 
            margin-top: 30px; 
            font-size: 1.1em;
            font-weight: 700;
            transition: background-color 0.2s;
        }
        input[type="submit"]:hover { background-color: #e0a800; }
        .back-link { display: block; margin-top: 25px; font-size: 1em; }
        .back-link a { color: #007bff; text-decoration: none; }
        .back-link a:hover { text-decoration: underline; }
        .success { color: green; font-weight: bold; background-color: #e9f7ef; padding: 10px; border-radius: 4px; }
        .error { color: red; font-weight: bold; background-color: #fcebeb; padding: 10px; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="container">
        <h2>✍️ Edit Staff Details (ID: <?php echo htmlspecialchars($staff_id ?? 'N/A'); ?>)</h2>
        <?php echo $message; ?>

        <?php if ($staff): ?>
            <form method="POST" action="edit_staff.php?staff_id=<?php echo htmlspecialchars($staff_id); ?>">
                <!-- Staff ID is displayed, not editable -->
                <p style="font-size: 1.1em; margin-top: 0; padding-bottom: 10px; border-bottom: 1px solid #ddd;">Staff ID: **<?php echo htmlspecialchars($staff['staff_id']); ?>**</p> 

                <label for="first_name">First Name:</label>
                <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($staff['first_name']); ?>" required>

                <label for="last_name">Last Name:</label>
                <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($staff['last_name']); ?>" required>

                <label for="position">Position:</label>
                <select id="position" name="position" required>
                    <option value="Teller" <?php if ($staff['position'] == 'Teller') echo 'selected'; ?>>Teller</option>
                    <option value="Manager" <?php if ($staff['position'] == 'Manager') echo 'selected'; ?>>Manager</option>
                    <option value="Loan Officer" <?php if ($staff['position'] == 'Loan Officer') echo 'selected'; ?>>Loan Officer</option>
                </select>

                <label for="salary">Salary ($):</label>
                <input type="text" id="salary" name="salary" value="<?php echo htmlspecialchars($staff['salary']); ?>" required>
                
                <label for="hire_date">Hire Date:</label>
                <input type="date" id="hire_date" name="hire_date" value="<?php echo htmlspecialchars($staff['hire_date']); ?>">
                
                <label for="branch_id">Branch ID:</label>
                <input type="text" id="branch_id" name="branch_id" value="<?php echo htmlspecialchars($staff['branch_id']); ?>" required>

                <input type="submit" value="Update Staff Details">
            </form>
        <?php endif; ?>

        <div class="back-link">
            <a href="admin_staff_details.php">← Back to Staff List</a>
        </div>
    </div>
</body>
</html>
