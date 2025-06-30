<?php
/**
 * Secure configuration file for sensitive credentials
 * This file should be outside the web root in production
 */

// SMTP Configuration
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_USERNAME', 'your-email@gmail.com');
define('SMTP_PASSWORD', 'your-app-password');
define('SMTP_ENCRYPTION', 'tls');
define('SMTP_PORT', 587);

// Database Configuration (if needed for additional security)
define('DB_HOST', 'sql5.webzdarma.cz');
define('DB_USERNAME', 'pauwelsrenti1221');
define('DB_PASSWORD', 'Micha3l-');
define('DB_NAME', 'pauwelsrenti1221');

// Security Keys
define('ENCRYPTION_KEY', 'your-32-character-encryption-key-here');
define('HASH_SALT', 'your-unique-salt-here');

// Rate Limiting Configuration
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900); // 15 minutes
define('MAX_PASSWORD_RESET_ATTEMPTS', 3);
define('PASSWORD_RESET_LOCKOUT_TIME', 3600); // 1 hour
?>