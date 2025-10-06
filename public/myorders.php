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

// Fetch all orders and their items for the current user
$query = $conn->prepare("
    SELECT 
        a.availability_id,
        a.tool_id,
        a.start_date,
        a.end_date,
        a.status,
        a.order_id,
        a.created_at,
        t.name,
        t.name_cs,
        t.picture,
        t.ownerID,
        u.firstname AS owner_firstname,
        u.lastname AS owner_lastname,
        o.order_date,
        o.denial_reason,
        o.invoice_number
    FROM Availability a
    JOIN Tools t ON a.tool_id = t.tool_id
    JOIN Users u ON t.ownerID = u.ownerID
    LEFT JOIN Orders o ON a.order_id = o.order_id
    WHERE a.user_id = ?
    ORDER BY a.created_at DESC
");
$query->bind_param("i", $user_id);
$query->execute();
$result = $query->get_result();

// Categorize reservations by status
$pending_availabilities = [];
$approved_availabilities = [];
$denied_availabilities = [];
$cancelled_availabilities = [];
$current_availabilities = [];
$historical_availabilities = [];

while ($avail = $result->fetch_assoc()) {
    if ($avail['status'] === 'pending') {
        $pending_availabilities[] = $avail;
    } elseif ($avail['status'] === 'denied') {
        $denied_availabilities[] = $avail;
    } elseif ($avail['status'] === 'cancelled') {
        $cancelled_availabilities[] = $avail;
    } elseif ($avail['status'] === 'approved') {
        // Check if it's current or future
        if ($avail['start_date'] > $current_date) {
            $approved_availabilities[] = $avail;
        } elseif ($avail['end_date'] >= $current_date) {
            $current_availabilities[] = $avail;
        } else {
            $historical_availabilities[] = $avail;
        }
    } elseif ($avail['end_date'] < $current_date) {
        $historical_availabilities[] = $avail;
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
            <div class="mb-20">
                <a href="myprofile.php?lang=<?php echo $lang; ?>" class="btn btn-blue">
                    <?php echo $lang === 'cs' ? '← Zpět na Můj Profil' : '← Back to My Profile'; ?>
                </a>
            </div>
            <h1><?php echo $lang === 'cs' ? 'Moje objednávky' : 'My Orders'; ?></h1>
            <div class="line-break"></div>

            <!-- Pending Orders Section -->
            <section class="orders-section">
                <h2><?php echo $lang === 'cs' ? '⏳ Čekající na schválení' : '⏳ Waiting for Approval'; ?></h2>
                <?php if (empty($pending_availabilities)): ?>
                    <p><?php echo $lang === 'cs' ? 'Nemáte žádné čekající objednávky.' : 'You have no pending orders.'; ?></p>
                <?php else: ?>
                    <?php foreach ($pending_availabilities as $avail): 
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
                                <p><strong><?php echo $lang === 'cs' ? 'Objednáno:' : 'Ordered:'; ?></strong> 
                                    <?php echo date('d.m.Y H:i', strtotime($avail['order_date'])); ?>
                                </p>
                                <span class="status-badge status-planned"><?php echo $lang === 'cs' ? 'Čeká na schválení' : 'Pending Approval'; ?></span>
                                <p class="order-pending-note">
                                    <?php echo $lang === 'cs' ? 'Vaše objednávka čeká na schválení administrátorem. Obdržíte email s fakturou po schválení.' : 'Your order is waiting for admin approval. You will receive an email with invoice once approved.'; ?>
                                </p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </section>

            <!-- Approved (Upcoming) Reservations Section -->
            <section class="orders-section">
                <h2><?php echo $lang === 'cs' ? '✓ Schválené výpůjčky' : '✓ Approved Rentals'; ?></h2>
                <?php if (empty($approved_availabilities)): ?>
                    <p><?php echo $lang === 'cs' ? 'Nemáte žádné schválené budoucí výpůjčky.' : 'You have no approved upcoming rentals.'; ?></p>
                <?php else: ?>
                    <?php foreach ($approved_availabilities as $avail): 
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
                                    <?php 
                                    $days_until_start = round((strtotime($avail['start_date']) - strtotime($current_date)) / (60 * 60 * 24));
                                    echo $days_until_start;
                                    ?>
                                </p>
                                <?php if ($avail['invoice_number']): ?>
                                    <p><strong><?php echo $lang === 'cs' ? 'Faktura:' : 'Invoice:'; ?></strong> 
                                        <?php echo htmlspecialchars($avail['invoice_number']); ?>
                                    </p>
                                <?php endif; ?>
                                <span class="status-badge status-active"><?php echo $lang === 'cs' ? 'Schváleno' : 'Approved'; ?></span>
                                
                                <?php
                                // Check if cancellation is allowed (more than 24 hours before start)
                                $hours_until_start = (strtotime($avail['start_date']) - time()) / 3600;
                                $can_cancel = $hours_until_start > 24;
                                ?>
                                
                                <div class="mt-15">
                                    <?php if ($can_cancel): ?>
                                        <button onclick="cancelOrder(<?php echo $avail['availability_id']; ?>, '<?php echo $lang; ?>', this)" class="btn btn-cancel">
                                            <?php echo $lang === 'cs' ? '✗ Zrušit objednávku' : '✗ Cancel Order'; ?>
                                        </button>
                                    <?php else: ?>
                                        <p class="cancellation-notice">
                                            <?php echo $lang === 'cs' 
                                                ? '⚠️ Zrušení není možné (méně než 24 hodin do začátku)' 
                                                : '⚠️ Cancellation not allowed (less than 24 hours until start)'; ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </section>

            <!-- Current (Active) Reservations Section -->
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
                                    <?php echo round((strtotime($avail['end_date']) - strtotime($current_date)) / (60 * 60 * 24)); ?>
                                </p>
                                <span class="status-badge status-active"><?php echo $lang === 'cs' ? 'Aktivní' : 'Active'; ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </section>

            <!-- Denied Orders Section -->
            <section class="orders-section">
                <h2><?php echo $lang === 'cs' ? '✗ Zamítnuté objednávky' : '✗ Denied Orders'; ?></h2>
                <?php if (empty($denied_availabilities)): ?>
                    <p><?php echo $lang === 'cs' ? 'Nemáte žádné zamítnuté objednávky.' : 'You have no denied orders.'; ?></p>
                <?php else: ?>
                    <?php foreach ($denied_availabilities as $avail): 
                        $tool_name = $lang === 'cs' && !empty($avail['name_cs']) ? $avail['name_cs'] : $avail['name'];
                    ?>
                        <div class="order-card">
                            <img src="<?php echo htmlspecialchars($avail['picture']); ?>" alt="<?php echo htmlspecialchars($tool_name); ?>" class="order-image">
                            <div class="order-details">
                                <h3><?php echo htmlspecialchars($tool_name); ?></h3>
                                <p><strong><?php echo $lang === 'cs' ? 'Vlastník:' : 'Owner:'; ?></strong> <?php echo htmlspecialchars($avail['owner_firstname'] . ' ' . $avail['owner_lastname']); ?></p>
                                <p><strong><?php echo $lang === 'cs' ? 'Požadované období:' : 'Requested period:'; ?></strong> 
                                    <?php echo date('d.m.Y', strtotime($avail['start_date'])) . ' - ' . date('d.m.Y', strtotime($avail['end_date'])); ?>
                                </p>
                                <span class="status-badge status-completed"><?php echo $lang === 'cs' ? 'Zamítnuto' : 'Denied'; ?></span>
                                <?php if ($avail['denial_reason']): ?>
                                    <div class="denial-reason-box">
                                        <strong><?php echo $lang === 'cs' ? 'Důvod zamítnutí:' : 'Denial Reason:'; ?></strong>
                                        <p><?php echo htmlspecialchars($avail['denial_reason']); ?></p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </section>

            <!-- Cancelled Orders Section -->
            <section class="orders-section">
                <h2><?php echo $lang === 'cs' ? '✗ Zrušené objednávky' : '✗ Cancelled Orders'; ?></h2>
                <?php if (empty($cancelled_availabilities)): ?>
                    <p><?php echo $lang === 'cs' ? 'Nemáte žádné zrušené objednávky.' : 'You have no cancelled orders.'; ?></p>
                <?php else: ?>
                    <?php foreach ($cancelled_availabilities as $avail): 
                        $tool_name = $lang === 'cs' && !empty($avail['name_cs']) ? $avail['name_cs'] : $avail['name'];
                    ?>
                        <div class="order-card">
                            <img src="<?php echo htmlspecialchars($avail['picture']); ?>" alt="<?php echo htmlspecialchars($tool_name); ?>" class="order-image">
                            <div class="order-details">
                                <h3><?php echo htmlspecialchars($tool_name); ?></h3>
                                <p><strong><?php echo $lang === 'cs' ? 'Vlastník:' : 'Owner:'; ?></strong> <?php echo htmlspecialchars($avail['owner_firstname'] . ' ' . $avail['owner_lastname']); ?></p>
                                <p><strong><?php echo $lang === 'cs' ? 'Požadované období:' : 'Requested period:'; ?></strong> 
                                    <?php echo date('d.m.Y', strtotime($avail['start_date'])) . ' - ' . date('d.m.Y', strtotime($avail['end_date'])); ?>
                                </p>
                                <p><strong><?php echo $lang === 'cs' ? 'Zrušeno:' : 'Cancelled:'; ?></strong> 
                                    <?php echo date('d.m.Y H:i', strtotime($avail['created_at'])); ?>
                                </p>
                                <span class="status-badge status-cancelled"><?php echo $lang === 'cs' ? 'Zrušeno' : 'Cancelled'; ?></span>
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
                                    <?php echo round((strtotime($current_date) - strtotime($avail['end_date'])) / (60 * 60 * 24)); ?>
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
</body>
</html>
