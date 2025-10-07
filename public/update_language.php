<?php
/**
 * Language Update Endpoint
 * Handles AJAX requests for updating user language preference
 */

require_once __DIR__ . '/../app/security.php';
require_once __DIR__ . '/../app/db_connect.php';
require_once __DIR__ . '/../app/language_init.php';
require_once __DIR__ . '/../app/cookie_functions.php';

startSecureSession();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $lang = $_POST['lang'] ?? '';
    
    // Validate language
    if (!in_array($lang, ['en', 'cs'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid language code.'
        ]);
        exit;
    }
    
    // Update cookie if consent given
    setLanguageCookie($lang);
    
    // If user is logged in, update database
    if (isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
        $success = updateUserLanguagePreference($conn, $user_id, $lang);
        
        if ($success) {
            echo json_encode([
                'success' => true,
                'message' => $lang === 'cs' ? 'Jazyk byl úspěšně změněn.' : 'Language changed successfully.',
                'lang' => $lang
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => $lang === 'cs' ? 'Chyba při změně jazyka.' : 'Error changing language.'
            ]);
        }
    } else {
        // For non-logged-in users, just confirm cookie was set
        echo json_encode([
            'success' => true,
            'message' => $lang === 'cs' ? 'Jazyk byl úspěšně změněn.' : 'Language changed successfully.',
            'lang' => $lang
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method.'
    ]);
}
?>
