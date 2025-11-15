<?php
// staff_pending_customers.php
session_start();

// Staff authentication check here...

// Fetch pending customers
$sql_pending = "SELECT cust_id, first_name, last_name, ssn, date_of_birth, city, mobile_no, created_at 
                FROM customer 
                WHERE status = 'pending' 
                ORDER BY created_at DESC";
$result_pending = $conn->query($sql_pending);
?>

<!-- Display pending customers in a table with Approve/Reject buttons -->