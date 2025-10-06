<?php
require_once __DIR__ . '/../app/security.php';

startSecureSession();

// Get language before destroying session
$lang = $_GET['lang'] ?? $_SESSION['lang'] ?? 'en';

// Log the logout event
if (isset($_SESSION['user_id'])) {
    logSecurityEvent('User logout', ['user_id' => $_SESSION['user_id']]);
}

// Destroy session
session_unset();
session_destroy();

// Start new session and regenerate ID to prevent session fixation
session_start();
regenerateSession();

// Redirect to home page
header("Location: ../index.php?lang=" . $lang);
exit();
?>
