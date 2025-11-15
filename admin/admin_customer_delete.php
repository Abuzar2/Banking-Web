<?php
session_start();

// Security check - ensure admin is logged in
if (!isset($_SESSION['admin_user'])) {
    header("Location: admin_login.php");
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

// Get Customer ID from URL
$cust_id = isset($_GET['cust_id']) ? (int)$_GET['cust_id'] : null;

if (!$cust_id) {
    $_SESSION['error_message'] = "Invalid Customer ID";
    header("Location: admin_customer_details.php");
    exit();
}

// Start transaction for data integrity
$conn->begin_transaction();

try {
    // First, get customer details for logging
    $sql_customer = "SELECT first_name, last_name FROM customer WHERE cust_id = ?";
    $stmt = $conn->prepare($sql_customer);
    $stmt->bind_param("i", $cust_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception("Customer not found");
    }
    
    $customer = $result->fetch_assoc();
    $customer_name = $customer['first_name'] . ' ' . $customer['last_name'];
    $stmt->close();

    // Check if customer has accounts
    $sql_check_accounts = "SELECT COUNT(*) as account_count FROM customer_account WHERE cust_id = ?";
    $stmt = $conn->prepare($sql_check_accounts);
    $stmt->bind_param("i", $cust_id);
    $stmt->execute();
    $account_result = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($account_result['account_count'] > 0) {
        throw new Exception("Cannot delete customer with active accounts. Please close all accounts first.");
    }

    // Check if customer has loans
    $sql_check_loans = "SELECT COUNT(*) as loan_count FROM customer_loan WHERE cust_id = ?";
    $stmt = $conn->prepare($sql_check_loans);
    $stmt->bind_param("i", $cust_id);
    $stmt->execute();
    $loan_result = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($loan_result['loan_count'] > 0) {
        throw new Exception("Cannot delete customer with active loans. Please clear all loans first.");
    }

    // Delete from cust_login table first (due to foreign key constraint)
    $sql_delete_login = "DELETE FROM cust_login WHERE cust_id = ?";
    $stmt = $conn->prepare($sql_delete_login);
    $stmt->bind_param("i", $cust_id);
    $stmt->execute();
    $stmt->close();

    // Delete from customer table
    $sql_delete_customer = "DELETE FROM customer WHERE cust_id = ?";
    $stmt = $conn->prepare($sql_delete_customer);
    $stmt->bind_param("i", $cust_id);
    
    if ($stmt->execute()) {
        // Log the action
        $log_stmt = $conn->prepare("INSERT INTO audit_log (timestamp, user_role, user_id, action_type, details) VALUES (NOW(), 'Admin', ?, 'Customer Deleted', ?)");
        $log_details = "Deleted customer ID: $cust_id ($customer_name)";
        $log_stmt->bind_param("ss", $_SESSION['admin_user'], $log_details);
        $log_stmt->execute();
        $log_stmt->close();
        
        $conn->commit();
        $_SESSION['success_message'] = "Customer '$customer_name' deleted successfully!";
    } else {
        throw new Exception("Failed to delete customer: " . $stmt->error);
    }
    
    $stmt->close();
    
} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['error_message'] = $e->getMessage();
}

$conn->close();

// Redirect back to customer list
header("Location: admin_customer_details.php");
exit();
?>