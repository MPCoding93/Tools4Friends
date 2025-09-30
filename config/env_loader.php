<?php
/**
 * Environment Configuration Loader
 * Loads environment variables from .env file
 */

function loadEnv($path) {
    if (!file_exists($path)) {
        die('Environment file not found. Please create .env file.');
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Skip comments
        if (strpos(trim($line), '#') === 0) {
            continue;
        }

        // Parse KEY=VALUE
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);

            // Remove quotes if present
            $value = trim($value, '"\'');

            // Set as environment variable and define constant
            putenv("$key=$value");
            if (!defined($key)) {
                define($key, $value);
            }
        }
    }
}

// Load environment variables
loadEnv(__DIR__ . '/../.env');
?>
