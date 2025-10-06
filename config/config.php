<?php
/**
 * Centralized Application Configuration
 * This file contains non-sensitive configuration data
 */

// Prevent direct access
if (!defined('APP_INIT')) {
    die('Direct access not permitted');
}

// Application Base Paths
define('APP_ROOT', dirname(__DIR__));
define('APP_PUBLIC', APP_ROOT . '/public');
define('APP_CONFIG', APP_ROOT . '/config');
define('APP_INCLUDES', APP_ROOT . '/app');
define('APP_LOGS', APP_ROOT . '/logs');
define('APP_UPLOADS', APP_PUBLIC . '/uploads');

// URL Base Paths (adjust based on your hosting)
define('BASE_URL', '/Tools4Friends');
define('PUBLIC_URL', BASE_URL . '/public');
define('UPLOADS_URL', PUBLIC_URL . '/uploads');

// Application Settings
define('APP_NAME', 'Tools4Friends');
define('APP_VERSION', '1.0.0');
define('APP_TIMEZONE', 'Europe/Prague');

// Error Reporting (set based on environment)
if (defined('APP_ENV') && APP_ENV === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', APP_LOGS . '/php_errors.log');
}

// Set timezone
date_default_timezone_set(APP_TIMEZONE);

// Email Rate Limiting Settings
define('EMAIL_RATE_LIMIT', 5); // Maximum emails per time window
define('EMAIL_RATE_WINDOW', 3600); // Time window in seconds (1 hour)

// Session Settings
define('SESSION_NAME', 'T4F_SESSION');
define('SESSION_COOKIE_LIFETIME', 0); // Until browser closes
define('SESSION_COOKIE_PATH', '/');
define('SESSION_COOKIE_SECURE', false); // Set to true if using HTTPS
define('SESSION_COOKIE_HTTPONLY', true);

// File Upload Settings
define('MAX_UPLOAD_SIZE', 5242880); // 5MB in bytes
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
define('UPLOAD_PATH', APP_UPLOADS);

// Pagination Settings
define('ITEMS_PER_PAGE', 20);

// Security Settings
define('CSRF_TOKEN_LIFETIME', 3600); // 1 hour
define('PASSWORD_MIN_LENGTH', 8);
define('LOGIN_MAX_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900); // 15 minutes

// Database Query Timeout
define('DB_QUERY_TIMEOUT', 30); // seconds

/**
 * Check if required configuration files exist
 */
function checkRequiredFiles() {
    $required_files = [
        APP_CONFIG . '/config_credentials.php' => 'Database credentials file',
        APP_INCLUDES . '/security.php' => 'Security functions file',
        APP_INCLUDES . '/db_connect.php' => 'Database connection file',
    ];
    
    $missing_files = [];
    foreach ($required_files as $file => $description) {
        if (!file_exists($file)) {
            $missing_files[] = $description . ' (' . basename($file) . ')';
        }
    }
    
    if (!empty($missing_files)) {
        die('Configuration Error: Missing required files:<br>' . implode('<br>', $missing_files));
    }
}

/**
 * Check if required directories exist and are writable
 */
function checkRequiredDirectories() {
    $required_dirs = [
        APP_LOGS => 'Logs directory',
        APP_UPLOADS => 'Uploads directory',
    ];
    
    foreach ($required_dirs as $dir => $description) {
        if (!is_dir($dir)) {
            if (!mkdir($dir, 0755, true)) {
                die('Configuration Error: Cannot create ' . $description . ' (' . $dir . ')');
            }
        }
        
        if (!is_writable($dir)) {
            die('Configuration Error: ' . $description . ' is not writable (' . $dir . ')');
        }
    }
}

// Run checks
checkRequiredFiles();
checkRequiredDirectories();

/**
 * Get full URL for a path
 */
function getUrl($path = '') {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'];
    return $protocol . $host . BASE_URL . '/' . ltrim($path, '/');
}

/**
 * Get public URL for a path
 */
function getPublicUrl($path = '') {
    return getUrl('public/' . ltrim($path, '/'));
}

/**
 * Redirect to a URL
 */
function redirect($url, $permanent = false) {
    if ($permanent) {
        header('HTTP/1.1 301 Moved Permanently');
    }
    header('Location: ' . $url);
    exit();
}

/**
 * Redirect to error page
 */
function redirectToError($code = 404) {
    $error_pages = [
        404 => PUBLIC_URL . '/error_404.php',
        500 => PUBLIC_URL . '/error_500.php',
        'database' => PUBLIC_URL . '/error_database.php',
    ];
    
    $error_page = $error_pages[$code] ?? $error_pages[404];
    redirect($error_page);
}
?>
