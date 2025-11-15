<?php
// NOTE: This version does NOT use session_start() or $_SESSION variables.
// It relies on passing user data through the URL, which is less secure.

// -----------------------------------------------------
// 1. DATABASE CONNECTION DETAILS - UPDATE THESE!
// -----------------------------------------------------
$servername = "localhost";
$username = "root";   // <--- UPDATE THIS
$password = ""; // <--- UPDATE THIS
$dbname = "banking_sys";
$port = 3307; // The specified port number

// -----------------------------------------------------
// 2. GET LOGIN DATA FROM THE FORM
// -----------------------------------------------------
$staff_id = isset($_POST['staff_id']) ? $_POST['staff_id'] : ''; 
$last_name = isset($_POST['password']) ? $_POST['password'] : ''; 

// -----------------------------------------------------
// 3. ESTABLISH CONNECTION
// -----------------------------------------------------
$conn = new mysqli($servername, $username, $password, $dbname, $port);

// Check connection
if ($conn->connect_error) {
    die("Connection failed on port " . $port . ": " . $conn->connect_error);
}

// -----------------------------------------------------
// 4. PREPARE AND EXECUTE QUERY (Admin/Manager Check)
// -----------------------------------------------------
$stmt = $conn->prepare("
    SELECT staff_id, first_name 
    FROM STAFF 
    WHERE staff_id = ? 
    AND last_name = ? 
    AND position = 'Manager' 
");
$stmt->bind_param("is", $staff_id, $last_name);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 1) {
    // -------------------------------------------------
    // LOGIN SUCCESSFUL
    // -------------------------------------------------
    $user = $result->fetch_assoc();
    
    // **CRITICAL:** Redirects using the insecure GET parameter
    header("Location: admin_dashboard.php?welcome=" . urlencode($user['first_name']));
    exit();
    
} else {
    // -------------------------------------------------
    // LOGIN FAILED
    // -------------------------------------------------
    header("Location: index.html?error=1");
    exit();
}

$stmt->close();
$conn->close();

?>