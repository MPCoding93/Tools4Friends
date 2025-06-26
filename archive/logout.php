<?php
session_start();
session_unset();
session_destroy();
header("Location: index.php"); // Changed from index.html to index.php for consistency
exit();
?>
