<?php
$servername = "sql5.webzdarma.cz";
$username = "pauwelsrenti1221";
$password = "Micha3l-";
$dbname = "pauwelsrenti1221";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
