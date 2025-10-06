<?php
// This file assumes session_start() has already been called in the main script
// and $lang variable is set.

$loggedIn = isset($_SESSION['user_id']);
$fullName = '';
if ($loggedIn) {
    $fullName = sanitizeOutput($_SESSION['firstname'] . ' ' . $_SESSION['lastname']);
}

// Determine the current page's base name for language switching
$currentPageBasename = basename($_SERVER['PHP_SELF']);

// Determine if we're in the public folder or root
$inPublicFolder = (strpos($_SERVER['PHP_SELF'], '/public/') !== false);
$homeLink = $inPublicFolder ? '../index.php' : './index.php';
$toolsLink = $inPublicFolder ? './tools.php' : './public/tools.php';
$contactsLink = $inPublicFolder ? './contacts.php' : './public/contacts.php';
$loginLink = $inPublicFolder ? './login.php' : './public/login.php';
$myprofileLink = $inPublicFolder ? './myprofile.php' : './public/myprofile.php';
$myordersLink = $inPublicFolder ? './myorders.php' : './public/myorders.php';
$logoutLink = $inPublicFolder ? './logout.php' : './public/logout.php';
?>

<nav>
    <div class="nav-left">
        <a href="<?php echo $homeLink; ?>?lang=<?php echo sanitizeOutput($lang); ?>" data-en="Home" data-cs="Domů">
            <?php echo $lang === 'cs' ? 'Domů' : 'Home'; ?>
        </a>
        <a href="<?php echo $toolsLink; ?>?lang=<?php echo sanitizeOutput($lang); ?>" data-en="Tools" data-cs="Nářadí">
            <?php echo $lang === 'cs' ? 'Nářadí' : 'Tools'; ?>
        </a>
        <a href="<?php echo $contactsLink; ?>?lang=<?php echo sanitizeOutput($lang); ?>" data-en="Contacts" data-cs="Kontakty">
            <?php echo $lang === 'cs' ? 'Kontakty' : 'Contacts'; ?>
        </a>

        <?php if ($loggedIn): ?>
            <div class="dropdown">
                <a href="#" class="dropbtn"><?php echo $fullName; ?></a>
                <div class="dropdown-content">
                    <a href="<?php echo $myprofileLink; ?>?lang=<?php echo sanitizeOutput($lang); ?>" data-en="My Profile" data-cs="Můj Profil">
                        <?php echo $lang === 'cs' ? 'Můj Profil' : 'My Profile'; ?>
                    </a>
                    <a href="<?php echo $myordersLink; ?>?lang=<?php echo sanitizeOutput($lang); ?>" data-en="My Orders" data-cs="Moje Objednávky">
                        <?php echo $lang === 'cs' ? 'Moje Objednávky' : 'My Orders'; ?>
                    </a>
                    <?php if (isset($_SESSION['admin']) && $_SESSION['admin'] == 1): ?>
                        <a href="<?php echo $inPublicFolder ? './admin_orders.php' : './public/admin_orders.php'; ?>?lang=<?php echo sanitizeOutput($lang); ?>" data-en="Admin Panel" data-cs="Administrace">
                            <?php echo $lang === 'cs' ? 'Administrace' : 'Admin Panel'; ?>
                        </a>
                    <?php endif; ?>
                    <a href="<?php echo $logoutLink; ?>?lang=<?php echo sanitizeOutput($lang); ?>" data-en="Log Out" data-cs="Odhlásit se">
                        <?php echo $lang === 'cs' ? 'Odhlásit se' : 'Log Out'; ?>
                    </a>
                </div>
            </div>
        <?php else: ?>
            <a href="<?php echo $loginLink; ?>?lang=<?php echo sanitizeOutput($lang); ?>" data-en="Login" data-cs="Přihlásit">
                <?php echo $lang === 'cs' ? 'Přihlásit' : 'Login'; ?>
            </a>
        <?php endif; ?>
    </div>

    <div class="nav-right language-toggle">
        <button onclick="switchLanguage('en', '<?php echo sanitizeOutput($currentPageBasename); ?>')">English</button>
        <button onclick="switchLanguage('cs', '<?php echo sanitizeOutput($currentPageBasename); ?>')">Čeština</button>
    </div>
</nav>
<br>
<div class="underconstruction"><h1>SITE UNDER CONSTRUCTION</h1></div>
