<?php
require_once __DIR__ . '/../app/security.php';
require_once __DIR__ . '/../app/db_connect.php';

startSecureSession();

// Get tool ID and language
$tool_id = isset($_GET['tool_id']) ? intval($_GET['tool_id']) : 0;
$lang = $_GET['lang'] ?? 'en';

// Handle cart operations
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    if ($_POST['action'] === 'add_to_cart') {
        // Validate CSRF token
        if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
            echo json_encode(['success' => false, 'message' => 'Security validation failed.']);
            logSecurityEvent('CSRF validation failed on add to cart', [
                'ip' => $_SERVER['REMOTE_ADDR'],
                'tool_id' => $tool_id
            ]);
            exit;
        }
        
        $start_date = $_POST['start_date'] ?? '';
        $end_date = $_POST['end_date'] ?? '';
        
        // Validate dates
        if (empty($start_date) || empty($end_date)) {
            echo json_encode(['success' => false, 'message' => $lang === 'cs' ? 'Vyberte prosím datum začátku a konce.' : 'Please select start and end dates.']);
            exit;
        }
        
        // Check if dates are valid
        $start = DateTime::createFromFormat('Y-m-d', $start_date);
        $end = DateTime::createFromFormat('Y-m-d', $end_date);
        
        if (!$start || !$end || $start > $end) {
            echo json_encode(['success' => false, 'message' => $lang === 'cs' ? 'Neplatné datum.' : 'Invalid dates.']);
            exit;
        }
        
        // Check if tool is available for the selected period
        $check_stmt = $conn->prepare("SELECT COUNT(*) as conflicts FROM Availability WHERE tool_id = ? AND ((start_date <= ? AND end_date >= ?) OR (start_date <= ? AND end_date >= ?) OR (start_date >= ? AND end_date <= ?))");
        $check_stmt->bind_param("issssss", $tool_id, $start_date, $start_date, $end_date, $end_date, $start_date, $end_date);
        $check_stmt->execute();
        $conflict_result = $check_stmt->get_result();
        $conflicts = $conflict_result->fetch_assoc()['conflicts'];
        
        if ($conflicts > 0) {
            echo json_encode(['success' => false, 'message' => $lang === 'cs' ? 'Nářadí není dostupné v tomto období.' : 'Tool is not available for this period.']);
            exit;
        }
        
        // Initialize cart if it doesn't exist
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }
        
        // Check if this tool is already in cart for overlapping dates
        $exists = false;
        
        foreach ($_SESSION['cart'] as $item) {
            if ($item['tool_id'] == $tool_id) {
                // Check for date overlap
                $existing_start = new DateTime($item['start_date']);
                $existing_end = new DateTime($item['end_date']);
                
                if (($start <= $existing_end && $end >= $existing_start)) {
                    $exists = true;
                    break;
                }
            }
        }
        
        if ($exists) {
            echo json_encode(['success' => false, 'message' => $lang === 'cs' ? 'Toto nářadí je již v košíku pro překrývající se období.' : 'This tool is already in cart for overlapping dates.']);
            exit;
        }
        
        // Add to cart
        $_SESSION['cart'][] = [
            'tool_id' => $tool_id,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'added_at' => date('Y-m-d H:i:s')
        ];
        
        logSecurityEvent('Tool added to cart', [
            'user_id' => $_SESSION['user_id'] ?? 'guest',
            'tool_id' => $tool_id,
            'start_date' => $start_date,
            'end_date' => $end_date
        ]);
        
        echo json_encode(['success' => true, 'message' => $lang === 'cs' ? 'Přidáno do košíku!' : 'Added to cart!', 'cart_count' => count($_SESSION['cart'])]);
        exit;
    }
}

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

// User Login Status for Navbar
$loggedIn = isset($_SESSION['user_id']);
$fullName = '';
if ($loggedIn) {
    $fullName = sanitizeOutput($_SESSION['firstname'] . ' ' . $_SESSION['lastname']);
}

// Get cart count for display
$cart_count = isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0;

// Generate CSRF token
$csrf_token = generateCSRFToken();
?>

<!DOCTYPE html>
<html lang="<?php echo sanitizeOutput($lang); ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo sanitizeOutput($name); ?> - <?php echo $lang === 'cs' ? 'Dostupnost' : 'Availability'; ?></title>
    <link rel="stylesheet" href="styles.css" />
    <link rel="icon" href="favicon/favicon-dark.ico" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Libre+Baskerville:ital,wght@0,400;0,700;1,400&family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <script src="script.js" defer></script>
