<?php
/**
 * Cookie Management Functions
 * Handles cookie consent and language preference cookies
 */

/**
 * Check if user has given cookie consent
 */
function hasCookieConsent() {
    return isset($_COOKIE['cookie_consent']) && $_COOKIE['cookie_consent'] === 'accepted';
}

/**
 * Set cookie consent
 */
function setCookieConsent($accepted = true) {
    $value = $accepted ? 'accepted' : 'declined';
    // Set cookie for 1 year
    setcookie('cookie_consent', $value, [
        'expires' => time() + (365 * 24 * 60 * 60),
        'path' => '/',
        'secure' => true,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
}

/**
 * Get language from cookie
 */
function getLanguageFromCookie() {
    if (hasCookieConsent() && isset($_COOKIE['preferred_language'])) {
        $lang = $_COOKIE['preferred_language'];
        // Validate language
        if (in_array($lang, ['en', 'cs'])) {
            return $lang;
        }
    }
    return null;
}

/**
 * Set language cookie
 */
function setLanguageCookie($lang) {
    if (!in_array($lang, ['en', 'cs'])) {
        return false;
    }
    
    if (hasCookieConsent()) {
        // Set cookie for 1 year
        setcookie('preferred_language', $lang, [
            'expires' => time() + (365 * 24 * 60 * 60),
            'path' => '/',
            'secure' => true,
            'httponly' => false, // Allow JavaScript access for language switching
            'samesite' => 'Lax'
        ]);
        return true;
    }
    return false;
}

/**
 * Check if cookie consent modal should be shown
 */
function shouldShowCookieConsent() {
    return !isset($_COOKIE['cookie_consent']);
}
?>
