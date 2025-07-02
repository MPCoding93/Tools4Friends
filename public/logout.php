<?php
session_start();
session_unset();
session_destroy();
header("Location: ../index.php"); // Fixed path to point to root index.php
exit();
?>