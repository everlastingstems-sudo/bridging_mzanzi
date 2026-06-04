<?php
session_start(); // Access the current session
session_unset(); // Remove all session variables
session_destroy(); // Destroy the session itself

// Redirect the user back to the login page or home page
header("Location: login.php");
exit();
?>