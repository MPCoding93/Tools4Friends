<?php
require_once __DIR__ . '/app/security.php';

startSecureSession();

$lang = $_GET['lang'] ?? 'en';

$loggedIn = isset($_SESSION['user_id']);
$fullName = '';
if ($loggedIn) {
    $fullName = sanitizeOutput($_SESSION['firstname'] . ' ' . $_SESSION['lastname']);
}
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
          data-en="Tools 4 Friends is a website where friends can display their tools for lending them to their friends."
          data-cs="Tools 4 Friends je webová stránka, kde si přátelé mohou půjčovat nářadí."
        >
          Tools 4 Friends is a website where friends can display their tools
          for lending them to their friends.
        </p>

        <h2 data-en="How does it work?" data-cs="Jak to funguje?">
          How does it work?
        </h2>

        <p
          data-en="It is possible for each of you to sign up to lend tools."
          data-cs="Každý z vás se může zaregistrovat a půjčovat nářadí."
        >
          It is possible for each of you to sign up to lend tools.
        </p>

        <p
          data-en="This page is not to make any money, however the owner of his tool may ask for a manipulation fee and deposit for his/her tool."
          data-cs="Tato stránka není určena k výdělku, ale majitel nářadí může požadovat manipulační poplatek a zálohu."
        >
          This page is not to make any money, however the owner of his tool may
          ask for a manipulation fee and deposit for his/her tool.
        </p>

        <p
          data-en="The deposit will be returned once the tool is back and in the same condition it was lend."
          data-cs="Záloha bude vrácena, jakmile bude nářadí vráceno ve stejném stavu, v jakém bylo půjčeno."
        >
          The deposit will be returned once the tool is back and in the same
          condition it was lend.
        </p>

        <p
          data-en="The manipulation fee is calculated on the estimated lifetime of the tool, the estimated times the tool will be used and should cover any repairs or buying of new tool if it broke down."
          data-cs="Manipulační poplatek je vypočítán na základě odhadované životnosti nářadí, počtu jeho použití a měl by pokrýt opravy nebo nákup nového nářadí v případě poruchy."
        >
          The manipulation fee is calculated on the estimated lifetime of the
          tool, the estimated times the tool will be used and should cover any
          repairs or buying of new tool if it broke down.
        </p>

        <p
          data-en='For more information please contact us <a href="public/contacts.php?lang=<?php echo $lang; ?>">here</a>'
          data-cs='Pro více informací nás prosím kontaktujte <a href="public/contacts.php?lang=<?php echo $lang; ?>">zde</a>'
        >
          For more information please contact us
          <a href="public/contacts.php?lang=<?php echo sanitizeOutput($lang); ?>">here</a>
        </p>
      </main>
      <footer>
        <p>© <span id="year"></span> Tools4Friends</p>
      </footer>
    </div>
  </body>
</html>
