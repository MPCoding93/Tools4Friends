<?php
session_start();
require_once __DIR__ . '/../app/db_connect.php'; // Path from public/reset_password.php to app/db_connect.php

$lang = $_GET['lang'] ?? 'en';
$error = '';
$success = '';
$token = $_GET['token'] ?? '';
$valid_token = false;
$user_email = '';

if (!empty($token)) {
    // 1. Validate token existence, expiration, and usage
    $stmt = $conn->prepare("SELECT email, expires_at, used FROM password_resets WHERE token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();
        $user_email = $row['email'];
        $expires_at = strtotime($row['expires_at']);
        $is_used = $row['used'];

        if ($is_used) {
            $error = ($lang === 'cs' ? 'Tento odkaz pro resetování hesla již byl použit.' : 'This password reset link has already been used.');
        } elseif (time() > $expires_at) {
            $error = ($lang === 'cs' ? 'Tento odkaz pro resetování hesla vypršel.' : 'This password reset link has expired.');
        } else {
            $valid_token = true; // Token is valid, show password reset form
        }
    } else {
        $error = ($lang === 'cs' ? 'Neplatný odkaz pro resetování hesla.' : 'Invalid password reset link.');
    }
    $stmt->close();
} else {
    $error = ($lang === 'cs' ? 'Chybí token pro resetování hesla.' : 'Password reset token is missing.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $valid_token) {
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($new_password) || empty($confirm_password)) {
        $error = ($lang === 'cs' ? 'Vyplňte prosím obě pole pro heslo.' : 'Please fill in both password fields.');
    } elseif ($new_password !== $confirm_password) {
        $error = ($lang === 'cs' ? 'Hesla se neshodují.' : 'Passwords do not match.');
    } elseif (strlen($new_password) < 8) {
        $error = ($lang === 'cs' ? 'Heslo musí obsahovat alespoň 8 znaků.' : 'Password must be at least 8 characters.');
    } else {
        // 2. Update user's password
        $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt_update_pass = $conn->prepare("UPDATE Users SET password_hash = ? WHERE email = ?");
        $stmt_update_pass->bind_param("ss", $password_hash, $user_email);

        if ($stmt_update_pass->execute()) {
            // 3. Mark token as used
            $stmt_mark_used = $conn->prepare("UPDATE password_resets SET used = TRUE WHERE token = ?");
            $stmt_mark_used->bind_param("s", $token);
            $stmt_mark_used->execute();
            $stmt_mark_used->close();

            $success = ($lang === 'cs' ? 'Vaše heslo bylo úspěšně resetováno. Nyní se můžete přihlásit.' : 'Your password has been reset successfully. You can now log in.');
            $valid_token = false; // Prevent form from showing again
        } else {
            $error = ($lang === 'cs' ? 'Chyba při aktualizaci hesla.' : 'Error updating password.');
        }
        $stmt_update_pass->close();
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
    <title><?php echo ($lang === 'cs' ? 'Reset hesla' : 'Reset Password'); ?> - Tools4Friends</title>
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
            <h1><?php echo ($lang === 'cs' ? 'Reset hesla' : 'Reset Password'); ?></h1>
            <div class="line-break"></div>

            <?php if ($error): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="success-message"><?php echo htmlspecialchars($success); ?></div>
                <p><a href="login.php?lang=<?php echo $lang; ?>"><?php echo ($lang === 'cs' ? 'Přejít na přihlášení' : 'Go to Login'); ?></a></p>
            <?php endif; ?>

            <?php if ($valid_token && empty($success)): // Only show form if token is valid and no success message yet ?>
                <form method="POST" class="form-card">
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                    <div class="form-group">
                        <label for="new_password"><?php echo ($lang === 'cs' ? 'Nové heslo:' : 'New Password:'); ?></label>
                        <input type="password" id="new_password" name="new_password" required>
                        <small><?php echo ($lang === 'cs' ? 'Heslo musí obsahovat alespoň 8 znaků.' : 'Password must be at least 8 characters.'); ?></small>
                    </div>
                    <div class="form-group">
                        <label for="confirm_password"><?php echo ($lang === 'cs' ? 'Potvrďte nové heslo:' : 'Confirm New Password:'); ?></label>
                        <input type="password" id="confirm_password" name="confirm_password" required>
                    </div>
                    <button type="submit" class="submit-button">
                        <?php echo ($lang === 'cs' ? 'Resetovat heslo' : 'Reset Password'); ?>
                    </button>
                </form>
            <?php endif; ?>
        </main>

        <footer>
            <p>&copy; <span id="year"></span> Tools4Friends</p>
        </footer>
    </div>
</body>

</html>