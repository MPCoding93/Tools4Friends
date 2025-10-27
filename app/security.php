<?php
/**
 * Security Helper Functions
 */

function startSecureSession() {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_samesite', 'Strict');
    
    if (defined('APP_ENV') && APP_ENV === 'production') {
        ini_set('session.cookie_secure', 1);
    }
    
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Set security headers
    setSecurityHeaders();
    
    checkSessionTimeout();
}

function setSecurityHeaders() {
    // Prevent clickjacking
    header("X-Frame-Options: SAMEORIGIN");
    
    // Prevent MIME type sniffing
    header("X-Content-Type-Options: nosniff");
    
    // Enable XSS protection
    header("X-XSS-Protection: 1; mode=block");
    
    // Referrer policy
    header("Referrer-Policy: strict-origin-when-cross-origin");
    
    // Permissions policy
    header("Permissions-Policy: geolocation=(), microphone=(), camera=()");
    
    // Content Security Policy - allowing inline scripts for now due to tool_availability.php data passing
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com; img-src 'self' data:; connect-src 'self';");
    
    // Strict Transport Security (HSTS) - only in production with HTTPS
    if (defined('APP_ENV') && APP_ENV === 'production' && 
        isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
        header("Strict-Transport-Security: max-age=31536000; includeSubDomains");
    }
}

function checkSessionTimeout() {
    $timeout = defined('SESSION_LIFETIME') ? SESSION_LIFETIME : 1800;

    if (isset($_SESSION['last_activity']) &&
        (time() - $_SESSION['last_activity'] > $timeout)) {
        session_unset();
        session_destroy();
        header("Location: /Tools4Friends/index.php");
        exit();
    }
    $_SESSION['last_activity'] = time();
}

function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCSRFToken($token) {
    if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        logSecurityEvent('CSRF validation failed', [
            'ip' => $_SERVER['REMOTE_ADDR'],
            'uri' => $_SERVER['REQUEST_URI']
        ]);
        die('Security validation failed. Please try again.');
    }
    return true;
}

function sanitizeOutput($data) {
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

function validateEmail($email) {
    $email = filter_var($email, FILTER_SANITIZE_EMAIL);
    return filter_var($email, FILTER_VALIDATE_EMAIL) && strlen($email) <= 255;
}

function validatePhone($phone) {
    $phone = preg_replace('/[^0-9+\-\s()]/', '', $phone);
    return preg_match('/^[0-9+\-\s()]{7,20}$/', $phone) ? $phone : false;
}

function validateDate($date, $format = 'Y-m-d') {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}

function validateFileUpload($file, $maxSize = 5242880) {
    $allowed_mimes = ['image/jpeg', 'image/png', 'image/gif'];
    
    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        return ['valid' => false, 'error' => 'Invalid file upload'];
    }
    
    if ($file['size'] > $maxSize) {
        return ['valid' => false, 'error' => 'File too large (max 5MB)'];
    }
    
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mime, $allowed_mimes)) {
        return ['valid' => false, 'error' => 'Invalid file type'];
    }
    
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];
    
    if (!in_array($ext, $allowed_ext)) {
        return ['valid' => false, 'error' => 'Invalid file extension'];
    }
    
    return ['valid' => true, 'mime' => $mime, 'ext' => $ext];
}

function generateSecureFilename($extension) {
    return bin2hex(random_bytes(16)) . '.' . $extension;
}

function checkLoginAttempts($identifier) {
    $max_attempts = defined('MAX_LOGIN_ATTEMPTS') ? MAX_LOGIN_ATTEMPTS : 5;
    $lockout_time = 900;
    
    if (!isset($_SESSION['login_attempts'])) {
        $_SESSION['login_attempts'] = [];
    }
    
    $key = md5($identifier . $_SERVER['REMOTE_ADDR']);
    
    if (isset($_SESSION['login_attempts'][$key])) {
        $attempts = $_SESSION['login_attempts'][$key];
        
        if ($attempts['count'] >= $max_attempts) {
            $time_passed = time() - $attempts['last_attempt'];
            if ($time_passed < $lockout_time) {
                $remaining = ceil(($lockout_time - $time_passed) / 60);
                return [
                    'allowed' => false, 
                    'message' => "Too many failed attempts. Try again in $remaining minutes."
                ];
            } else {
                unset($_SESSION['login_attempts'][$key]);
            }
        }
    }
    
    return ['allowed' => true];
}

function recordFailedLogin($identifier) {
    $key = md5($identifier . $_SERVER['REMOTE_ADDR']);
    
    if (!isset($_SESSION['login_attempts'][$key])) {
        $_SESSION['login_attempts'][$key] = ['count' => 0, 'last_attempt' => 0];
    }
    
    $_SESSION['login_attempts'][$key]['count']++;
    $_SESSION['login_attempts'][$key]['last_attempt'] = time();
    
    logSecurityEvent('Failed login attempt', [
        'identifier' => $identifier,
        'ip' => $_SERVER['REMOTE_ADDR'],
        'attempts' => $_SESSION['login_attempts'][$key]['count']
    ]);
}

function clearLoginAttempts($identifier) {
    $key = md5($identifier . $_SERVER['REMOTE_ADDR']);
    unset($_SESSION['login_attempts'][$key]);
}

function logSecurityEvent($event, $details = []) {
    $log_dir = __DIR__ . '/../logs';
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    
    $log_file = $log_dir . '/security.log';
    $timestamp = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    
    $log_entry = sprintf(
        "[%s] %s | IP: %s | User-Agent: %s | Details: %s\n",
        $timestamp,
        $event,
        $ip,
        $user_agent,
        json_encode($details)
    );
    
    file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
}

function requireLogin($lang = 'en') {
    if (!isset($_SESSION['user_id'])) {
        header("Location: /Tools4Friends/public/login.php?lang=" . $lang);
        exit();
    }
}

function regenerateSession() {
    session_regenerate_id(true);
}
?>
