<?php
// Start session
session_start();

// Destroy all session variables
session_unset();

// Destroy the session
session_destroy();

// Redirect to landing page (index.html)
header("Location: index.html");
exit();
?>
