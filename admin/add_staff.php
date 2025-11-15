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

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 1. Sanitize and collect form data
    $first_name = $conn->real_escape_string($_POST['first_name']);
    $last_name = $conn->real_escape_string($_POST['last_name']);
    $position = $conn->real_escape_string($_POST['position']);
    $salary = $conn->real_escape_string($_POST['salary']);
    $hire_date = $conn->real_escape_string($_POST['hire_date']);
    $branch_id = $conn->real_escape_string($_POST['branch_id']);
    
    // NOTE: staff_id is AUTO_INCREMENT, so we don't insert it.

    // 2. Prepare the INSERT SQL statement
    $sql = "INSERT INTO STAFF (first_name, last_name, position, salary, hire_date, branch_id) 
            VALUES ('$first_name', '$last_name', '$position', '$salary', '$hire_date', '$branch_id')";

    // 3. Execute the query
    if ($conn->query($sql) === TRUE) {
        $message = "<p class='success'>✅ New staff member added successfully! Staff ID: " . $conn->insert_id . "</p>";
    } else {
        $message = "<p class='error'>❌ Error adding staff: " . $conn->error . "</p>";
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin - Add Staff</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Roboto', sans-serif; background-color: #f0f4f8; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 50px auto; padding: 30px; background: white; border-radius: 12px; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1); }
        h2 { color: #28a745; border-bottom: 2px solid #eee; padding-bottom: 10px; margin-bottom: 20px; font-weight: 700; }
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
            background-color: #28a745; 
            color: white; 
            padding: 12px 20px; 
            border: none; 
            border-radius: 6px; 
            cursor: pointer; 
            margin-top: 30px; 
            font-size: 1.1em;
            font-weight: 700;
            transition: background-color 0.2s;
        }
        input[type="submit"]:hover { background-color: #218838; }
        .back-link { display: block; margin-top: 25px; font-size: 1em; }
        .back-link a { color: #007bff; text-decoration: none; }
        .back-link a:hover { text-decoration: underline; }
        .success { color: green; font-weight: bold; background-color: #e9f7ef; padding: 10px; border-radius: 4px; }
        .error { color: red; font-weight: bold; background-color: #fcebeb; padding: 10px; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="container">
        <h2>➕ Add New Staff Member</h2>
        <?php echo $message; ?>
        <form method="POST" action="">
            <label for="first_name">First Name:</label>
            <input type="text" id="first_name" name="first_name" required>

            <label for="last_name">Last Name:</label>
            <input type="text" id="last_name" name="last_name" required>

            <label for="position">Position:</label>
            <select id="position" name="position" required>
                <option value="Teller">Teller</option>
                <option value="Manager">Manager</option>
                <option value="Loan Officer">Loan Officer</option>
            </select>

            <label for="salary">Salary ($):</label>
            <input type="text" id="salary" name="salary" placeholder="e.g., 50000.00" required>
            
            <label for="hire_date">Hire Date:</label>
            <input type="date" id="hire_date" name="hire_date" required>
            
            <label for="branch_id">Branch ID:</label>
            <input type="text" id="branch_id" name="branch_id" required>

            <input type="submit" value="Add Staff">
        </form>
        <div class="back-link">
            <a href="admin_staff_details.php">← Back to Staff List</a>
        </div>
    </div>
</body>
</html>
