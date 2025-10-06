<?php
/**
 * Secure Configuration Credentials
 * This file contains SMTP email configuration only
 * Database credentials are in .env file
 * Make sure this file is NOT accessible via web browser
 */

// Gmail SMTP Settings (for sending emails)
// IMPORTANT: Your actual Gmail credentials
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'tools4friends.info@gmail.com');
define('SMTP_PASSWORD', 'muot qzyy xgxr funr'); // Your 16-character App Password
define('SMTP_ENCRYPTION', 'tls');

// Email Display Settings
define('COMPANY_EMAIL', 'tools4friends.info@gmail.com');
define('COMPANY_NAME', 'Tools4Friends');

// Session Configuration
define('SESSION_LIFETIME', 1800); // 30 minutes in seconds
define('APP_ENV', 'production'); // 'production' or 'development'

/**
 * SETUP INSTRUCTIONS:
 * 
 * 1. Gmail Account: tools4friends.info@gmail.com ✓
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
 *    - Replace SMTP_PASSWORD above with your new App Password
 * 
 * 4. Current Configuration:
 *    - SMTP_USERNAME: tools4friends.info@gmail.com ✓
 *    - SMTP_PASSWORD: [Your 16-character code] - UPDATE THIS!
 *    - COMPANY_EMAIL: tools4friends.info@gmail.com ✓
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
 * 
 * NOTE: If you have a new 16-character authentication code,
 * replace the SMTP_PASSWORD value above with your new code.
 * Format: 'xxxx xxxx xxxx xxxx' (remove spaces when entering)
 */
?>
