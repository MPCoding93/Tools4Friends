<?php
/**
 * Secure Configuration Credentials
 * This file contains sensitive configuration data
 * Make sure this file is NOT accessible via web browser
 */

// Database Configuration
define('DB_HOST', 'localhost'); // Update with your database host
define('DB_USER', 'your_database_user'); // Update with your database username
define('DB_PASS', 'your_database_password'); // Update with your database password
define('DB_NAME', 'your_database_name'); // Update with your database name

// Gmail SMTP Settings (for sending emails)
// IMPORTANT: Replace with your actual Gmail credentials
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'tools4friends.info@gmail.com'); // Replace with your Gmail address
define('SMTP_PASSWORD', 'gtxo uylj urwn jbsh'); // Replace with your Gmail App Password (16 characters)
define('SMTP_ENCRYPTION', 'tls');

// Email Display Settings
define('COMPANY_EMAIL', 'tools4friends.info@gmail.com'); // Replace with your Gmail address
define('COMPANY_NAME', 'Tools4Friends');

// Session Configuration
define('SESSION_LIFETIME', 1800); // 30 minutes in seconds
define('APP_ENV', 'production'); // 'production' or 'development'

/**
 * SETUP INSTRUCTIONS:
 * 
 * 1. Create a Gmail account (e.g., tools4friends.info@gmail.com)
 * 
 * 2. Enable 2-Factor Authentication:
 *    - Go to: https://myaccount.google.com/security
 *    - Enable "2-Step Verification"
 * 
 * 3. Generate App Password:
 *    - Go to: https://myaccount.google.com/apppasswords
 *    - Select "Mail" and "Other (Custom name)"
 *    - Name it: "Tools4Friends Website"
 *    - Copy the 16-character password (format: xxxx xxxx xxxx xxxx)
 * 
 * 4. Update this file:
 *    - Replace SMTP_USERNAME with your Gmail address
 *    - Replace SMTP_PASSWORD with the App Password from step 3
 *    - Replace COMPANY_EMAIL with your Gmail address
 *    - Update database credentials (DB_HOST, DB_USER, DB_PASS, DB_NAME)
 * 
 * 5. Test:
 *    - Use the "Forgot Password" feature
 *    - Check if email arrives without "unverified" warning
 * 
 * SECURITY NOTES:
 * - Never commit this file to public repositories
 * - Keep your App Password secret
 * - Use different passwords for development and production
 * - Regularly rotate your App Passwords
 */
?>
