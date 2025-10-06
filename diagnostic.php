<?php
/**
 * Diagnostic Script - Check System Configuration
 * Access this file directly to see what's causing issues
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Tools4Friends Diagnostic Report</h1>";
echo "<style>body{font-family:monospace;} .success{color:green;} .error{color:red;} .warning{color:orange;}</style>";

// Test 1: PHP Version
echo "<h2>1. PHP Version</h2>";
$php_version = phpversion();
echo "PHP Version: <strong>$php_version</strong>";
if (version_compare($php_version, '7.4.0', '>=')) {
    echo " <span class='success'>✓ OK</span><br>";
} else {
    echo " <span class='error'>✗ Too old (need 7.4+)</span><br>";
}

// Test 2: Required Extensions
echo "<h2>2. Required PHP Extensions</h2>";
$required_extensions = ['mysqli', 'session', 'json', 'mbstring'];
foreach ($required_extensions as $ext) {
    echo "- $ext: ";
    if (extension_loaded($ext)) {
        echo "<span class='success'>✓ Loaded</span><br>";
    } else {
        echo "<span class='error'>✗ Missing</span><br>";
    }
}

// Test 3: File Permissions
echo "<h2>3. File Permissions</h2>";
$files_to_check = [
    '.env' => 'Environment variables',
    'config/env_loader.php' => 'Environment loader',
    'config/config_credentials.php' => 'SMTP credentials',
    'app/security.php' => 'Security functions',
    'app/db_connect.php' => 'Database connection',
];

foreach ($files_to_check as $file => $description) {
    echo "- $description ($file): ";
    $full_path = __DIR__ . '/' . $file;
    if (file_exists($full_path)) {
        if (is_readable($full_path)) {
            echo "<span class='success'>✓ Readable</span><br>";
        } else {
            echo "<span class='error'>✗ Not readable</span><br>";
        }
    } else {
        echo "<span class='error'>✗ File not found</span><br>";
    }
}

// Test 4: .env File Contents
echo "<h2>4. Environment Variables (.env)</h2>";
if (file_exists(__DIR__ . '/.env')) {
    echo "<span class='success'>✓ .env file exists</span><br>";
    
    // Try to load it
    try {
        require_once __DIR__ . '/config/env_loader.php';
        echo "<span class='success'>✓ env_loader.php loaded successfully</span><br>";
        
        // Check if constants are defined
        $env_vars = ['DB_HOST', 'DB_USER', 'DB_PASS', 'DB_NAME'];
        echo "<br><strong>Checking environment constants:</strong><br>";
        foreach ($env_vars as $var) {
            echo "- $var: ";
            if (defined($var)) {
                $value = constant($var);
                // Mask sensitive data
                if (in_array($var, ['DB_PASS'])) {
                    $display = str_repeat('*', strlen($value));
                } else {
                    $display = $value;
                }
                echo "<span class='success'>✓ Defined ($display)</span><br>";
            } else {
                echo "<span class='error'>✗ Not defined</span><br>";
            }
        }
    } catch (Exception $e) {
        echo "<span class='error'>✗ Error loading env_loader.php: " . $e->getMessage() . "</span><br>";
    }
} else {
    echo "<span class='error'>✗ .env file not found</span><br>";
}

// Test 5: Database Connection
echo "<h2>5. Database Connection</h2>";
try {
    if (defined('DB_HOST') && defined('DB_USER') && defined('DB_PASS') && defined('DB_NAME')) {
        $test_conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        if ($test_conn->connect_error) {
            echo "<span class='error'>✗ Connection failed: " . $test_conn->connect_error . "</span><br>";
        } else {
            echo "<span class='success'>✓ Database connection successful</span><br>";
            echo "- Server: " . $test_conn->host_info . "<br>";
            echo "- Database: " . DB_NAME . "<br>";
            $test_conn->close();
        }
    } else {
        echo "<span class='error'>✗ Database constants not defined</span><br>";
    }
} catch (Exception $e) {
    echo "<span class='error'>✗ Database error: " . $e->getMessage() . "</span><br>";
}

// Test 6: Session
echo "<h2>6. Session Support</h2>";
if (session_status() === PHP_SESSION_NONE) {
    session_start();
    echo "<span class='success'>✓ Session started successfully</span><br>";
    echo "- Session ID: " . session_id() . "<br>";
} else {
    echo "<span class='warning'>⚠ Session already started</span><br>";
}

// Test 7: Security Functions
echo "<h2>7. Security Functions</h2>";
try {
    require_once __DIR__ . '/app/security.php';
    echo "<span class='success'>✓ security.php loaded</span><br>";
    
    // Check if functions exist
    $functions = ['startSecureSession', 'sanitizeOutput', 'generateCSRFToken', 'validateCSRFToken'];
    foreach ($functions as $func) {
        echo "- $func(): ";
        if (function_exists($func)) {
            echo "<span class='success'>✓ Available</span><br>";
        } else {
            echo "<span class='error'>✗ Not found</span><br>";
        }
    }
} catch (Exception $e) {
    echo "<span class='error'>✗ Error loading security.php: " . $e->getMessage() . "</span><br>";
}

// Test 8: SMTP Configuration
echo "<h2>8. SMTP Configuration</h2>";
try {
    require_once __DIR__ . '/config/config_credentials.php';
    echo "<span class='success'>✓ config_credentials.php loaded</span><br>";
    
    $smtp_vars = ['SMTP_HOST', 'SMTP_PORT', 'SMTP_USERNAME', 'SMTP_PASSWORD', 'SMTP_ENCRYPTION'];
    foreach ($smtp_vars as $var) {
        echo "- $var: ";
        if (defined($var)) {
            $value = constant($var);
            // Mask password
            if ($var === 'SMTP_PASSWORD') {
                $display = str_repeat('*', strlen($value));
            } else {
                $display = $value;
            }
            echo "<span class='success'>✓ Defined ($display)</span><br>";
        } else {
            echo "<span class='error'>✗ Not defined</span><br>";
        }
    }
} catch (Exception $e) {
    echo "<span class='error'>✗ Error loading config_credentials.php: " . $e->getMessage() . "</span><br>";
}

// Test 9: PHPMailer
echo "<h2>9. PHPMailer Library</h2>";
if (file_exists(__DIR__ . '/public/vendor/autoload.php')) {
    echo "<span class='success'>✓ Composer autoload found</span><br>";
    try {
        require __DIR__ . '/public/vendor/autoload.php';
        if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
            echo "<span class='success'>✓ PHPMailer class available</span><br>";
        } else {
            echo "<span class='error'>✗ PHPMailer class not found</span><br>";
        }
    } catch (Exception $e) {
        echo "<span class='error'>✗ Error loading PHPMailer: " . $e->getMessage() . "</span><br>";
    }
} else {
    echo "<span class='warning'>⚠ Composer autoload not found (email features may not work)</span><br>";
}

// Test 10: Write Permissions
echo "<h2>10. Write Permissions</h2>";
$writable_dirs = [
    'logs' => 'Log directory',
    'public/uploads' => 'Upload directory',
];

foreach ($writable_dirs as $dir => $description) {
    echo "- $description ($dir): ";
    $full_path = __DIR__ . '/' . $dir;
    if (is_dir($full_path)) {
        if (is_writable($full_path)) {
            echo "<span class='success'>✓ Writable</span><br>";
        } else {
            echo "<span class='error'>✗ Not writable</span><br>";
        }
    } else {
        echo "<span class='warning'>⚠ Directory doesn't exist</span><br>";
    }
}

// Test 11: .htaccess Configuration
echo "<h2>11. .htaccess Configuration</h2>";
if (file_exists(__DIR__ . '/.htaccess')) {
    echo "<span class='success'>✓ .htaccess file exists</span><br>";
    $htaccess_content = file_get_contents(__DIR__ . '/.htaccess');
    
    // Check for config directory block
    if (strpos($htaccess_content, 'DirectoryMatch.*config') !== false) {
        echo "<span class='error'>✗ WARNING: .htaccess is blocking config directory access!</span><br>";
        echo "<span class='error'>  This will prevent PHP from including config files.</span><br>";
    } else {
        echo "<span class='success'>✓ Config directory not blocked</span><br>";
    }
} else {
    echo "<span class='warning'>⚠ .htaccess file not found</span><br>";
}

echo "<hr>";
echo "<h2>Summary</h2>";
echo "<p>If all tests show <span class='success'>✓ OK</span> or <span class='success'>✓</span>, your system is configured correctly.</p>";
echo "<p>If you see <span class='error'>✗</span> errors, fix those issues first.</p>";
echo "<p><strong>Next step:</strong> Try accessing <a href='index.php'>index.php</a> or <a href='public/login.php'>login.php</a></p>";
?>
