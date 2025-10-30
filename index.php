<?php
require_once __DIR__ . '/app/security.php';
require_once __DIR__ . '/app/db_connect.php';
require_once __DIR__ . '/app/language_init.php';
require_once __DIR__ . '/app/cookie_functions.php';

startSecureSession();

$lang = initializeLanguage($conn);

$loggedIn = isset($_SESSION['user_id']);
$fullName = '';
if ($loggedIn) {
    $fullName = sanitizeOutput($_SESSION['firstname'] . ' ' . $_SESSION['lastname']);
}

// Determine if we're in the public folder or root for cookie consent
$inPublicFolder = false;
?>
<!DOCTYPE html>
<html lang="<?php echo sanitizeOutput($lang); ?>">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta
      name="description"
      content="Borrowing tools from friends for friends"
    />
    <meta name="keywords" content="Tools for Friends, tools, naradi" />
    <meta name="author" content="MPCoding" />
    <link rel="stylesheet" href="public/styles.css" /> <!-- Updated path -->
    <link rel="icon" href="public/favicon/favicon-dark.ico" /> <!-- Updated path -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Libre+Baskerville:ital,wght@0,400;0,700;1,400&family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap"
      rel="stylesheet"
    />
    <script src="public/script.js" defer></script> <!-- Updated path -->

    <title>Tools4Friends</title>
  </head>
  <body>
    <?php include 'app/cart_icon.php'; ?>
    
    <div class="container">
      <header>
        <div class="banner">
          <img
            src="public/images/banners/tools4friends_dark_Banner_2000x400.png"
            alt="Company Logo"
          /> <!-- Updated path -->
        </div>
      </header>
      <div class="line-break"></div>
      <?php include 'app/navbar.php'; // Updated path ?>

      <main>
  <h1
    data-en="Welcome to Tools 4 Friends"
    data-cs="Vítejte na Tools 4 Friends"
  >
    Welcome to Tools 4 Friends
  </h1>

  <p
    data-en="Tools 4 Friends is a community-driven platform where friends can share and lend tools with one another. Whether you're tackling a home improvement project, fixing up your bike, or just need a tool for a quick job, this is the place to find what you need—without having to buy it."
    data-cs="Tools 4 Friends je komunitní platforma, kde si přátelé mohou navzájem půjčovat nářadí. Ať už pracujete na domácím projektu, opravujete kolo nebo potřebujete nářadí na jednorázovou práci, zde najdete, co potřebujete – bez nutnosti nákupu."
  >
    Tools 4 Friends is a community-driven platform where friends can share and lend tools with one another. Whether you're tackling a home improvement project, fixing up your bike, or just need a tool for a quick job, this is the place to find what you need—without having to buy it.
  </p>

  <h2 data-en="How It Works" data-cs="Jak to funguje?">
    How It Works
  </h2>

  <p
    data-en="Sign up to join the community and list the tools you're happy to lend. Need a tool? Browse what's available and request to borrow from your friends."
    data-cs="Zaregistrujte se do komunity a nabídněte nářadí, které můžete půjčit. Potřebujete nářadí? Prohlédněte si dostupné položky a požádejte o zapůjčení od přátel."
  >
    Sign up to join the community and list the tools you're happy to lend. Need a tool? Browse what's available and request to borrow from your friends.
  </p>

  <p
    data-en="This site is not for making money. However, tool owners may request a small handling fee and a refundable deposit to ensure tools are returned in good condition."
    data-cs="Tato stránka není určena k výdělku. Majitelé nářadí však mohou požadovat malý manipulační poplatek a vratnou zálohu, aby bylo zajištěno vrácení nářadí v dobrém stavu."
  >
    This site is not for making money. However, tool owners may request a small handling fee and a refundable deposit to ensure tools are returned in good condition.
  </p>

  <p
    data-en="The deposit will be fully refunded once the tool is returned in the same condition it was lent."
    data-cs="Záloha bude plně vrácena, jakmile bude nářadí vráceno ve stejném stavu, v jakém bylo půjčeno."
  >
    The deposit will be fully refunded once the tool is returned in the same condition it was lent.
  </p>

  <p
    data-en="The handling fee is based on the tool’s estimated lifespan and usage, helping to cover maintenance or replacement if needed."
    data-cs="Manipulační poplatek je založen na odhadované životnosti a využití nářadí a pomáhá pokrýt údržbu nebo případnou výměnu."
  >
    The handling fee is based on the tool’s estimated lifespan and usage, helping to cover maintenance or replacement if needed.
  </p>

  <p
    data-en='We’re here to help! For more information, please contact us <a href="public/contacts.php?lang=<?php echo sanitizeOutput($lang); ?>">here</a>.'
  >
    We’re here to help! For more information, please contact us
    <a href="public/contacts.php?lang=<?php echo sanitizeOutput($lang); ?>">here</a>.
  </p>
</main>
      <footer>
        <p>© <span id="year"></span> Tools4Friends</p>
      </footer>
    </div>
    
    <?php include 'app/cookie_consent.php'; ?>
  </body>
</html>
