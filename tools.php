<?php
// Start session at the very beginning of the file
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
include 'db_connect.php';

// Get selected language from URL or default to English
$lang = $_GET['lang'] ?? 'en';

// Get selected category or default to 'vše'
$selected_category = $_GET['category'] ?? 'vše';

// Fetch categories excluding 'vše' (All)
$category_sql = "SELECT DISTINCT category_name FROM Categories WHERE category_name != 'vše'";
$category_result = $conn->query($category_sql);

// Fetch tools based on selected category - FIXED SQL INJECTION
if ($selected_category === 'vše') {
    $tools_sql = "SELECT * FROM Tools";
    $tools_result = $conn->query($tools_sql);
} else {
    $tools_sql = "SELECT Tools.* FROM Tools
                  JOIN ToolCategories ON Tools.tool_id = ToolCategories.tool_id
                  JOIN Categories ON ToolCategories.category_id = Categories.category_id
                  WHERE Categories.category_name = ?";
    $stmt = $conn->prepare($tools_sql);
    $stmt->bind_param("s", $selected_category);
    $stmt->execute();
    $tools_result = $stmt->get_result();
}

$tools = [];
while ($tool_row = $tools_result->fetch_assoc()) {
    $tools[] = $tool_row;
}

// --- User Login Status for Navbar ---
$loggedIn = isset($_SESSION['user_id']);
$fullName = '';
if ($loggedIn) {
    $fullName = htmlspecialchars($_SESSION['firstname'] . ' ' . $_SESSION['lastname']);
}
?>

<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="description" content="Borrowing tools from friends for friends" />
    <meta name="keywords" content="Tools for Friends, tools, naradi" />
    <meta name="author" content="MPCoding" />
    <link rel="stylesheet" href="styles.css" />
    <link rel="icon" href="/favicon-dark.ico" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Libre+Baskerville:ital,wght@0,400;0,700;1,400&family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap"
        rel="stylesheet">
    <script src="script.js" defer></script>

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
                <a href="index.html?lang=<?php echo $lang; ?>" data-en="Home" data-cs="Domů">
                    <?php echo $lang === 'cs' ? 'Domů' : 'Home'; ?>
                </a>
                <a href="tools.php?lang=<?php echo $lang; ?>" data-en="Tools" data-cs="Nářadí">
                    <?php echo $lang === 'cs' ? 'Nářadí' : 'Tools'; ?>
                </a>
                <a href="contacts.html?lang=<?php echo $lang; ?>" data-en="Contacts" data-cs="Kontakty">
                    <?php echo $lang === 'cs' ? 'Kontakty' : 'Contacts'; ?>
                </a>

                <?php if ($loggedIn): ?>
                    <div class="dropdown">
                        <a href="#" class="dropbtn"><?php echo $fullName; ?></a>
                        <div class="dropdown-content">
                            <a href="profile.php?lang=<?php echo $lang; ?>" data-en="My Profile" data-cs="Můj Profil">
                                <?php echo $lang === 'cs' ? 'Můj Profil' : 'My Profile'; ?>
                            </a>
                            <a href="orders.php?lang=<?php echo $lang; ?>" data-en="My Orders" data-cs="Moje Objednávky">
                                <?php echo $lang === 'cs' ? 'Moje Objednávky' : 'My Orders'; ?>
                            </a>
                            <a href="logout.php?lang=<?php echo $lang; ?>" data-en="Log Out" data-cs="Odhlásit se">
                                <?php echo $lang === 'cs' ? 'Odhlásit se' : 'Log Out'; ?>
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="login.php?lang=<?php echo $lang; ?>" data-en="Login" data-cs="Přihlásit">
                        <?php echo $lang === 'cs' ? 'Přihlásit' : 'Login'; ?>
                    </a>
                <?php endif; ?>
            </div>

            <div class="nav-right language-toggle">
                <button onclick="switchLanguage('en', '<?php echo basename($_SERVER['PHP_SELF']); ?>')">English</button>
                <button onclick="switchLanguage('cs', '<?php echo basename($_SERVER['PHP_SELF']); ?>')">Čeština</button>
            </div>
        </nav>

        <main>
            <h1 class="page_title"><?php echo $lang === 'cs' ? 'Nářadí' : 'Tools'; ?></h1>

            <nav class="category-nav">

                <?php while ($category_row = $category_result->fetch_assoc()): ?>
                    <a href="tools.php?category=<?php echo urlencode($category_row['category_name']); ?>&lang=<?php echo $lang; ?>"
                        class="<?php echo $selected_category === $category_row['category_name'] ? 'active' : ''; ?>">
                        <?php echo htmlspecialchars($category_row['category_name']); ?>
                    </a>
                <?php endwhile; ?>
            </nav>

            <div class="tool-list">
                <?php foreach ($tools as $tool):
                    $name = $lang === 'cs' && !empty($tool['name_cs']) ? $tool['name_cs'] : $tool['name'];
                    $description = $lang === 'cs' && !empty($tool['description_cs']) ? $tool['description_cs'] : $tool['description'];
                    $technical_data = $lang === 'cs' && !empty($tool['technical_data_cs']) ? $tool['technical_data_cs'] : $tool['technical_data'];
                    ?>
                    <div class="tool-block">
                        <img src="<?php echo htmlspecialchars($tool['picture']); ?>"
                            alt="<?php echo htmlspecialchars($name); ?>">
                        <h3><?php echo htmlspecialchars($name); ?></h3>
                        <div class="left-text">
                            <p><strong><?php echo $lang === 'cs' ? 'Popis:' : 'Description:'; ?></strong>
                                <?php echo htmlspecialchars($description); ?></p>
                            <p><strong><?php echo $lang === 'cs' ? 'Značka:' : 'Brand:'; ?></strong>
                                <?php echo htmlspecialchars($tool['brand']); ?></p>
                            <p><strong><?php echo $lang === 'cs' ? 'Model:' : 'Model:'; ?></strong>
                                <?php echo htmlspecialchars($tool['model']); ?></p>
                            <p><strong><?php echo $lang === 'cs' ? 'Technické Detaily:' : 'Technical Details:'; ?></strong>
                                <?php echo htmlspecialchars($technical_data); ?></p>
                            <p><strong><?php echo $lang === 'cs' ? 'Majitel:' : 'Owner:'; ?></strong>
                                <?php echo htmlspecialchars($tool['ownerID']); ?></p>
                        </div>
                        <div>
                            <a href="tool_availability.php?tool_id=<?php echo $tool['tool_id']; ?>&lang=<?php echo $lang; ?>"
                                class="availability-button">
                                <?php echo $lang === 'cs' ? 'Zkontrolovat Dostupnost' : 'Check Availability'; ?>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </main>

        <footer>
            <p>&copy; <span id="year"></span> Tools4Friends</p>
        </footer>
    </div>
</body>

</html>
