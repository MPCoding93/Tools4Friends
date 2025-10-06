<?php
require_once __DIR__ . '/../app/security.php';
require_once __DIR__ . '/../app/db_connect.php';

startSecureSession();

// Load credentials
require_once __DIR__ . '/../config/config_credentials.php';

// Check if PHPMailer is available
$phpmailer_available = false;
$autoload_path = __DIR__ . '/vendor/autoload.php';

if (file_exists($autoload_path)) {
    require $autoload_path;
    
    // Check if PHPMailer classes exist
    if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        $phpmailer_available = true;
    }
}

// If PHPMailer is not available, log error and show message
if (!$phpmailer_available) {
    error_log('PHPMailer not found. Please install it using: composer require phpmailer/phpmailer');
}

$lang = $_GET['lang'] ?? 'en';
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $error = "Security validation failed.";
        logSecurityEvent('CSRF validation failed on password reset', [
            'ip' => $_SERVER['REMOTE_ADDR'],
            'email' => $_POST['email'] ?? 'unknown'
        ]);
    } else {
        $email = trim($_POST['email']);

        if (empty($email)) {
            $error = ($lang === 'cs' ? 'Zadejte prosím svůj email.' : 'Please enter your email address.');
        } elseif (!validateEmail($email)) {
            $error = ($lang === 'cs' ? 'Neplatná emailová adresa.' : 'Invalid email address.');
        } else {
            // Rate limiting check
            $rate_check = checkLoginAttempts($email);
            if (!$rate_check['allowed']) {
                $error = $rate_check['message'];
                logSecurityEvent('Password reset rate limit exceeded', ['email' => $email]);
            } else {
                // 1. Check if email exists
                $stmt = $conn->prepare("SELECT user_id FROM Users WHERE email = ?");
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows > 0) {
                    // 2. Generate a unique token
                    $token = bin2hex(random_bytes(32));
                    $expires_at = date('Y-m-d H:i:s', strtotime('+30 minutes')); // Reduced to 30 minutes

                    // 3. Store token in database
                    $stmt_invalidate = $conn->prepare("UPDATE password_resets SET used = TRUE WHERE email = ? AND used = FALSE");
                    $stmt_invalidate->bind_param("s", $email);
                    $stmt_invalidate->execute();
                    $stmt_invalidate->close();

                    $stmt_insert = $conn->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)");
                    $stmt_insert->bind_param("sss", $email, $token, $expires_at);

                    if ($stmt_insert->execute()) {
                        // 4. Send email with reset link
                        if (!$phpmailer_available) {
                            error_log("Cannot send password reset email: PHPMailer not installed");
                            $error = ($lang === 'cs' ? 
                                'Email systém není dostupný. Kontaktujte prosím administrátora.' : 
                                'Email system is not available. Please contact the administrator.');
                        } else {
                            $reset_link = "http://" . $_SERVER['HTTP_HOST'] . "/public/reset_password.php?token=" . $token . "&lang=" . $lang;

                            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
                            try {
                                // Check if SMTP settings are configured
                                if (!defined('SMTP_HOST') || !defined('SMTP_USERNAME') || !defined('SMTP_PASSWORD')) {
                                    throw new Exception('SMTP settings not configured');
                                }
                                
                                $mail->isSMTP();
                                $mail->Host       = SMTP_HOST;
                                $mail->SMTPAuth   = true;
                                $mail->Username   = SMTP_USERNAME;
                                $mail->Password   = SMTP_PASSWORD;
                                $mail->SMTPSecure = SMTP_ENCRYPTION;
                                $mail->Port       = SMTP_PORT;

                                // Use proper from address to avoid "unverified" warning
                                $from_email = defined('COMPANY_EMAIL') ? COMPANY_EMAIL : SMTP_USERNAME;
                                $from_name = defined('COMPANY_NAME') ? COMPANY_NAME : 'Tools4Friends';
                                
                                $mail->setFrom($from_email, $from_name);
                                $mail->addReplyTo($from_email, $from_name);
                                $mail->addAddress($email);

                                $mail->isHTML(true);
                                $mail->Subject = ($lang === 'cs' ? 'Reset hesla Tools4Friends' : 'Tools4Friends Password Reset');
                                $mail->Body    = ($lang === 'cs' ?
                                    '<p>Dobrý den,</p><p>Obdrželi jsme požadavek na resetování hesla pro váš účet Tools4Friends.</p><p>Pro resetování hesla klikněte na následující odkaz:</p><p><a href="' . $reset_link . '">' . $reset_link . '</a></p><p>Tento odkaz vyprší za 30 minut.</p><p>Pokud jste o reset hesla nežádali, můžete tuto zprávu ignorovat.</p><p>S pozdravem,<br>Tým Tools4Friends</p>' :
                                    '<p>Hello,</p><p>We received a request to reset the password for your Tools4Friends account.</p><p>To reset your password, please click on the following link:</p><p><a href="' . $reset_link . '">' . $reset_link . '</a></p><p>This link will expire in 30 minutes.</p><p>If you did not request a password reset, please ignore this email.</p><p>Sincerely,<br>The Tools4Friends Team</p>');
                                $mail->AltBody = ($lang === 'cs' ?
                                    'Dobrý den, Obdrželi jsme požadavek na resetování hesla pro váš účet Tools4Friends. Pro resetování hesla klikněte na následující odkaz: ' . $reset_link . ' Tento odkaz vyprší za 30 minut. Pokud jste o reset hesla nežádali, můžete tuto zprávu ignorovat. S pozdravem, Tým Tools4Friends' :
                                    'Hello, We received a request to reset the password for your Tools4Friends account. To reset your password, please click on the following link: ' . $reset_link . ' This link will expire in 30 minutes. If you did not request a password reset, please ignore this email. Sincerely, The Tools4Friends Team');

                                $mail->send();
                                
                                logSecurityEvent('Password reset email sent', ['email' => $email]);
                            } catch (\Exception $e) {
                                error_log("Email sending failed: " . $mail->ErrorInfo . " | Exception: " . $e->getMessage());
                                $error = ($lang === 'cs' ? 'Nepodařilo se odeslat email. Zkuste to prosím později.' : 'Could not send email. Please try again later.');
                            }
                        }
                    } else {
                        error_log("Password reset token generation failed: " . $conn->error);
                        $error = ($lang === 'cs' ? 'Došlo k chybě. Zkuste to prosím později.' : 'An error occurred. Please try again later.');
                    }
                    $stmt_insert->close();
                }
                $stmt->close();
                
                // Always show success message to prevent user enumeration
                $success = ($lang === 'cs' ? 'Pokud email existuje v našem systému, byl odeslán odkaz pro resetování hesla.' : 'If that email exists in our system, a password reset link has been sent.');
                
                // Record attempt for rate limiting
                recordFailedLogin($email);
            }
        }
    }
}