</head>

<body>
    <?php include __DIR__ . '/../app/cart_icon.php'; ?>

    <div class="container">
        <header>
            <div class="banner">
                <img alt="Company Logo" src="images/banners/tools4friends_dark_Banner_2000x400.png" />
            </div>
        </header>
        <div class="line-break"></div>

        <?php include __DIR__ . '/../app/navbar.php'; ?>

        <main>
            <h1><?php echo sanitizeOutput($name); ?></h1>
            <div class="tool-details">
                <p><strong><?php echo $lang === 'cs' ? 'Popis:' : 'Description:'; ?></strong>
                    <?php echo sanitizeOutput($description); ?></p>
                <p><strong><?php echo $lang === 'cs' ? 'Značka:' : 'Brand:'; ?></strong>
                    <?php echo sanitizeOutput($tool['brand']); ?></p>
                <p><strong><?php echo $lang === 'cs' ? 'Model:' : 'Model:'; ?></strong>
                    <?php echo sanitizeOutput($tool['model']); ?></p>
                <?php if (!empty($technical_data)): ?>
                    <p><strong><?php echo $lang === 'cs' ? 'Technické Detaily:' : 'Technical Details:'; ?></strong>
                        <?php echo sanitizeOutput($technical_data); ?></p>
                <?php endif; ?>
                <?php if (!empty($tool['ownerID'])): ?>
                    <p><strong><?php echo $lang === 'cs' ? 'Majitel:' : 'Owner:'; ?></strong>
                        <?php echo sanitizeOutput($tool['ownerID']); ?></p>
                <?php endif; ?>
            </div>

            <h2><?php echo $lang === 'cs' ? 'Kalendář Dostupnosti' : 'Availability Calendar'; ?></h2>
            
            <div class="calendar-instructions">
                <p><strong><?php echo $lang === 'cs' ? '📅 Jak vybrat období:' : '📅 How to select a date range:'; ?></strong></p>
                <p><?php echo $lang === 'cs' ? '1. Klikněte na datum začátku' : '1. Click on the start date'; ?></p>
                <p><?php echo $lang === 'cs' ? '2. Klikněte na datum konce (musí být po datu začátku)' : '2. Click on the end date (must be after start date)'; ?></p>
                <p><?php echo $lang === 'cs' ? '3. Vybrané období se zvýrazní modře' : '3. Selected range will be highlighted in blue'; ?></p>
                <p><?php echo $lang === 'cs' ? '4. Klikněte na "Přidat do košíku" pro rezervaci' : '4. Click "Add to Cart" to reserve'; ?></p>
            </div>
            
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
                        <span><?php echo $lang === 'cs' ? 'Vybrané období' : 'Selected Range'; ?></span>
                    </div>
                </div>
            </div>

            <div class="booking-section">
                <div id="date-selection-info" class="date-selection-info">
                    <h3><?php echo $lang === 'cs' ? '📅 Vybrané období:' : '📅 Selected Period:'; ?></h3>
                    <p id="selected-dates-text"></p>
                </div>
                
                <div id="booking-alert" class="alert"></div>
                
                <form id="booking-form" class="booking-form">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <input type="hidden" id="start-date" name="start_date">
                    <input type="hidden" id="end-date" name="end_date">
                    <input type="hidden" name="action" value="add_to_cart">
                    <button type="submit" id="add-to-cart-btn" disabled>
                        <?php echo $lang === 'cs' ? '🛒 Přidat do košíku' : '🛒 Add to Cart'; ?>
                    </button>
                </form>
            </div>
        </main>
        <footer>
            <p>&copy; <span id="year"></span> Tools4Friends</p>
        </footer>
    </div>

    <script>
        // Initialize tool availability page functionality
        document.addEventListener('DOMContentLoaded', function() {
            const toolId = <?php echo $tool_id; ?>;
            const lang = '<?php echo $lang; ?>';
            const unavailableRanges = <?php echo json_encode($unavailable_ranges); ?>;
            const csrfToken = '<?php echo $csrf_token; ?>';
            
            initializeCalendar(unavailableRanges);
            initializeToolAvailabilityPage(toolId, lang, unavailableRanges, csrfToken);
        });
    </script>
</body>

</html>
