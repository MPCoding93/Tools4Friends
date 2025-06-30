<?php
/**
 * Security utility functions for Tools4Friends
 */

class Security {
    
    /**
     * Generate CSRF token
     */
    public static function generateCSRFToken() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Verify CSRF token
     */
    public static function verifyCSRFToken($token) {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * Sanitize input data
     */
    public static function sanitizeInput($input) {
        if (is_array($input)) {
            return array_map([self::class, 'sanitizeInput'], $input);
        }
        
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Validate email
     */
    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * Validate password strength
     */
    public static function validatePassword($password) {
        // At least 8 characters, 1 uppercase, 1 lowercase, 1 number
        return preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[a-zA-Z\d@$!%*?&]{8,}$/', $password);
    }
    
    /**
     * Rate limiting check
     */
    public static function checkRateLimit($action, $limit = 5, $window = 300) {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        $key = $action . '_' . $_SERVER['REMOTE_ADDR'];
        $now = time();
        
        if (!isset($_SESSION['rate_limit'][$key])) {
            $_SESSION['rate_limit'][$key] = [];
        }
        
        // Clean old attempts
        $_SESSION['rate_limit'][$key] = array_filter(
            $_SESSION['rate_limit'][$key],
            function($timestamp) use ($now, $window) {
                return ($now - $timestamp) < $window;
            }
        );
        
        // Check if limit exceeded
        if (count($_SESSION['rate_limit'][$key]) >= $limit) {
            return false;
        }
        
        // Add current attempt
        $_SESSION['rate_limit'][$key][] = $now;
        return true;
    }
    
    /**
     * Secure file upload validation
     */
    public static function validateFileUpload($file, $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'], $maxSize = 5242880) {
        $errors = [];
        
        // Check if file was uploaded
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            $errors[] = 'Invalid file upload';
            return $errors;
        }
        
        // Check file size
        if ($file['size'] > $maxSize) {
            $errors[] = 'File size too large (max 5MB)';
        }
        
        // Check file type by extension
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, $allowedTypes)) {
            $errors[] = 'Invalid file type';
        }
        
        // Check MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        $allowedMimes = [
            'image/jpeg',
            'image/jpg', 
            'image/png',
            'image/gif'
        ];
        
        if (!in_array($mimeType, $allowedMimes)) {
            $errors[] = 'Invalid file content';
        }
        
        return $errors;
    }
    
    /**
     * Generate secure filename
     */
    public static function generateSecureFilename($originalName) {
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        return bin2hex(random_bytes(16)) . '.' . $extension;
    }
}
?>