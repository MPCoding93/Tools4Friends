<?php
session_start();
session_unset();
session_destroy();
header("Location: index.html"); // Changed from index 4.html
exit();
?>
