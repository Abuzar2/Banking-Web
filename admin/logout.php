<?php
// 1. Start the session (required to access session data)
session_start();

// 2. Unset all of the session variables
// This clears any user-specific data stored in the session
$_SESSION = array();

// 3. Destroy the session
// This removes the session from the server's storage
session_destroy();

// 4. Redirect the user back to the login page
// This ensures they cannot use the 'back' button to access the dashboard
header("Location: index.html");
exit;
?>