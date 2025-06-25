<?php
// Start session at the very beginning of the file
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($name); ?> - <?php echo $lang === 'cs' ? 'Dostupnost' : 'Availability'; ?>
    </title>
    <link rel="stylesheet" href="styles.css" />
    <link rel="icon" href="/favicon-dark.ico" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Libre+Baskerville:ital,wght@0,400;0,700;1,400&family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap"
        rel="stylesheet">
    <script src="script.js" defer></script>
</head>

<body>
    <div class="container">
        <header>
            <div class="banner">
                <img alt="Company Logo" src="/tools4friends_dark_Banner_2000x400.png" />
            </div>
        </header>
        <div class="line-break"></div>

        <?php
        // Include the navbar file here
        // Make sure navbar.php is in the same directory or provide the correct path
        include 'navbar.php';
        ?>

        <main>
            <h1><?php echo htmlspecialchars($name); ?></h1>
            <div class="tool-details">
                <p><strong><?php echo $lang === 'cs' ? 'Popis:' : 'Description:'; ?></strong>
                    <?php echo htmlspecialchars($description); ?></p>
                <p><strong><?php echo $lang === 'cs' ? 'Značka:' : 'Brand:'; ?></strong>
                    <?php echo htmlspecialchars($tool['brand']); ?></p>
                <p><strong><?php echo $lang === 'cs' ? 'Model:' : 'Model:'; ?></strong>
                    <?php echo htmlspecialchars($tool['model']); ?></p>
                <?php if (!empty($technical_data)): ?>
                    <p><strong><?php echo $lang === 'cs' ? 'Technické Detaily:' : 'Technical Details:'; ?></strong>
                        <?php echo htmlspecialchars($technical_data); ?></p>
                <?php endif; ?>
                <?php if (!empty($tool['ownerID'])): ?>
                    <p><strong><?php echo $lang === 'cs' ? 'Majitel:' : 'Owner:'; ?></strong>
                        <?php echo htmlspecialchars($tool['ownerID']); ?></p>
                <?php endif; ?>
            </div>

            <h2><?php echo $lang === 'cs' ? 'Kalendář Dostupnosti' : 'Availability Calendar'; ?></h2>
            <div class="calendar-container">
                <div class="calendar-nav">
                    <button onclick="changeMonth(-1)">←
                        <?php echo $lang === 'cs' ? 'Předchozí' : 'Previous'; ?></button>
                    <div class="calendar-controls">
                        <h3 id="calendar-month"></h3>
                        <button onclick="goToToday()"
                            class="today-btn"><?php echo $lang === 'cs' ? 'Dnes' : 'Today'; ?></button>
                    </div>
                    <button onclick="changeMonth(1)"><?php echo $lang === 'cs' ? 'Další' : 'Next'; ?> →</button>
                </div>
                <div class="calendar" id="calendar"></div>
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
                    <div class="legend-item">
                        <div class="legend-color selected"></div>
                        <span><?php echo $lang === 'cs' ? 'Vybráno' : 'Selected'; ?></span>
                    </div>
                </div>
            </div>
        </main>
        <footer>
            <p>&copy; <span id="year"></span> Tools4Friends</p>
        </footer>
    </div>

    <script>
        // Initialize calendar when page loads
        document.addEventListener('DOMContentLoaded', function () {
            const unavailableRanges = <?php echo json_encode($unavailable_ranges); ?>;
            initializeCalendar(unavailableRanges);
        });
    </script>
</body>

</html>
