<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="description" content="Borrowing tools from friends for friends" />
    <meta name="keywords" content="Tools for Friends, tools, naradi" />
    <meta name="author" content="MPCoding" />
    <link rel="stylesheet" href="styles.css" />
    <link rel="icon" href="/favicon-dark.ico" />
    https://fonts.googleapis.com
    <link rel="preconnect" href=" <link
        href="https://fonts.googleapis.com/css2?family=Libre+Baskerville:ital,wght@0,400;0,700;1,400&family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap   function setLanguage(lang) {
            document.querySelectorAll("[data-en]").forEach((el) => {
                el.textContent = el.getAttribute(`data-${lang}`);
            });
        }
    </script>

    <title>Tools4Friends</title>
</head>

<body>
    <div class="container">
        <header>
            <div class="banner">
                <img src="/tools4friends_dark_Banner_2000x400.png" alt="Company Logo" />
            </div>
        </header>
        <div class="line-break"></div>

        <nav>
            <div class="nav-left">
                <a href="index.html" data-en="Home" data-cs="Domů">Home</a> &nbsp;
                <a href="tools.php" data-en="Tools" data-cs="Nářadí">Tools</a> &nbsp;
                <a href="contacts.html" data-en="Contacts" data-cs="Kontakty">Contacts</a>
            </div>

            <div class="nav-right language-toggle">
                <button onclick="setLanguage('en')">English</button> &nbsp;&nbsp;&nbsp;
                <button onclick="setLanguage('cs')">Čeština</button> &nbsp;
            </div>
        </nav>

        <div class="content">
            <h1 class="page_title" data-en="Tools" data-cs="Nářadí">Tools</h1>
            <nav class="category-nav">
                <?php
                // Include database connection
                include 'db_connect.php';

                // Add "vše" category manually
                //echo '<a href="tools.php?category=vše" data-en="All" data-cs="Vše">All</a>';
                
                $category_sql = "SELECT DISTINCT category_name FROM Categories";
                $category_result = $conn->query($category_sql);
                while ($category_row = $category_result->fetch_assoc()) {
                    echo '<a href="tools.php?category=' . $category_row['category_name'] . '" data-en="' . $category_row['category_name'] . '" data-cs="' . $category_row['category_name'] . '">' . $category_row['category_name'] . '</a>';
                }
                ?>
            </nav>
            <div class="tool-list">
                <?php
                // Fetch tools based on selected category
                $selected_category = $_GET['category'] ?? 'vše';
                if ($selected_category === 'vše') {
                    $tools_sql = "SELECT * FROM Tools";
                } else {
                    $tools_sql = "SELECT Tools.* FROM Tools 
                                      JOIN ToolCategories ON Tools.tool_id = ToolCategories.tool_id 
                                      JOIN Categories ON ToolCategories.category_id = Categories.category_id 
                                      WHERE Categories.category_name = '$selected_category'";
                }
                $tools_result = $conn->query($tools_sql);
                $tools = [];
                while ($tool_row = $tools_result->fetch_assoc()) {
                    $tools[] = $tool_row;
                }

                foreach ($tools as $tool): ?>
                    <div class="tool-block">
                        <img src="<?php echo $tool['picture']; ?>" alt="<?php echo $tool['name']; ?>">
                        <h3 data-en="<?php echo $tool['name']; ?>" data-cs="<?php echo $tool['name']; ?>"><?php echo $tool['name']; ?></h3>
                        <div class="left-text">
                            <p><strong data-en="Description:" data-cs="Popis:">Description:</strong> <?php echo $tool['description']; ?></p>
                            <p><strong data-en="Brand:" data-cs="Značka:">Brand:</strong> <?php echo $tool['brand']; ?></p>
                            <p><strong data-en="Model:" data-cs="Model:">Model:</strong> <?php echo $tool['model']; ?></p>
                            <p><strong data-en="Technical Details:" data-cs="Technické Detaily:">Technical Details:</strong> <?php echo $tool['technical_data']; ?></p>
                            <!--
                            <p><strong data-en="Deposit:" data-cs="Záloha:">Deposit:</strong> <?php echo $tool['deposit']; ?>Kč</p>
                            <p><strong data-en="Manipulation Fee:" data-cs="Manipulační Poplatek:">Manipulation Fee:</strong> <?php echo $tool['manipulation_fee']; ?>Kč</p> 
                            -->
                            <p><strong data-en="Owner:" data-cs="Majitel:">Owner:</strong> <?php echo $tool['ownerID']; ?></p>
                        </div>
                        <div><a href="tool_availability.php" class="availability-button" data-en="Check Availability" data-cs="Zkontrolovat Dostupnost">Check Availability</a></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <footer>
            <p>&copy; <span id="year"></span> Tools4Friends</p>
            <script>
                document.getElementById('year').textContent = new Date().getFullYear();
            </script>
        </footer>

    </div>
</body>

</html>
