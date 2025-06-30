<?php
// Start session at the very beginning of the file
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
include __DIR__ . '/../app/db_connect.php'; // Path from public/tools.php to app/db_connect.php

// Get selected language from URL or default to English
$lang = $_GET['lang'] ?? 'en';

// Get selected category or default to 'All'
$selected_category = $_GET['category'] ?? 'All';

// Fetch categories
$category_sql = "SELECT DISTINCT category_name FROM Categories";
$category_result = $conn->query($category_sql);

// Check for SQL errors
if (!$category_result) {
    die("Database query failed: " . $conn->error);
}

// Fetch tools based on selected category - FIXED SQL INJECTION
if ($selected_category === 'All') {
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

// Check for SQL errors
if (!$tools_result) {
    die("Database query failed: " . $conn->error);
}

$tools = [];
while ($tool_row = $tools_result->fetch_assoc()) {
    $tools[] = $tool_row;
}

// --- User Login Status for Navbar ---
// These variables need to be defined BEFORE including navbar.php
$loggedIn = isset($_SESSION['user_id']);
$fullName = '';
if ($loggedIn) {
    // Assuming 'firstname' and 'lastname' are stored in the session upon login
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
    <link rel="icon" href="favicon/favicon-dark.ico" />
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
                <img src="images/banners/tools4friends_dark_Banner_2000x400.png" alt="Company Logo" />
            </div>
        </header>
        <div class="line-break"></div>

        <?php include __DIR__ . '/../app/navbar.php'; ?>

        <main>
            <h1 class="page_title"><?php echo $lang === 'cs' ? 'Nářadí' : 'Tools'; ?></h1>

            <!-- Category Filter Container -->
            <div class="category-filter-container">
                <!-- Desktop Category Navigation -->
                <nav class="category-nav-desktop">
                    <!-- Add "All" category option with relative path -->
                    <a href="./public/tools.php?category=All&lang=<?php echo $lang; ?>"
                        class="<?php echo $selected_category === 'All' ? 'active' : ''; ?>">
                        <?php echo $lang === 'cs' ? 'Vše' : 'All'; ?>
                    </a>

                    <?php
                    // Reset result pointer if needed
                    if ($category_result->num_rows > 0) {
                        $category_result->data_seek(0);
                    }

                    while ($category_row = $category_result->fetch_assoc()): ?>
                        <a href="./public/tools.php?category=<?php echo urlencode($category_row['category_name']); ?>&lang=<?php echo $lang; ?>"
                            class="<?php echo $selected_category === $category_row['category_name'] ? 'active' : ''; ?>">
                            <?php echo htmlspecialchars($category_row['category_name']); ?>
                        </a>
                    <?php endwhile; ?>
                </nav>

                <!-- Mobile/Tablet Category Dropdown -->
                <div class="category-dropdown-mobile">
                    <label for="category-select" class="sr-only">Select Category</label>
                    <select id="category-select" onchange="location = this.value;">
                        <option value="./public/tools.php?category=All&lang=<?php echo $lang; ?>"
                            <?php echo $selected_category === 'All' ? 'selected' : ''; ?>>
                            <?php echo $lang === 'cs' ? 'Vše' : 'All'; ?>
                        </option>
                        <?php
                        // Reset result pointer again for the dropdown
                        if ($category_result->num_rows > 0) {
                            $category_result->data_seek(0);
                        }
                        while ($category_row = $category_result->fetch_assoc()): ?>
                            <option value="./public/tools.php?category=<?php echo urlencode($category_row['category_name']); ?>&lang=<?php echo $lang; ?>"
                                <?php echo $selected_category === $category_row['category_name'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category_row['category_name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div> <!-- End category-filter-container -->

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
                            <p><strong><?php echo $lang === 'cs' ? 'Poplatek:' : 'Fee:'; ?></strong>
                                <?php echo htmlspecialchars($tool['manipulation_fee']); ?>Kc</p>
                            <p><strong><?php echo $lang === 'cs' ? 'Majitel:' : 'Owner:'; ?></strong>
                                <?php echo htmlspecialchars($tool['ownerID']); ?></p>
                        </div>
                        <div>
                            <a href="./public/tool_availability.php?tool_id=<?php echo $tool['tool_id']; ?>&lang=<?php echo $lang; ?>"
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
