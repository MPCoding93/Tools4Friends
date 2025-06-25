<?php
// tool_availability.php

// Include database connection
include 'db_connect.php';

// Get tool ID and language
$tool_id = isset($_GET['tool_id']) ? intval($_GET['tool_id']) : 0;
$lang = $_GET['lang'] ?? 'en';

// Fetch tool details
$stmt = $conn->prepare("SELECT * FROM Tools WHERE tool_id = ?");
$stmt->bind_param("i", $tool_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "<h2>" . ($lang === 'cs' ? 'Nářadí nenalezeno.' : 'Tool not found.') . "</h2>";
    exit;
}
$tool = $result->fetch_assoc();

// Fetch availability ranges using MySQLi
$availability_stmt = $conn->prepare("SELECT start_date, end_date FROM Availability WHERE tool_id = ?");
$availability_stmt->bind_param("i", $tool_id);
$availability_stmt->execute();
$availability_result = $availability_stmt->get_result();
$unavailable_ranges = [];

while ($row = $availability_result->fetch_assoc()) {
    $unavailable_ranges[] = $row;
}

// Get localized tool data
$name = $lang === 'cs' && !empty($tool['name_cs']) ? $tool['name_cs'] : $tool['name'];
$description = $lang === 'cs' && !empty($tool['description_cs']) ? $tool['description_cs'] : $tool['description'];
$technical_data = $lang === 'cs' && !empty($tool['technical_data_cs']) ? $tool['technical_data_cs'] : $tool['technical_data'];
?>

<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($name); ?> - 
           <?php echo $lang === 'cs' ? 'Dostupnost' : 'Availability'; ?> - Tools4Friends</title>
    <link rel="stylesheet" href="styles.css" />
    <link rel="icon" href="/favicon-dark.ico" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Libre+Baskerville:ital,wght@0,400;0,700;1,400&family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap"
        rel="stylesheet">
</head>

<body>
    <div class="container">
        <header>
            <div class="banner">
                <img alt="Company Logo" src="/tools4friends_dark_Banner_2000x400.png" />
            </div>
        </header>
        <div class="line-break"></div>
        <nav>
            <div class="nav-left">
                <a href="index.html?lang=<?php echo $lang; ?>">
                    <?php echo $lang === 'cs' ? 'Domů' : 'Home'; ?>
                </a>
                <a href="tools.php?lang=<?php echo $lang; ?>">
                    <?php echo $lang === 'cs' ? 'Nářadí' : 'Tools'; ?>
                </a>
                <a href="contacts.html?lang=<?php echo $lang; ?>">
                    <?php echo $lang === 'cs' ? 'Kontakty' : 'Contacts'; ?>
                </a>
            </div>
            <div class="nav-right language-toggle">
                <button onclick="switchLanguage('en')" class="<?php echo $lang === 'en' ? 'active' : ''; ?>">English</button>
                <button onclick="switchLanguage('cs')" class="<?php echo $lang === 'cs' ? 'active' : ''; ?>">Čeština</button>
            </div>
        </nav>
        <main>
            <div class="breadcrumb">
                <a href="tools.php?lang=<?php echo $lang; ?>">
                    <?php echo $lang === 'cs' ? 'Nářadí' : 'Tools'; ?>
                </a>
                <span class="breadcrumb-separator">/</span>
                <span><?php echo htmlspecialchars($name); ?></span>
            </div>

            <h1><?php echo htmlspecialchars($name); ?></h1>
            
            <div class="tool-details">
                <div class="tool-info-grid">
                    <div class="tool-image">
                        <img src="<?php echo htmlspecialchars($tool['picture']); ?>" 
                             alt="<?php echo htmlspecialchars($name); ?>"
                             onerror="this.src='/images/tool-placeholder.png'">
                    </div>
                    <div class="tool-specs">
                        <h3><?php echo $lang === 'cs' ? 'Specifikace' : 'Specifications'; ?></h3>
                        <div class="spec-item">
                            <strong><?php echo $lang === 'cs' ? 'Popis:' : 'Description:'; ?></strong>
                            <span><?php echo htmlspecialchars($description); ?></span>
                        </div>
                        <div class="spec-item">
                            <strong><?php echo $lang === 'cs' ? 'Značka:' : 'Brand:'; ?></strong>
                            <span><?php echo htmlspecialchars($tool['brand']); ?></span>
                        </div>
                        <div class="spec-item">
                            <strong><?php echo $lang === 'cs' ? 'Model:' : 'Model:'; ?></strong>
                            <span><?php echo htmlspecialchars($tool['model']); ?></span>
                        </div>
                        <?php if (!empty($technical_data)): ?>
                        <div class="spec-item">
                            <strong><?php echo $lang === 'cs' ? 'Technické Detaily:' : 'Technical Details:'; ?></strong>
                            <span><?php echo htmlspecialchars($technical_data); ?></span>
                        </div>
                        <?php endif; ?>
                        <div class="spec-item">
                            <strong><?php echo $lang === 'cs' ? 'Majitel:' : 'Owner:'; ?></strong>
                            <span><?php echo htmlspecialchars($tool['ownerID']); ?></span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="availability-section">
                <h2><?php echo $lang === 'cs' ? 'Kalendář Dostupnosti' : 'Availability Calendar'; ?></h2>
                <div class="calendar-container">
                    <div class="calendar-controls">
                        <div class="month-navigation">
                            <button id="prev-month" class="nav-btn">
                                <span class="nav-arrow">←</span>
                            </button>
                            <h3 id="calendar-month" class="current-month"></h3>
                            <button id="next-month" class="nav-btn">
                                <span class="nav-arrow">→</span>
                            </button>
                        </div>
                        <div class="calendar-legend">
                            <div class="legend-item">
                                <div class="legend-color available"></div>
                                <span><?php echo $lang === 'cs' ? 'Dostupné' : 'Available'; ?></span>
                            </div>
                            <div class="legend-item">
                                <div class="legend-color unavailable"></div>
                                <span><?php echo $lang === 'cs' ? 'Nedostupné' : 'Unavailable'; ?></span>
                            </div>
                            <div class="legend-item">
                                <div class="legend-color today"></div>
                                <span><?php echo $lang === 'cs' ? 'Dnes' : 'Today'; ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="modern-calendar" id="calendar"></div>
                    <div class="calendar-info">
                        <p class="selected-date-info" id="selected-date-info">
                            <?php echo $lang === 'cs' ? 'Klikněte na datum pro více informací' : 'Click on a date for more information'; ?>
                        </p>
                    </div>
                </div>
            </div>
        </main>
        <footer>
            <p>&copy; <span id="year"></span> Tools4Friends</p>
        </footer>
    </div>

    <script>
        // Pass PHP data to JavaScript
        window.toolData = {
            unavailableRanges: <?php echo json_encode($unavailable_ranges); ?>,
            lang: '<?php echo $lang; ?>',
            toolId: <?php echo $tool_id; ?>
        };
    </script>
    <script src="script.js"></script>
</body>

</html>
