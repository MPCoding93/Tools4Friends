<?php
// Start session and check authentication
session_start();
require_once 'db_connect.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?lang=" . ($_GET['lang'] ?? 'en'));
    exit();
}

// Get the language from the URL or default to English
$lang = $_GET['lang'] ?? 'en'; // Ensure $lang is defined here

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';
$password_error = '';
$profile_error = '';

// Fetch user data with prepared statement
$stmt_user = $conn->prepare("SELECT firstname, lastname, email, phone, profile_picture FROM Users WHERE user_id = ?");
$stmt_user->bind_param("i", $user_id);
$stmt_user->execute();
$result_user = $stmt_user->get_result();
$user = $result_user->fetch_assoc();
$stmt_user->close();

// Handle profile updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    // ... (rest of your code)
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    // ... (rest of your code)
}

// Fetch user's tools
$stmt_tools = $conn->prepare("SELECT tool_id, name, name_cs, picture, brand, model FROM Tools WHERE ownerID = ?");
$stmt_tools->bind_param("i", $user_id);
$stmt_tools->execute();
$user_tools_result = $stmt_tools->get_result();
$user_tools = [];
while ($tool_row = $user_tools_result->fetch_assoc()) {
    $user_tools[] = $tool_row;
}
$stmt_tools->close();

// Navbar variables
$loggedIn = true;
$fullName = htmlspecialchars($_SESSION['firstname'] . ' ' . $_SESSION['lastname']);
?>
