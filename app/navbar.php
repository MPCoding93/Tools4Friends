<?php
// This file assumes session_start() has already been called in the main script
// and $lang variable is set.

$loggedIn = isset($_SESSION['user_id']);
$fullName = '';
if ($loggedIn) {
    $fullName = htmlspecialchars($_SESSION['firstname'] . ' ' . $_SESSION['lastname']);
}

// Determine the current page's base name for language switching
$currentPageBasename = basename($_SERVER['PHP_SELF']);
?>

<nav>
    <div class="nav-left">
        <a href="/index.php?lang=<?php echo $lang; ?>" data-en="Home" data-cs="Domů">
            <?php echo $lang === 'cs' ? 'Domů' : 'Home'; ?>
        </a>
        <a href="/public/tools.php?lang=<?php echo $lang; ?>" data-en="Tools" data-cs="Nářadí">
            <?php echo $lang === 'cs' ? 'Nářadí' : 'Tools'; ?>
        </a>
        <a href="/public/contacts.php?lang=<?php echo $lang; ?>" data-en="Contacts" data-cs="Kontakty">
            <?php echo $lang === 'cs' ? 'Kontakty' : 'Contacts'; ?>
        </a>

        <?php if ($loggedIn): ?>
            <div class="dropdown">
                <a href="#" class="dropbtn"><?php echo $fullName; ?></a>
                <div class="dropdown-content">
                    <a href="/public/myprofile.php?lang=<?php echo $lang; ?>" data-en="My Profile" data-cs="Můj Profil">
                        <?php echo $lang === 'cs' ? 'Můj Profil' : 'My Profile'; ?>
                    </a>
                    <a href="/public/myorders.php?lang=<?php echo $lang; ?>" data-en="My Orders" data-cs="Moje Objednávky">
                        <?php echo $lang === 'cs' ? 'Moje Objednávky' : 'My Orders'; ?>
                    </a>    
                    <a href="/public/logout.php?lang=<?php echo $lang; ?>" data-en="Log Out" data-cs="Odhlásit se">
                        <?php echo $lang === 'cs' ? 'Odhlásit se' : 'Log Out'; ?>
                    </a>
                </div>
            </div>
        <?php else: ?>
            <a href="/public/login.php?lang=<?php echo $lang; ?>" data-en="Login" data-cs="Přihlásit">
                <?php echo $lang === 'cs' ? 'Přihlásit' : 'Login'; ?>
            </a>
        <?php endif; ?>
    </div>

    <div class="nav-right language-toggle">
        <button onclick="switchLanguage('en', '<?php echo $currentPageBasename; ?>')">English</button>
        <button onclick="switchLanguage('cs', '<?php echo $currentPageBasename; ?>')">Čeština</button>
    </div>
    <div><h1>SITE UNDER CONSTRUCTION</h1></div>
</nav>
