<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../app/security.php';
require_once __DIR__ . '/../app/db_connect.php';

startSecureSession();

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?lang=" . ($_GET['lang'] ?? 'en'));
    exit();
}

$lang = $_GET['lang'] ?? 'en';
$user_id = $_SESSION['user_id'];
$current_date = date('Y-m-d');

// Fetch all reservations (availability records) for the current user
$query = $conn->prepare("
    SELECT 
        a.availability_id,
        a.tool_id,
        a.start_date,
        a.end_date,
        a.status,
        a.created_at,
        t.name,
        t.name_cs,
        t.picture,
        t.ownerID,
        u.firstname AS owner_firstname,
        u.lastname AS owner_lastname
    FROM Availability a
    JOIN Tools t ON a.tool_id = t.tool_id
    JOIN Users u ON t.ownerID = u.ownerID
    WHERE a.user_id = ?
    ORDER BY a.start_date DESC
");
$query->bind_param("i", $user_id);
$query->execute();
$result = $query->get_result();

// Categorize reservations
$current_availabilities = [];
$planned_availabilities = [];
$historical_availabilities = [];

while ($avail = $result->fetch_assoc()) {
    if ($avail['end_date'] < $current_date) {
        $historical_availabilities[] = $avail;
    } elseif ($avail['start_date'] > $current_date) {
        $planned_availabilities[] = $avail;
    } else {
        $current_availabilities[] = $avail;
    }
}

// Navbar variables
$loggedIn = true;
$fullName = htmlspecialchars($_SESSION['firstname'] . ' ' . $_SESSION['lastname']);
?>

<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo ($lang === 'cs' ? 'Moje objednávky' : 'My Orders'); ?> - Tools4Friends</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="icon" href="favicon/favicon-dark.ico" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Libre+Baskerville:ital,wght@0,400;0,700;1,400&family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <script src="script.js" defer></script>
    <style>
        /* Add custom styles for the orders page */
        .orders-section {
            margin-bottom: 40px;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .orders-section h2 {
            color: #1F2D5A;
            border-bottom: 2px solid #4a90e2;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        
        .order-card {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
            padding: 15px;
            background: #f9f9f9;
            border-radius: 8px;
            border-left: 4px solid #4a90e2;
        }
        
        .order-image {
            width: 120px;
            height: 120px;
            object-fit: cover;
            border-radius: 4px;
        }
        
        .order-details {
            flex: 1;
        }
        
        .order-details h3 {
            margin-top: 0;
            color: #1F2D5A;
        }
        
        .status-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 0.8em;
            font-weight: bold;
        }
        
        .status-active {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-planned {
            background-color: #cce5ff;
            color: #004085;
        }
        
        .status-completed {
            background-color: #e2e3e5;
            color: #383d41;
        }
    </style>
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
            <div style="margin-bottom: 20px;">
                <a href="myprofile.php?lang=<?php echo $lang; ?>" class="btn btn-blue">
                    <?php echo $lang === 'cs' ? '← Zpět na Můj Profil' : '← Back to My Profile'; ?>
                </a>
            </div>
            <h1><?php echo $lang === 'cs' ? 'Moje objednávky' : 'My Orders'; ?></h1>
            <div class="line-break"></div>

            <!-- Current Reservations Section -->
            <section class="orders-section">
                <h2><?php echo $lang === 'cs' ? 'Aktivní výpůjčky' : 'Active Rentals'; ?></h2>
                <?php if (empty($current_availabilities)): ?>
                    <p><?php echo $lang === 'cs' ? 'Nemáte žádné aktivní výpůjčky.' : 'You have no active rentals.'; ?></p>
                <?php else: ?>
                    <?php foreach ($current_availabilities as $avail): 
                        $tool_name = $lang === 'cs' && !empty($avail['name_cs']) ? $avail['name_cs'] : $avail['name'];
                    ?>
                        <div class="order-card">
                            <img src="<?php echo htmlspecialchars($avail['picture']); ?>" alt="<?php echo htmlspecialchars($tool_name); ?>" class="order-image">
                            <div class="order-details">
                                <h3><?php echo htmlspecialchars($tool_name); ?></h3>
                                <p><strong><?php echo $lang === 'cs' ? 'Vlastník:' : 'Owner:'; ?></strong> <?php echo htmlspecialchars($avail['owner_firstname'] . ' ' . $avail['owner_lastname']); ?></p>
                                <p><strong><?php echo $lang === 'cs' ? 'Období výpůjčky:' : 'Rental period:'; ?></strong> 
                                    <?php echo date('d.m.Y', strtotime($avail['start_date'])) . ' - ' . date('d.m.Y', strtotime($avail['end_date'])); ?>
                                </p>
                                <p><strong><?php echo $lang === 'cs' ? 'Zbývající dny:' : 'Days remaining:'; ?></strong> 
                                    <?php echo (strtotime($avail['end_date']) - strtotime($current_date)) / (60 * 60 * 24); ?>
                                </p>
                                <span class="status-badge status-active"><?php echo $lang === 'cs' ? 'Aktivní' : 'Active'; ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </section>

            <!-- Planned Reservations Section -->
            <section class="orders-section">
                <h2><?php echo $lang === 'cs' ? 'Plánované výpůjčky' : 'Upcoming Rentals'; ?></h2>
                <?php if (empty($planned_availabilities)): ?>
                    <p><?php echo $lang === 'cs' ? 'Nemáte žádné plánované výpůjčky.' : 'You have no upcoming rentals.'; ?></p>
                <?php else: ?>
                    <?php foreach ($planned_availabilities as $avail): 
                        $tool_name = $lang === 'cs' && !empty($avail['name_cs']) ? $avail['name_cs'] : $avail['name'];
                    ?>
                        <div class="order-card">
                            <img src="<?php echo htmlspecialchars($avail['picture']); ?>" alt="<?php echo htmlspecialchars($tool_name); ?>" class="order-image">
                            <div class="order-details">
                                <h3><?php echo htmlspecialchars($tool_name); ?></h3>
                                <p><strong><?php echo $lang === 'cs' ? 'Vlastník:' : 'Owner:'; ?></strong> <?php echo htmlspecialchars($avail['owner_firstname'] . ' ' . $avail['owner_lastname']); ?></p>
                                <p><strong><?php echo $lang === 'cs' ? 'Období výpůjčky:' : 'Rental period:'; ?></strong> 
                                    <?php echo date('d.m.Y', strtotime($avail['start_date'])) . ' - ' . date('d.m.Y', strtotime($avail['end_date'])); ?>
                                </p>
                                <p><strong><?php echo $lang === 'cs' ? 'Dny do začátku:' : 'Days until start:'; ?></strong> 
                                    <?php echo (strtotime($avail['start_date']) - strtotime($current_date)) / (60 * 60 * 24); ?>
                                </p>
                                <span class="status-badge status-planned"><?php echo $lang === 'cs' ? 'Plánované' : 'Planned'; ?></span>
                                <div style="margin-top: 10px;">
                                    <button class="btn btn-blue" onclick="cancelReservation(<?php echo $avail['availability_id']; ?>)">
                                        <?php echo $lang === 'cs' ? 'Zrušit rezervaci' : 'Cancel reservation'; ?>
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </section>

            <!-- Historical Reservations Section -->
            <section class="orders-section">
                <h2><?php echo $lang === 'cs' ? 'Historie výpůjček' : 'Rental History'; ?></h2>
                <?php if (empty($historical_availabilities)): ?>
                    <p><?php echo $lang === 'cs' ? 'Nemáte žádnou historii výpůjček.' : 'You have no rental history.'; ?></p>
                <?php else: ?>
                    <?php foreach ($historical_availabilities as $avail): 
                        $tool_name = $lang === 'cs' && !empty($avail['name_cs']) ? $avail['name_cs'] : $avail['name'];
                    ?>
                        <div class="order-card">
                            <img src="<?php echo htmlspecialchars($avail['picture']); ?>" alt="<?php echo htmlspecialchars($tool_name); ?>" class="order-image">
                            <div class="order-details">
                                <h3><?php echo htmlspecialchars($tool_name); ?></h3>
                                <p><strong><?php echo $lang === 'cs' ? 'Vlastník:' : 'Owner:'; ?></strong> <?php echo htmlspecialchars($avail['owner_firstname'] . ' ' . $avail['owner_lastname']); ?></p>
                                <p><strong><?php echo $lang === 'cs' ? 'Období výpůjčky:' : 'Rental period:'; ?></strong> 
                                    <?php echo date('d.m.Y', strtotime($avail['start_date'])) . ' - ' . date('d.m.Y', strtotime($avail['end_date'])); ?>
                                </p>
                                <p><strong><?php echo $lang === 'cs' ? 'Dny od vrácení:' : 'Days since return:'; ?></strong> 
                                    <?php echo (strtotime($current_date) - strtotime($avail['end_date'])) / (60 * 60 * 24); ?>
                                </p>
                                <span class="status-badge status-completed"><?php echo $lang === 'cs' ? 'Dokončeno' : 'Completed'; ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </section>
        </main>

        <footer>
            <p>&copy; <span id="year"></span> Tools4Friends</p>
        </footer>
    </div>
    <script>
        function cancelReservation(availabilityId) {
            if (confirm('<?php echo $lang === 'cs' ? 'Opravdu chcete zrušit tuto rezervaci?' : 'Are you sure you want to cancel this reservation?'; ?>')) {
                fetch('cancel_reservation.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `availability_id=${availabilityId}&lang=<?php echo $lang; ?>`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        location.reload();
                    } else {
                        alert(data.message);
                    }
                })
                .catch(error => {
                    alert('<?php echo $lang === 'cs' ? 'Chyba při komunikaci se serverem' : 'Error communicating with server'; ?>');
                });
            }
        }

        // Display current year in footer
        document.getElementById('year').textContent = new Date().getFullYear();
    </script>
</body>
</html>
