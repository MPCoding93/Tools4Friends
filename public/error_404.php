<?php
define('APP_INIT', true);
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/security.php';

startSecureSession();

$lang = $_GET['lang'] ?? 'en';
$loggedIn = isset($_SESSION['user_id']);
$fullName = '';
if ($loggedIn) {
    $fullName = sanitizeOutput($_SESSION['firstname'] . ' ' . $_SESSION['lastname']);
}

// Log 404 error
error_log('404 Error: ' . ($_SERVER['REQUEST_URI'] ?? 'Unknown URI') . ' | Referrer: ' . ($_SERVER['HTTP_REFERER'] ?? 'None'));
?>
<!DOCTYPE html>
<html lang="<?php echo sanitizeOutput($lang); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $lang === 'cs' ? 'Stránka nenalezena' : 'Page Not Found'; ?> - Tools4Friends</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="icon" href="favicon/favicon-dark.ico" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Libre+Baskerville:ital,wght@0,400;0,700;1,400&family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <script src="script.js" defer></script>
    <style>
        .error-container {
            text-align: center;
            padding: 50px 20px;
            min-height: 60vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }
        .error-code {
            font-size: 120px;
            font-weight: bold;
            color: #1F2D5A;
            margin: 0;
            line-height: 1;
        }
        .error-message {
            font-size: 24px;
            color: #333;
            margin: 20px 0;
        }
        .error-description {
            font-size: 16px;
            color: #666;
            max-width: 600px;
            margin: 20px auto;
        }
        .error-actions {
            margin-top: 30px;
        }
        .error-actions a {
            display: inline-block;
            padding: 12px 30px;
            margin: 0 10px;
            background-color: #1F2D5A;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s;
        }
        .error-actions a:hover {
            background-color: #2a3d6f;
        }
        .error-actions a.secondary {
            background-color: #6c757d;
        }
        .error-actions a.secondary:hover {
            background-color: #5a6268;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <div class="banner">
                <img src="images/banners/tools4friends_dark_Banner_2000x400.png" alt="Tools4Friends Logo" />
            </div>
        </header>
        <div class="line-break"></div>
        <?php include __DIR__ . '/../app/navbar.php'; ?>

        <main>
            <div class="error-container">
                <h1 class="error-code">404</h1>
                <h2 class="error-message">
                    <?php echo $lang === 'cs' ? 'Stránka nenalezena' : 'Page Not Found'; ?>
                </h2>
                <p class="error-description">
                    <?php echo $lang === 'cs' 
                        ? 'Omlouváme se, ale stránka, kterou hledáte, neexistuje nebo byla přesunuta.'
                        : 'Sorry, the page you are looking for does not exist or has been moved.'; ?>
                </p>
                <div class="error-actions">
                    <a href="<?php echo getUrl('index.php?lang=' . $lang); ?>">
                        <?php echo $lang === 'cs' ? '🏠 Domů' : '🏠 Home'; ?>
                    </a>
                    <a href="<?php echo getPublicUrl('tools.php?lang=' . $lang); ?>" class="secondary">
                        <?php echo $lang === 'cs' ? '🔧 Nářadí' : '🔧 Tools'; ?>
                    </a>
                    <a href="<?php echo getPublicUrl('contacts.php?lang=' . $lang); ?>" class="secondary">
                        <?php echo $lang === 'cs' ? '📧 Kontakt' : '📧 Contact'; ?>
                    </a>
                </div>
            </div>
        </main>

        <footer>
            <p>&copy; <span id="year"></span> Tools4Friends</p>
        </footer>
    </div>
</body>
</html>
