
<?php
session_start();

// Remove session data
$_SESSION = [];

// Destroy the current session
session_destroy();

// Return to the homepage
header("Location: index.php");
exit;
?>
