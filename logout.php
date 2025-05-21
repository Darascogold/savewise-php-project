<?php
session_start();

// Clear all session variables
session_unset();

// Destroy the session
session_destroy();

// Redirect the user to the homepage (index.html)
header("Location: index.html");
exit;
?>
