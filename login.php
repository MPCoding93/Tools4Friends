<?php
session_start();
require_once 'db_connect.php'; // Your DB connection file

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (!empty($username) && !empty($password)) {
        if ($action === 'login') {
            // LOGIN
            $stmt = $conn->prepare("SELECT user_id, username, password_hash FROM Users WHERE username = ? OR email = ?");
            $stmt->bind_param("ss", $username, $username);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows === 1) {
                $stmt->bind_result($user_id, $db_username, $password_hash);
                $stmt->fetch();

                if (password_verify($password, $password_hash)) {
                    $_SESSION['user_id'] = $user_id;
                    $_SESSION['username'] = $db_username;
                    header("Location: index.html"); // Changed from index 4.html
                    exit();
                } else {
                    $error = "Invalid credentials.";
                }
            } else {
                $error = "User not found.";
            }

            $stmt->close();
        } elseif ($action === 'register') {
            // REGISTER
            $stmt = $conn->prepare("SELECT user_id FROM Users WHERE username = ? OR email = ?");
            $stmt->bind_param("ss", $username, $username);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows > 0) {
                $error = "Username or email already exists.";
            } else {
                $stmt->close();
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO Users (username, password_hash) VALUES (?, ?)");
                $stmt->bind_param("ss", $username, $password_hash);
                if ($stmt->execute()) {
                    $success = "Registration successful. You can now log in.";
                } else {
                    $error = "Registration failed. Please try again.";
                }
            }

            $stmt->close();
        }
    } else {
        $error = "Please enter both username and password.";
    }
}
?>
