<?php
session_start();
require_once __DIR__ . '/../app/db_connect.php'; // Path from public/forgot_password.php to app/db_connect.php

// --- START: Secure Credential Loading ---
// Adjust the path based on where you placed config_credentials.php
// Path from public/forgot_password.php to config/config.credentials.php
require_once __DIR__ . '/../config/config.credentials.php';
// --- END: Secure Credential Loading ---

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Use Composer's autoloader if you installed via Composer
require 'vendor/autoload.php';
// OR, if you installed manually, use these:
// require 'PHPMailer/src/Exception.php';
// require 'PHPMailer/src/PHPMailer.php';
// require 'PHPMailer/src/SMTP.php';


$lang = $_GET['lang'] ?? 'en';
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);

    if (empty($email)) {
        $error = ($lang === 'cs' ? 'Zadejte prosím svůj email.' : 'Please enter your email address.');
    } else {
        // 1. Check if email exists
        $stmt = $conn->prepare("SELECT user_id FROM Users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // 2. Generate a unique token
            $token = bin2hex(random_bytes(32)); // Cryptographically secure random token
            $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour')); // Token valid for 1 hour

            // 3. Store token in database
            // First, invalidate any existing unused tokens for this email
            $stmt_invalidate = $conn->prepare("UPDATE password_resets SET used = TRUE WHERE email = ? AND used = FALSE");
            $stmt_invalidate->bind_param("s", $email);
            $stmt_invalidate->execute();
            $stmt_invalidate->close();

            $stmt_insert = $conn->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)");
            $stmt_insert->bind_param("sss", $email, $token, $expires_at);

            if ($stmt_insert->execute()) {
                // 4. Send email with reset link
                $reset_link = "http://" . $_SERVER['HTTP_HOST'] . "/reset_password.php?token=" . $token . "&lang=" . $lang;

                $mail = new PHPMailer(true); // Pass `true` to enable exceptions
                try {
                    // Server settings - NOW USING SECURELY LOADED CONSTANTS
                    $mail->isSMTP();
                    $mail->Host       = SMTP_HOST;
                    $mail->SMTPAuth   = true;
                    $mail->Username   = SMTP_USERNAME;
                    $mail->Password   = SMTP_PASSWORD;
                    $mail->SMTPSecure = SMTP_ENCRYPTION;
                    $mail->Port       = SMTP_PORT;

                    // Recipients
                    $mail->setFrom(SMTP_USERNAME, 'Tools4Friends No-Reply'); // Use the defined username for 'From' address
                    $mail->addAddress($email);                                  // Add a recipient

                    // Content
                    $mail->isHTML(true);                                        // Set email format to HTML
                    $mail->Subject = ($lang === 'cs' ? 'Reset hesla Tools4Friends' : 'Tools4Friends Password Reset');
                    $mail->Body    = ($lang === 'cs' ?
                        '<p>Dobrý den,</p><p>Obdrželi jsme požadavek na resetování hesla pro váš účet Tools4Friends.</p><p>Pro resetování hesla klikněte na následující odkaz:</p><p><a href="' . $reset_link . '">' . $reset_link . '</a></p><p>Tento odkaz vyprší za 1 hodinu.</p><p>Pokud jste o reset hesla nežádali, můžete tuto zprávu ignorovat.</p><p>S pozdravem,<br>Tým Tools4Friends</p>' :
                        '<p>Hello,</p><p>We received a request to reset the password for your Tools4Friends account.</p><p>To reset your password, please click on the following link:</p><p><a href="' . $reset_link . '">' . $reset_link . '</a></p><p>This link will expire in 1 hour.</p><p>If you did not request a password reset, please ignore this email.</p><p>Sincerely,<br>The Tools4Friends Team</p>');
                    $mail->AltBody = ($lang === 'cs' ?
                        'Dobrý den, Obdrželi jsme požadavek na resetování hesla pro váš účet Tools4Friends. Pro resetování hesla klikněte na následující odkaz: ' . $reset_link . ' Tento odkaz vyprší za 1 hodinu. Pokud jste o reset hesla nežádali, můžete tuto zprávu ignorovat. S pozdravem, Tým Tools4Friends' :
                        'Hello, We received a request to reset the password for your Tools4Friends account. To reset your password, please click on the following link: ' . $reset_link . ' This link will expire in 1 hour. If you did not request a password reset, please ignore this email. Sincerely, The Tools4Friends Team');

                    $mail->send();
                    $success = ($lang === 'cs' ? 'Odkaz pro resetování hesla byl odeslán na váš email.' : 'A password reset link has been sent to your email address.');
                } catch (Exception $e) {
                    $error = ($lang === 'cs' ? 'Nepodařilo se odeslat email. Chyba Maileru: ' : 'Could not send email. Mailer Error: ') . $mail->ErrorInfo;
                }
            } else {
                $error = ($lang === 'cs' ? 'Chyba při generování odkazu pro resetování hesla.' : 'Error generating password reset link.');
            }
            $stmt_insert->close();
        } else {
            $error = ($lang === 'cs' ? 'Emailová adresa nebyla nalezena.' : 'Email address not found.');
        }
        $stmt->close();
    }
}

// Navbar variables (ensure these are set before including navbar.php)
$loggedIn = isset($_SESSION['user_id']);
$fullName = '';
if ($loggedIn) {
    $fullName = htmlspecialchars($_SESSION['firstname'] . ' ' . $_SESSION['lastname']);
}
?>

<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo ($lang === 'cs' ? 'Zapomenuté heslo' : 'Forgot Password'); ?> - Tools4Friends</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="icon" href="favicon/favicon-dark.ico" /> <!-- Updated path -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Libre+Baskerville:ital,wght@0,400;0,700;1,400&family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap"
        rel="stylesheet">
    <script src="script.js" defer></script>
</head>

<body>
    <div class="container">
        <header>
            <div class="banner">
                <img src="images/banners/tools4friends_dark_Banner_2000x400.png" alt="Company Logo" /> <!-- Updated path -->
            </div>
        </header>
        <div class="line-break"></div>
        <?php include __DIR__ . '/../app/navbar.php'; ?> <!-- Updated path -->

        <main>
            <div style="margin-bottom: 20px;">
                <a href="login.php?lang=<?php echo $lang; ?>" class="btn btn-blue">
                    <?php echo $lang === 'cs' ? '← Zpět na Přihlášení' : '← Back to Login'; ?>
                </a>
            </div>
            <h1><?php echo ($lang === 'cs' ? 'Zapomenuté heslo' : 'Forgot Password'); ?></h1>
            <div class="line-break"></div>

            <?php if ($error): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="success-message"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <form method="POST" class="form-card">
                <div class="form-group">
                    <label for="email"><?php echo ($lang === 'cs' ? 'Zadejte svůj email:' : 'Enter your email address:'); ?></label>
                    <input type="email" id="email" name="email" required>
                </div>
                <button type="submit" class="submit-button">
                    <?php echo ($lang === 'cs' ? 'Odeslat odkaz pro reset hesla' : 'Send Reset Link'); ?>
                </button>
            </form>
        </main>

        <footer>
            <p>&copy; <span id="year"></span> Tools4Friends</p>
        </footer>
    </div>
</body>

</html>