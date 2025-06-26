<?php
    session_start();
    // Path from public/forgot_password.php to app/db_connect.php
    require_once __DIR__ . '/../app/db_connect.php';

    // Include the secure credentials file from config/
    // Path from public/forgot_password.php to config/config.credentials.php
    require_once __DIR__ . '/../config/config.credentials.php';

    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\Exception;

    require 'vendor/autoload.php'; // Or your manual PHPMailer includes

    $lang = $_GET['lang'] ?? 'en';
    $error = '';
    $success = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $email = trim($_POST['email']);

        if (empty($email)) {
            $error = ($lang === 'cs' ? 'Zadejte prosím svůj email.' : 'Please enter your email address.');
        } else {
            // ... (rest of your existing code)

            $mail = new PHPMailer(true);
            try {
                // Server settings - now using constants
                $mail->isSMTP();
                // These constants (SMTP_HOST, SMTP_USERNAME, etc.) are assumed to be defined in config/config.credentials.php
                $mail->Host       = SMTP_HOST;
                $mail->SMTPAuth   = true;
                $mail->Username   = SMTP_USERNAME;
                $mail->Password   = SMTP_PASSWORD;
                $mail->SMTPSecure = SMTP_ENCRYPTION;
                $mail->Port       = SMTP_PORT;

                // ... (rest of your existing code)

            } catch (Exception $e) {
                $error = ($lang === 'cs' ? 'Nepodařilo se odeslat email. Chyba Mailer: ' : 'Could not send email. Mailer Error: ') . $mail->ErrorInfo;
            }
        }
    }

    // ... (rest of your HTML and PHP)
    ?>
