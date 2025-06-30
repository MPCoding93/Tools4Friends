<?php
// Secure database connection with error handling
$servername = "sql5.webzdarma.cz";
$username = "pauwelsrenti1221";
$password = "Micha3l-";
$dbname = "pauwelsrenti1221";

// Create connection with proper error handling
try {
    $conn = new mysqli($servername, $username, $password, $dbname);
    
    // Check connection
    if ($conn->connect_error) {
        // Log error securely without exposing details to user
        error_log("Database connection failed: " . $conn->connect_error);
        die("Database connection failed. Please try again later.");
    }
    
    // Set charset to prevent character set confusion attacks
    $conn->set_charset("utf8mb4");
    
} catch (Exception $e) {
    error_log("Database connection error: " . $e->getMessage());
    die("Database connection failed. Please try again later.");
}
?>