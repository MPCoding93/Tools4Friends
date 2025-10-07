<?php
/**
 * Cookie Consent Handler
 * Handles AJAX requests for setting cookie consent
 */

require_once __DIR__ . '/../app/security.php';
require_once __DIR__ . '/../app/cookie_functions.php';

startSecureSession();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $consent = $_POST['consent'] ?? '';
    $lang = $_POST['lang'] ?? 'en';
    
    if ($consent === 'accepted') {
        setCookieConsent(true);
        echo json_encode([
            'success' => true,
            'message' => $lang === 'cs' ? 'Cookies byly přijaty.' : 'Cookies accepted.'
        ]);
    } elseif ($consent === 'declined') {
        setCookieConsent(false);
        echo json_encode([
            'success' => true,
            'message' => $lang === 'cs' ? 'Cookies byly odmítnuty.' : 'Cookies declined.'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => $lang === 'cs' ? 'Neplatný požadavek.' : 'Invalid request.'
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method.'
    ]);
}
?>
