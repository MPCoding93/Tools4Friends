<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../app/security.php';
require_once __DIR__ . '/../app/db_connect.php';

startSecureSession();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['admin']) || $_SESSION['admin'] != 1) {
    header("Location: login.php?lang=" . ($_GET['lang'] ?? 'en'));
    exit();
}

$lang = $_GET['lang'] ?? 'en';
$user_id = $_SESSION['user_id'];

// Fetch all orders with user details
$pending_orders = [];
$approved_orders = [];
$denied_orders = [];

$query = $conn->prepare("
    SELECT 
        o.order_id,
        o.user_id,
        o.order_date,
        o.status,
        o.total_amount,
        o.total_deposit,
        o.invoice_number,
        o.denial_reason,
        o.approved_date,
        u.firstname,
        u.lastname,
        u.email,
        u.phone,
        COUNT(a.availability_id) as item_count
    FROM Orders o
    JOIN Users u ON o.user_id = u.user_id
    LEFT JOIN Availability a ON o.order_id = a.order_id
    GROUP BY o.order_id
    ORDER BY o.order_date DESC
");

$query->execute();
$result = $query->get_result();

while ($order = $result->fetch_assoc()) {
    if ($order['status'] === 'pending') {
        $pending_orders[] = $order;
    } elseif ($order['status'] === 'approved') {
        $approved_orders[] = $order;
    } elseif ($order['status'] === 'denied') {
        $denied_orders[] = $order;
    }
}

// Navbar variables
$loggedIn = true;
$fullName = htmlspecialchars($_SESSION['firstname'] . ' ' . $_SESSION['lastname']);

// Generate CSRF token
$csrf_token = generateCSRFToken();
?>

<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo ($lang === 'cs' ? 'Administrace Objednávek' : 'Order Administration'); ?> - Tools4Friends</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="icon" href="favicon/favicon-dark.ico" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Libre+Baskerville:ital,wght@0,400;0,700;1,400&family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <script src="script.js" defer></script>
    <style>
        .admin-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .admin-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            border-bottom: 2px solid #ddd;
        }
        
        .admin-tab {
            padding: 12px 24px;
            background: #f5f5f5;
            border: none;
            border-radius: 8px 8px 0 0;
            cursor: pointer;
            font-size: 1em;
            transition: all 0.3s;
        }
        
        .admin-tab.active {
            background: #1F2D5A;
            color: white;
        }
        
        .admin-tab:hover {
            background: #4a90e2;
            color: white;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .order-card {
            background: white;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-left: 4px solid #4a90e2;
        }
        
        .order-card.pending {
            border-left-color: #ffc107;
        }
        
        .order-card.approved {
            border-left-color: #28a745;
        }
        
        .order-card.denied {
            border-left-color: #dc3545;
        }
        
        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 15px;
        }
        
        .order-info {
            flex: 1;
        }
        
        .order-actions {
            display: flex;
            gap: 10px;
        }
        
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9em;
            transition: all 0.3s;
        }
        
        .btn-approve {
            background-color: #28a745;
            color: white;
        }
        
        .btn-approve:hover {
            background-color: #218838;
        }
        
        .btn-deny {
            background-color: #dc3545;
            color: white;
        }
        
        .btn-deny:hover {
            background-color: #c82333;
        }
        
        .btn-info {
            background-color: #17a2b8;
            color: white;
        }
        
        .btn-info:hover {
            background-color: #138496;
        }
        
        .btn-settings {
            background-color: #6c757d;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 4px;
        }
        
        .btn-settings:hover {
            background-color: #5a6268;
        }
        
        .order-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }
        
        .detail-item {
            display: flex;
            flex-direction: column;
        }
        
        .detail-label {
            font-size: 0.85em;
            color: #666;
            margin-bottom: 4px;
        }
        
        .detail-value {
            font-weight: 600;
            color: #1F2D5A;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 0.85em;
            font-weight: bold;
        }
        
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .status-approved {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-denied {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        
        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 30px;
            border-radius: 8px;
            width: 90%;
            max-width: 600px;
            max-height: 80vh;
            overflow-y: auto;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #ddd;
        }
        
        .modal-close {
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            color: #aaa;
        }
        
        .modal-close:hover {
            color: #000;
        }
        
        .denial-form textarea {
            width: 100%;
            min-height: 120px;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-family: inherit;
            resize: vertical;
        }
        
        .denial-form button {
            margin-top: 15px;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }
        
        .empty-state h3 {
            color: #1F2D5A;
            margin-bottom: 10px;
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

        <main class="admin-container">
            <div class="admin-header">
                <h1><?php echo $lang === 'cs' ? 'Administrace Objednávek' : 'Order Administration'; ?></h1>
                <a href="admin_settings.php?lang=<?php echo $lang; ?>" class="btn-settings">
                    <?php echo $lang === 'cs' ? '⚙️ Nastavení' : '⚙️ Settings'; ?>
                </a>
            </div>
            <div class="line-break"></div>

            <!-- Tabs -->
            <div class="admin-tabs">
                <button class="admin-tab active" onclick="switchTab('pending')">
                    <?php echo $lang === 'cs' ? 'Čekající' : 'Pending'; ?> (<?php echo count($pending_orders); ?>)
                </button>
                <button class="admin-tab" onclick="switchTab('approved')">
                    <?php echo $lang === 'cs' ? 'Schválené' : 'Approved'; ?> (<?php echo count($approved_orders); ?>)
                </button>
                <button class="admin-tab" onclick="switchTab('denied')">
                    <?php echo $lang === 'cs' ? 'Zamítnuté' : 'Denied'; ?> (<?php echo count($denied_orders); ?>)
                </button>
            </div>

            <!-- Pending Orders Tab -->
            <div id="pending-tab" class="tab-content active">
                <?php if (empty($pending_orders)): ?>
                    <div class="empty-state">
                        <h3><?php echo $lang === 'cs' ? 'Žádné čekající objednávky' : 'No Pending Orders'; ?></h3>
                        <p><?php echo $lang === 'cs' ? 'Všechny objednávky byly zpracovány' : 'All orders have been processed'; ?></p>
                    </div>
                <?php else: ?>
                    <?php foreach ($pending_orders as $order): ?>
                        <div class="order-card pending">
                            <div class="order-header">
                                <div class="order-info">
                                    <h3><?php echo $lang === 'cs' ? 'Objednávka' : 'Order'; ?> #<?php echo $order['order_id']; ?></h3>
                                    <span class="status-badge status-pending">
                                        <?php echo $lang === 'cs' ? 'Čeká na schválení' : 'Pending Approval'; ?>
                                    </span>
                                </div>
                                <div class="order-actions">
                                    <button class="btn btn-info" onclick="showOrderDetails(<?php echo $order['order_id']; ?>)">
                                        <?php echo $lang === 'cs' ? 'Detail' : 'More Info'; ?>
                                    </button>
                                    <button class="btn btn-approve" onclick="approveOrder(<?php echo $order['order_id']; ?>)">
                                        <?php echo $lang === 'cs' ? '✓ Schválit' : '✓ Approve'; ?>
                                    </button>
                                    <button class="btn btn-deny" onclick="showDenyModal(<?php echo $order['order_id']; ?>)">
                                        <?php echo $lang === 'cs' ? '✗ Zamítnout' : '✗ Deny'; ?>
                                    </button>
                                </div>
                            </div>
                            <div class="order-details">
                                <div class="detail-item">
                                    <span class="detail-label"><?php echo $lang === 'cs' ? 'Zákazník' : 'Customer'; ?></span>
                                    <span class="detail-value"><?php echo htmlspecialchars($order['firstname'] . ' ' . $order['lastname']); ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label"><?php echo $lang === 'cs' ? 'Email' : 'Email'; ?></span>
                                    <span class="detail-value"><?php echo htmlspecialchars($order['email']); ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label"><?php echo $lang === 'cs' ? 'Datum objednávky' : 'Order Date'; ?></span>
                                    <span class="detail-value"><?php echo date('d.m.Y H:i', strtotime($order['order_date'])); ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label"><?php echo $lang === 'cs' ? 'Počet nástrojů' : 'Items'; ?></span>
                                    <span class="detail-value"><?php echo $order['item_count']; ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label"><?php echo $lang === 'cs' ? 'Poplatek' : 'Fee'; ?></span>
                                    <span class="detail-value"><?php echo number_format($order['total_amount'], 2); ?> Kč</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label"><?php echo $lang === 'cs' ? 'Záloha' : 'Deposit'; ?></span>
                                    <span class="detail-value"><?php echo number_format($order['total_deposit'], 2); ?> Kč</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label"><?php echo $lang === 'cs' ? 'Celkem' : 'Total'; ?></span>
                                    <span class="detail-value"><?php echo number_format($order['total_amount'] + $order['total_deposit'], 2); ?> Kč</span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Approved Orders Tab -->
            <div id="approved-tab" class="tab-content">
                <?php if (empty($approved_orders)): ?>
                    <div class="empty-state">
                        <h3><?php echo $lang === 'cs' ? 'Žádné schválené objednávky' : 'No Approved Orders'; ?></h3>
                    </div>
                <?php else: ?>
                    <?php foreach ($approved_orders as $order): ?>
                        <div class="order-card approved">
                            <div class="order-header">
                                <div class="order-info">
                                    <h3><?php echo $lang === 'cs' ? 'Objednávka' : 'Order'; ?> #<?php echo $order['order_id']; ?></h3>
                                    <span class="status-badge status-approved">
                                        <?php echo $lang === 'cs' ? 'Schváleno' : 'Approved'; ?>
                                    </span>
                                </div>
                                <div class="order-actions">
                                    <button class="btn btn-info" onclick="showOrderDetails(<?php echo $order['order_id']; ?>)">
                                        <?php echo $lang === 'cs' ? 'Detail' : 'More Info'; ?>
                                    </button>
                                    <a href="generate_invoice.php?order_id=<?php echo $order['order_id']; ?>" class="btn btn-info" target="_blank">
                                        <?php echo $lang === 'cs' ? '📄 Faktura' : '📄 Invoice'; ?>
                                    </a>
                                </div>
                            </div>
                            <div class="order-details">
                                <div class="detail-item">
                                    <span class="detail-label"><?php echo $lang === 'cs' ? 'Zákazník' : 'Customer'; ?></span>
                                    <span class="detail-value"><?php echo htmlspecialchars($order['firstname'] . ' ' . $order['lastname']); ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label"><?php echo $lang === 'cs' ? 'Schváleno' : 'Approved'; ?></span>
                                    <span class="detail-value"><?php echo date('d.m.Y H:i', strtotime($order['approved_date'])); ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label"><?php echo $lang === 'cs' ? 'Faktura' : 'Invoice'; ?></span>
                                    <span class="detail-value"><?php echo htmlspecialchars($order['invoice_number']); ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label"><?php echo $lang === 'cs' ? 'Celkem' : 'Total'; ?></span>
                                    <span class="detail-value"><?php echo number_format($order['total_amount'] + $order['total_deposit'], 2); ?> Kč</span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Denied Orders Tab -->
            <div id="denied-tab" class="tab-content">
                <?php if (empty($denied_orders)): ?>
                    <div class="empty-state">
                        <h3><?php echo $lang === 'cs' ? 'Žádné zamítnuté objednávky' : 'No Denied Orders'; ?></h3>
                    </div>
                <?php else: ?>
                    <?php foreach ($denied_orders as $order): ?>
                        <div class="order-card denied">
                            <div class="order-header">
                                <div class="order-info">
                                    <h3><?php echo $lang === 'cs' ? 'Objednávka' : 'Order'; ?> #<?php echo $order['order_id']; ?></h3>
                                    <span class="status-badge status-denied">
                                        <?php echo $lang === 'cs' ? 'Zamítnuto' : 'Denied'; ?>
                                    </span>
                                </div>
                                <div class="order-actions">
                                    <button class="btn btn-info" onclick="showOrderDetails(<?php echo $order['order_id']; ?>)">
                                        <?php echo $lang === 'cs' ? 'Detail' : 'More Info'; ?>
                                    </button>
                                </div>
                            </div>
                            <div class="order-details">
                                <div class="detail-item">
                                    <span class="detail-label"><?php echo $lang === 'cs' ? 'Zákazník' : 'Customer'; ?></span>
                                    <span class="detail-value"><?php echo htmlspecialchars($order['firstname'] . ' ' . $order['lastname']); ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label"><?php echo $lang === 'cs' ? 'Zamítnuto' : 'Denied'; ?></span>
                                    <span class="detail-value"><?php echo date('d.m.Y H:i', strtotime($order['approved_date'])); ?></span>
                                </div>
                                <div class="detail-item" style="grid-column: 1 / -1;">
                                    <span class="detail-label"><?php echo $lang === 'cs' ? 'Důvod zamítnutí' : 'Denial Reason'; ?></span>
                                    <span class="detail-value"><?php echo htmlspecialchars($order['denial_reason']); ?></span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>

        <footer>
            <p>&copy; <span id="year"></span> Tools4Friends</p>
        </footer>
    </div>

    <!-- Denial Modal -->
    <div id="denyModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><?php echo $lang === 'cs' ? 'Zamítnout objednávku' : 'Deny Order'; ?></h2>
                <span class="modal-close" onclick="closeDenyModal()">&times;</span>
            </div>
            <form id="denyForm" class="denial-form" onsubmit="submitDenial(event)">
                <input type="hidden" id="deny_order_id" name="order_id">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <label for="denial_reason">
                    <strong><?php echo $lang === 'cs' ? 'Důvod zamítnutí (povinné):' : 'Reason for Denial (required):'; ?></strong>
                </label>
                <textarea id="denial_reason" name="denial_reason" required 
                          placeholder="<?php echo $lang === 'cs' ? 'Uveďte prosím důvod zamítnutí objednávky...' : 'Please provide a reason for denying this order...'; ?>"></textarea>
                <button type="submit" class="btn btn-deny" style="width: 100%;">
                    <?php echo $lang === 'cs' ? 'Potvrdit zamítnutí' : 'Confirm Denial'; ?>
                </button>
            </form>
        </div>
    </div>

    <!-- Order Details Modal -->
    <div id="detailsModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><?php echo $lang === 'cs' ? 'Detail objednávky' : 'Order Details'; ?></h2>
                <span class="modal-close" onclick="closeDetailsModal()">&times;</span>
            </div>
            <div id="orderDetailsContent">
                <!-- Content loaded via JavaScript -->
            </div>
        </div>
    </div>

    <script>
        // Tab switching
        function switchTab(tabName) {
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            document.querySelectorAll('.admin-tab').forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Show selected tab
            document.getElementById(tabName + '-tab').classList.add('active');
            event.target.classList.add('active');
        }

        // Approve order
        function approveOrder(orderId) {
            if (confirm('<?php echo $lang === 'cs' ? 'Opravdu chcete schválit tuto objednávku?' : 'Are you sure you want to approve this order?'; ?>')) {
                fetch('process_order_approval.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=approve&order_id=${orderId}&csrf_token=<?php echo $csrf_token; ?>&lang=<?php echo $lang; ?>`
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

        // Show deny modal
        function showDenyModal(orderId) {
            document.getElementById('deny_order_id').value = orderId;
            document.getElementById('denial_reason').value = '';
            document.getElementById('denyModal').style.display = 'block';
        }

        // Close deny modal
        function closeDenyModal() {
            document.getElementById('denyModal').style.display = 'none';
        }

        // Submit denial
        function submitDenial(event) {
            event.preventDefault();
            const formData = new FormData(event.target);
            formData.append('action', 'deny');
            formData.append('lang', '<?php echo $lang; ?>');

            fetch('process_order_approval.php', {
                method: 'POST',
                body: new URLSearchParams(formData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    closeDenyModal();
                    location.reload();
                } else {
                    alert(data.message);
                }
            })
            .catch(error => {
                alert('<?php echo $lang === 'cs' ? 'Chyba při komunikaci se serverem' : 'Error communicating with server'; ?>');
            });
        }

        // Show order details
        function showOrderDetails(orderId) {
            fetch(`process_order_approval.php?action=details&order_id=${orderId}&lang=<?php echo $lang; ?>`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('orderDetailsContent').innerHTML = data.html;
                    document.getElementById('detailsModal').style.display = 'block';
                } else {
                    alert(data.message);
                }
            })
            .catch(error => {
                alert('<?php echo $lang === 'cs' ? 'Chyba při načítání detailů' : 'Error loading details'; ?>');
            });
        }

        // Close details modal
        function closeDetailsModal() {
            document.getElementById('detailsModal').style.display = 'none';
        }

        // Close modals when clicking outside
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }

        // Display current year in footer
        document.getElementById('year').textContent = new Date().getFullYear();
    </script>
</body>
</html>
