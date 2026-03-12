<?php
// Start session
session_start();

// Destroy all session variables
session_unset();


session_destroy();

// Redirect to landing page (index.html)
header("Location: index.html");
exit();
?>
