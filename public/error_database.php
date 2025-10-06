<?php
define('APP_INIT', true);
$lang = $_GET['lang'] ?? 'en';

// Don't try to connect to database or start session since database is down
// Log error if possible
if (function_exists('error_log')) {
    error_log('Database Error: Connection failed | URI: ' . ($_SERVER['REQUEST_URI'] ?? 'Unknown'));
}
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($lang); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $lang === 'cs' ? 'Chyba databáze' : 'Database Error'; ?> - Tools4Friends</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="icon" href="favicon/favicon-dark.ico" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Libre+Baskerville:ital,wght@0,400;0,700;1,400&family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Montserrat', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .error-container {
            text-align: center;
            padding: 50px 20px;
            min-height: 60vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-top: 50px;
        }
        .error-icon {
            font-size: 80px;
            margin-bottom: 20px;
        }
        .error-code {
            font-size: 48px;
            font-weight: bold;
            color: #dc3545;
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
            line-height: 1.6;
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
        .banner {
            text-align: center;
            margin-bottom: 20px;
        }
        .banner img {
            max-width: 100%;
            height: auto;
        }
        footer {
            text-align: center;
            padding: 20px;
            color: #666;
            margin-top: 50px;
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

        <main>
            <div class="error-container">
                <div class="error-icon">🔌</div>
                <h1 class="error-code">
                    <?php echo $lang === 'cs' ? 'CHYBA DATABÁZE' : 'DATABASE ERROR'; ?>
                </h1>
                <h2 class="error-message">
                    <?php echo $lang === 'cs' ? 'Připojení k databázi selhalo' : 'Database Connection Failed'; ?>
                </h2>
                <p class="error-description">
                    <?php echo $lang === 'cs' 
                        ? 'Omlouváme se, ale momentálně se nemůžeme připojit k databázi. Tato chyba byla automaticky nahlášena našemu týmu.'
                        : 'Sorry, we are currently unable to connect to the database. This error has been automatically reported to our team.'; ?>
                </p>
                <p class="error-description">
                    <?php echo $lang === 'cs' 
                        ? 'Možné příčiny:'
                        : 'Possible causes:'; ?>
                </p>
                <ul style="text-align: left; max-width: 500px; margin: 20px auto; color: #666;">
                    <li><?php echo $lang === 'cs' ? 'Databázový server je dočasně nedostupný' : 'Database server is temporarily unavailable'; ?></li>
                    <li><?php echo $lang === 'cs' ? 'Probíhá údržba systému' : 'System maintenance in progress'; ?></li>
                    <li><?php echo $lang === 'cs' ? 'Problém s připojením k síti' : 'Network connection issue'; ?></li>
                </ul>
                <p class="error-description">
                    <?php echo $lang === 'cs' 
                        ? 'Zkuste to prosím znovu za několik minut. Pokud problém přetrvává, kontaktujte nás.'
                        : 'Please try again in a few minutes. If the problem persists, please contact us.'; ?>
                </p>
                <div class="error-actions">
                    <a href="javascript:location.reload()">
                        <?php echo $lang === 'cs' ? '🔄 Zkusit znovu' : '🔄 Try Again'; ?>
                    </a>
                    <a href="contacts.php?lang=<?php echo htmlspecialchars($lang); ?>">
                        <?php echo $lang === 'cs' ? '📧 Kontaktovat podporu' : '📧 Contact Support'; ?>
                    </a>
                </div>
            </div>
        </main>

        <footer>
            <p>&copy; <?php echo date('Y'); ?> Tools4Friends</p>
        </footer>
    </div>
</body>
</html>
