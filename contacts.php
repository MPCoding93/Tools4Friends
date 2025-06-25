<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta
      name="description"
      content="Borrowing tools from friends for friends"
    />
    <meta name="keywords" content="Tools for Friends, tools, naradi" />
    <meta name="author" content="MPCoding" />
    <link rel="stylesheet" href="styles.css" />
    <link rel="icon" href="/favicon-dark.ico" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Libre+Baskerville:ital,wght@0,400;0,700;1,400&family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap"
      rel="stylesheet"
    />
    <script src="script.js" defer></script>

    <title>Tools4Friends - Contacts</title>
  </head>

  <body>
    <div class="container">
      <header>
        <div class="banner">
          <img
            src="/tools4friends_dark_Banner_2000x400.png"
            alt="Company Logo"
          />
        </div>
      </header>
      <div class="line-break"></div>

      <nav>
        <div class="nav-left">
          <a href="index.html" data-en="Home" data-cs="Domů">Home</a>  
          <a href="tools.php" data-en="Tools" data-cs="Nářadí">Tools</a>  
          <a href="contacts.html" data-en="Contacts" data-cs="Kontakty">Contacts</a>
        </div>

        <div class="nav-right language-toggle">
          <button onclick="switchLanguage('en', 'contacts.html')">English</button>    
          <button onclick="switchLanguage('cs', 'contacts.html')">Čeština</button>  
        </div>
      </nav>

      <main id="contact-container">
        <div id="contact">
          <h1 data-en="How to reach us?" data-cs="Jak nás kontaktovat?">How to reach us?</h1>
          <h2 data-en="Contact Details" data-cs="Kontaktní údaje">Contact Details</h2>
          
          <div class="profile">
            <img src="https://i.ibb.co/pjnxkGVb/Profile-Picture.jpg" alt="Michael Pauwels" class="profile-pic">
            <div>
              <h3>Michael Pauwels</h3>
            </div>
          </div>
        
          <div class="profile">
            <img src="https://i.ibb.co/qZd1jR0/No-Profile-Picture.jpg" alt="Pavel Eleder" class="profile-pic">
            <div>
              <h3>Pavel Eleder</h3>
            </div>
          </div>
          
          <div class="contact-info">
            <p><strong data-en="Email:" data-cs="Email:">Email:</strong> 
              <a href="mailto:info@tools4friends.kvalitne.cz?subject=Info%20request">
                info@tools4friends.kvalitne.cz
              </a>
            </p>
          </div>
        </div>
      </main>
      
      <footer>
        <p>&copy; <span id="year"></span> Tools4Friends</p>
      </footer>
    </div>
  </body>
</html>
