<?php
/**
 * Centralized Language Initialization
 * Determines the user's preferred language based on multiple sources
 * Priority: User DB preference → Cookie → URL parameter → Default 'cz'
 */

require_once __DIR__ . '/cookie_functions.php';

/**
 * Initialize and return the appropriate language for the current user
 * @param mysqli $conn Database connection (optional, for logged-in users)
 * @return string Language code ('en' or 'cs')
 */
function initializeLanguage($conn = null) {
    $lang = 'cz'; // Default language
    
    // Priority 1: Check if user is logged in and has a database preference
    if (isset($_SESSION['user_id']) && $conn !== null) {
        $user_id = $_SESSION['user_id'];
        $stmt = $conn->prepare("SELECT preferred_language FROM Users WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            if (!empty($row['preferred_language']) && in_array($row['preferred_language'], ['en', 'cs'])) {
                $lang = $row['preferred_language'];
                $_SESSION['preferred_language'] = $lang;
                $stmt->close();
                return $lang;
            }
        }
        $stmt->close();
    }
    
    // Priority 2: Check session (for logged-in users who already loaded their preference)
    if (isset($_SESSION['preferred_language']) && in_array($_SESSION['preferred_language'], ['en', 'cs'])) {
        $lang = $_SESSION['preferred_language'];
        return $lang;
    }
    
    // Priority 3: Check cookie (for non-logged-in users or if DB preference not set)
    $cookieLang = getLanguageFromCookie();
    if ($cookieLang !== null) {
        $lang = $cookieLang;
        return $lang;
    }
    
    // Priority 4: Check URL parameter
    if (isset($_GET['lang']) && in_array($_GET['lang'], ['en', 'cs'])) {
        $lang = $_GET['lang'];
        // Set cookie if consent given
        setLanguageCookie($lang);
        return $lang;
    }
    
    // Priority 5: Return default
    return $lang;
}

/**
 * Update user's preferred language in database
 * @param mysqli $conn Database connection
 * @param int $user_id User ID
 * @param string $lang Language code ('en' or 'cs')
 * @return bool Success status
 */
function updateUserLanguagePreference($conn, $user_id, $lang) {
    if (!in_array($lang, ['en', 'cs'])) {
        return false;
    }
    
    $stmt = $conn->prepare("UPDATE Users SET preferred_language = ? WHERE user_id = ?");
    $stmt->bind_param("si", $lang, $user_id);
    $success = $stmt->execute();
    $stmt->close();
    
    if ($success) {
        $_SESSION['preferred_language'] = $lang;
        setLanguageCookie($lang);
    }
    
    return $success;
}
?>
