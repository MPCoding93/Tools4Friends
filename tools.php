<?php
// Include database connection
include 'db_connect.php';

// Get selected language from URL or default to English
$lang = $_GET['lang'] ?? 'en';

// Get selected category or default to 'vše'
$selected_category = $_GET['category'] ?? 'vše';

// Fetch categories
$category_sql = "SELECT DISTINCT category_name FROM Categories";
$category_result = $conn->query($category_sql);

// Fetch tools based on selected category
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
                <a href="index.html?lang=<?php echo $lang; ?>" data-en="Home" data-cs="Domů">Home</a>&nbsp;
               lang=<?php echo $lang; ?>" data-en="Tools" data-cs="Nářadí">Tools</a>&nbsp;
                <a href="contacts.html?lang=<?php echo $lang; ?>" data-en="Contacts" data-cs="Kontakty">Contacts</a>
            </div>

            <div class="nav-right language-toggle">
                <button onclick="window.location.href='tools.php?lang=en'">English</button>&nbsp;&nbsp;&nbsp;
                <button onclick="window.location.href='tools.php?lang=cs'">Čeština</button>&nbsp;
            </div>
        </nav>

        <div class="content">
            <h1 class="page_title"><?php echo $lang === 'cs' ? 'Nářadí' : 'Tools'; ?></h1>

            <nav class="category-nav">
                <a href="tools.php?category=vše&lang=<?php echo $lang; ?>">
                    <?php echo $lang === 'cs' ? 'Vše' : 'All'; ?>
                </a>
                <?php while ($category_row = $category_result->fetch_assoc()): ?>
                    <a href="tools.php?category=<?php echo urlencode($category_row['category_name']); ?>&lang=<?php echo $lang; ?>">
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
                        <img src="<?php echo $tool['picture']; ?>" alt="<?php echo htmlspecialchars($name); ?>">
                        <h3><?php echo htmlspecialchars($name); ?></h3>
                        <div class="left-text">
                            <p><strong><?php echo $lang === 'cs' ? 'Popis:' : 'Description:'; ?></strong> <?php echo htmlspecialchars($description); ?></p>
                            <p><strong><?php echo $lang === 'cs' ? 'Značka:' : 'Brand:'; ?></strong> <?php echo htmlspecialchars($tool['brand']); ?></p>
                            <p><strong><?php echo $lang === 'cs' ? 'Model:' : 'Model:'; ?></strong> <?php echo htmlspecialchars($tool['model']); ?></p>
                            <p><strong><?php echo $lang === 'cs' ? 'Technické Detaily:' : 'Technical Details:'; ?></strong> <?php echo htmlspecialchars($technical_data); ?></p>
                            <p><strong><?php echo $lang === 'cs' ? 'Majitel:' : 'Owner:'; ?></strong> <?php echo htmlspecialchars($tool['ownerID']); ?></p>
                        </div>
                        <div>
                            <a href="tool_availability.php?tool_id=<?php echo $tool['tool_id']; ?>&lang=<?php echo $lang; ?>" class="availability-button">
                                <?php echo $lang === 'cs' ? 'Zkontrolovat Dostupnost' : 'Check Availability'; ?>
                            </a>
                        </div>
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
