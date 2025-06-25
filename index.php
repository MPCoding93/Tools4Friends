    <script src="script.js" defer></script>
    <title>Tools4Friends</title>
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
      <?php include 'navbar.php'; // Include the navbar ?>

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
          data-en='For more information please contact us <a href="contacts.html">here</a>'
          data-cs='Pro více informací nás prosím kontaktujte <a href="contacts.html">zde</a>'
        >
          For more information please contact us
          <a href="contacts.html">here</a>
        </p>
      </main>
      <footer>
        <p>&copy; <span id="year"></span> Tools4Friends</p>
        <script>
          document.getElementById("year").textContent =
            new Date().getFullYear();
        </script>
      </footer>
    </div>
  </body>
</html>
