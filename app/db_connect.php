<?php
/**
 * Secure Database Connection
 */

require_once __DIR__ . '/../config/env_loader.php';

$servername = T4F_DB_HOST;
$username = T4F_DB_USER;
$password = T4F_DB_PASS;
$dbname = T4F_DB_NAME;

try {
    $conn = new mysqli($servername, $username, $password, $dbname);
    
    if ($conn->connect_error) {
        error_log("Database connection failed: " . $conn->connect_error);
        die("Database connection error. Please contact administrator.");
    }
    
    $conn->set_charset("utf8mb4");
    
} catch (Exception $e) {
    error_log("Database connection exception: " . $e->getMessage());
    die("Database connection error. Please contact administrator.");
}
?>
