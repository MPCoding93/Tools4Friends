<?php
session_start();
session_unset();
session_destroy();
header("Location: /index.php"); // Updated to point to root index.php
exit();
?>
