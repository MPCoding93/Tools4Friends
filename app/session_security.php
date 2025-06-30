<?php
/**
 * Session security configuration
 */

// Start secure session configuration
if (session_status() == PHP_SESSION_NONE) {
    // Configure session security settings
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', 1); // Only if using HTTPS
    ini_set('session.use_strict_mode', 1);
    ini_set('session.cookie_samesite', 'Strict');
    
    // Set session timeout (30 minutes)
    ini_set('session.gc_maxlifetime', 1800);
    
    session_start();
    
    // Check for session timeout
    if (isset($_SESSION['login_time'])) {
        if (time() - $_SESSION['login_time'] > 1800) { // 30 minutes
            session_unset();
            session_destroy();
            header("Location: /public/login.php?timeout=1");
            exit();
        }
        // Update last activity time
        $_SESSION['login_time'] = time();
    }
    
    // Regenerate session ID periodically
    if (!isset($_SESSION['last_regeneration'])) {
        $_SESSION['last_regeneration'] = time();
    } elseif (time() - $_SESSION['last_regeneration'] > 300) { // 5 minutes
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();
    }
}
?>