$csrf_token = generateCSRFToken();

$loggedIn = isset($_SESSION['user_id']);
$fullName = '';
if ($loggedIn) {
    $fullName = sanitizeOutput($_SESSION['firstname'] . ' ' . $_SESSION['lastname']);
}
?>

<!DOCTYPE html>
<html lang="<?php echo sanitizeOutput($lang); ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo ($lang === 'cs' ? 'Zapomenuté heslo' : 'Forgot Password'); ?> - Tools4Friends</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="icon" href="favicon/favicon-dark.ico" />
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
                <img src="images/banners/tools4friends_dark_Banner_2000x400.png" alt="Company Logo" />
            </div>
        </header>
        <div class="line-break"></div>
        <?php include __DIR__ . '/../app/navbar.php'; ?>

        <main>
            <div style="margin-bottom: 20px;">
                <a href="login.php?lang=<?php echo sanitizeOutput($lang); ?>" class="btn btn-blue">
                    <?php echo $lang === 'cs' ? '← Zpět na Přihlášení' : '← Back to Login'; ?>
                </a>
            </div>
            <h1><?php echo ($lang === 'cs' ? 'Zapomenuté heslo' : 'Forgot Password'); ?></h1>
            <div class="line-break"></div>

            <?php if ($error): ?>
                <div class="error-message"><?php echo sanitizeOutput($error); ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="success-message"><?php echo sanitizeOutput($success); ?></div>
            <?php endif; ?>

            <form method="POST" class="form-card">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <div class="form-group">
                    <label for="email"><?php echo ($lang === 'cs' ? 'Zadejte svůj email:' : 'Enter your email address:'); ?></label>
                    <input type="email" id="email" name="email" required maxlength="255">
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
