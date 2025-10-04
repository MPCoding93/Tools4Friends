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

// Initialize cart in session if not exists
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Handle cart actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'remove' && isset($_POST['cart_index'])) {
        $cart_index = intval($_POST['cart_index']);
        if (isset($_SESSION['cart'][$cart_index])) {
            array_splice($_SESSION['cart'], $cart_index, 1);
        }
        
        header("Location: cart.php?lang=" . $lang);
        exit();
    }
    
    if ($_POST['action'] === 'update_dates' && isset($_POST['cart_index'])) {
        $cart_index = intval($_POST['cart_index']);
        $new_start = $_POST['start_date'] ?? '';
        $new_end = $_POST['end_date'] ?? '';
        
        if (isset($_SESSION['cart'][$cart_index]) && validateDate($new_start) && validateDate($new_end)) {
            if ($new_end >= $new_start) {
                $_SESSION['cart'][$cart_index]['start_date'] = $new_start;
                $_SESSION['cart'][$cart_index]['end_date'] = $new_end;
            }
        }
        
        header("Location: cart.php?lang=" . $lang);
        exit();
    }
    
    if ($_POST['action'] === 'checkout') {
        // Validate CSRF token
        if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
            $error_message = $lang === 'cs' ? 'Bezpečnostní ověření selhalo' : 'Security validation failed';
        } else {
            // Process checkout - create order and availability records
            $conn->begin_transaction();
            
            try {
                // Calculate totals for the order
                $total_amount = 0;
                $total_deposit = 0;
                
                // First, get tool details including deposit
                $cart_with_details = [];
                foreach ($_SESSION['cart'] as $item) {
                    $tool_query = $conn->prepare("
                        SELECT tool_id, manipulation_fee, deposit 
                        FROM Tools 
                        WHERE tool_id = ?
                    ");
                    $tool_query->bind_param("i", $item['tool_id']);
                    $tool_query->execute();
                    $tool_result = $tool_query->get_result();
                    $tool_data = $tool_result->fetch_assoc();
                    
                    if ($tool_data) {
                        $total_amount += $tool_data['manipulation_fee'];
                        $total_deposit += $tool_data['deposit'];
                        
                        $cart_with_details[] = [
                            'tool_id' => $item['tool_id'],
                            'start_date' => $item['start_date'],
                            'end_date' => $item['end_date'],
                            'fee' => $tool_data['manipulation_fee'],
                            'deposit' => $tool_data['deposit']
                        ];
                    }
                }
                
                // Create the Order record
                $order_stmt = $conn->prepare("
                    INSERT INTO Orders (user_id, order_date, status, total_amount, total_deposit)
                    VALUES (?, NOW(), 'pending', ?, ?)
                ");
                $order_stmt->bind_param("idd", $user_id, $total_amount, $total_deposit);
                $order_stmt->execute();
                $order_id = $conn->insert_id;
                
                // Create Availability records linked to this order
                foreach ($cart_with_details as $item) {
                    $avail_stmt = $conn->prepare("
                        INSERT INTO Availability (tool_id, user_id, start_date, end_date, status, order_id, created_at)
                        VALUES (?, ?, ?, ?, 'pending', ?, NOW())
                    ");
                    $avail_stmt->bind_param("iissi", 
                        $item['tool_id'], 
                        $user_id, 
                        $item['start_date'], 
                        $item['end_date'],
                        $order_id
                    );
                    $avail_stmt->execute();
                }
                
                $conn->commit();
                $_SESSION['cart'] = []; // Clear cart
                $_SESSION['checkout_success'] = true;
                
                header("Location: myorders.php?lang=" . $lang);
                exit();
                
            } catch (Exception $e) {
                $conn->rollback();
                $error_message = $lang === 'cs' ? 'Chyba při zpracování objednávky: ' . $e->getMessage() : 'Error processing order: ' . $e->getMessage();
            }
        }
    }
}

// Fetch cart items details
$cart_items = [];
if (!empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $index => $item) {
        $tool_id = $item['tool_id'];
        
        $query = $conn->prepare("
            SELECT 
                t.tool_id,
                t.name,
                t.name_cs,
                t.picture,
                t.manipulation_fee,
                t.deposit,
                t.ownerID,
                u.firstname AS owner_firstname,
                u.lastname AS owner_lastname
            FROM Tools t
            JOIN Users u ON t.ownerID = u.ownerID
            WHERE t.tool_id = ?
        ");
        
        $query->bind_param("i", $tool_id);
        $query->execute();
        $result = $query->get_result();
        
        if ($tool = $result->fetch_assoc()) {
            $tool['cart_index'] = $index;
            $tool['start_date'] = $item['start_date'];
            $tool['end_date'] = $item['end_date'];
            
            // Calculate days and total fee
            $start = new DateTime($tool['start_date']);
            $end = new DateTime($tool['end_date']);
            $days = $start->diff($end)->days + 1;
            
            $tool['days'] = $days;
            $tool['total_fee'] = $tool['manipulation_fee'];
            $tool['deposit_amount'] = $tool['deposit'];
            
            $cart_items[] = $tool;
        }
    }
}

// Calculate totals
$total_amount = 0;
$total_deposit = 0;
foreach ($cart_items as $item) {
    $total_amount += $item['total_fee'];
    $total_deposit += $item['deposit_amount'];
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
    <title><?php echo ($lang === 'cs' ? 'Košík' : 'Cart'); ?> - Tools4Friends</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="icon" href="favicon/favicon-dark.ico" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Libre+Baskerville:ital,wght@0,400;0,700;1,400&family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <script src="script.js" defer></script>
    <style>
        .cart-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .cart-item {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
            padding: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .cart-item-image {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border-radius: 4px;
        }
        
        .cart-item-details {
            flex: 1;
        }
        
        .cart-item-details h3 {
            margin-top: 0;
            color: #1F2D5A;
        }
        
        .cart-summary {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-top: 20px;
        }
        
        .cart-summary h2 {
            color: #1F2D5A;
            border-bottom: 2px solid #4a90e2;
            padding-bottom: 10px;
        }
        
        .total-row {
            display: flex;
            justify-content: space-between;
            font-size: 1.2em;
            font-weight: bold;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 2px solid #ddd;
        }
        
        .empty-cart {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .empty-cart h2 {
            color: #1F2D5A;
        }
        
        .btn-remove {
            background-color: #dc3545;
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .btn-remove:hover {
            background-color: #c82333;
        }
        
        .btn-checkout {
            background-color: #28a745;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1.1em;
            width: 100%;
            margin-top: 15px;
        }
        
        .btn-checkout:hover {
            background-color: #218838;
        }
        
        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../app/cart_icon.php'; ?>
    
    <div class="container">
        <header>
            <div class="banner">
                <img src="images/banners/tools4friends_dark_Banner_2000x400.png" alt="Company Logo" />
            </div>
        </header>
        <div class="line-break"></div>
        <?php include __DIR__ . '/../app/navbar.php'; ?>

        <main class="cart-container">
            <div style="margin-bottom: 20px;">
                <a href="tools.php?lang=<?php echo $lang; ?>" class="btn btn-blue">
                    <?php echo $lang === 'cs' ? '← Pokračovat v nákupu' : '← Continue Shopping'; ?>
                </a>
            </div>
            
            <h1><?php echo $lang === 'cs' ? 'Košík' : 'Shopping Cart'; ?></h1>
            <div class="line-break"></div>

            <?php if (isset($error_message)): ?>
                <div class="error-message"><?php echo $error_message; ?></div>
            <?php endif; ?>

            <?php if (empty($cart_items)): ?>
                <div class="empty-cart">
                    <h2><?php echo $lang === 'cs' ? 'Váš košík je prázdný' : 'Your cart is empty'; ?></h2>
                    <p><?php echo $lang === 'cs' ? 'Přidejte nástroje do košíku pro rezervaci' : 'Add tools to your cart to make a reservation'; ?></p>
                    <a href="tools.php?lang=<?php echo $lang; ?>" class="btn btn-blue">
                        <?php echo $lang === 'cs' ? 'Procházet nástroje' : 'Browse Tools'; ?>
                    </a>
                </div>
            <?php else: ?>
                <!-- Cart Items -->
                <?php foreach ($cart_items as $item): 
                    $tool_name = $lang === 'cs' && !empty($item['name_cs']) ? $item['name_cs'] : $item['name'];
                ?>
                    <div class="cart-item">
                        <img src="<?php echo htmlspecialchars($item['picture']); ?>" 
                             alt="<?php echo htmlspecialchars($tool_name); ?>" 
                             class="cart-item-image">
                        <div class="cart-item-details">
                            <h3><?php echo htmlspecialchars($tool_name); ?></h3>
                            <p><strong><?php echo $lang === 'cs' ? 'Vlastník:' : 'Owner:'; ?></strong> 
                                <?php echo htmlspecialchars($item['owner_firstname'] . ' ' . $item['owner_lastname']); ?>
                            </p>
                            <form method="POST" style="margin-top: 10px;">
                                <input type="hidden" name="action" value="update_dates">
                                <input type="hidden" name="cart_index" value="<?php echo $item['cart_index']; ?>">
                                
                                <div style="margin-bottom: 10px;">
                                    <label style="display: block; font-weight: 600; margin-bottom: 5px;">
                                        <?php echo $lang === 'cs' ? 'Datum od:' : 'Start Date:'; ?>
                                    </label>
                                    <input type="date" name="start_date" value="<?php echo $item['start_date']; ?>" 
                                           min="<?php echo date('Y-m-d'); ?>" required
                                           style="padding: 5px; border: 1px solid #ddd; border-radius: 4px; width: 100%;">
                                </div>
                                
                                <div style="margin-bottom: 10px;">
                                    <label style="display: block; font-weight: 600; margin-bottom: 5px;">
                                        <?php echo $lang === 'cs' ? 'Datum do:' : 'End Date:'; ?>
                                    </label>
                                    <input type="date" name="end_date" value="<?php echo $item['end_date']; ?>" 
                                           min="<?php echo $item['start_date']; ?>" required
                                           style="padding: 5px; border: 1px solid #ddd; border-radius: 4px; width: 100%;">
                                </div>
                                
                                <p><strong><?php echo $lang === 'cs' ? 'Počet dní:' : 'Number of days:'; ?></strong> 
                                    <?php echo $item['days']; ?>
                                </p>
                                <p><strong><?php echo $lang === 'cs' ? 'Poplatek:' : 'Fee:'; ?></strong> 
                                    <?php echo $item['manipulation_fee']; ?> Kč
                                </p>
                                <p><strong><?php echo $lang === 'cs' ? 'Záloha:' : 'Deposit:'; ?></strong> 
                                    <?php echo $item['deposit_amount']; ?> Kč
                                </p>
                                <p><strong><?php echo $lang === 'cs' ? 'Celkem:' : 'Total:'; ?></strong> 
                                    <?php echo $item['total_fee']; ?> Kč
                                </p>
                                
                                <div style="display: flex; gap: 10px; margin-top: 10px;">
                                    <button type="submit" class="btn-update" style="flex: 1; background-color: #4a90e2; color: white; padding: 8px; border: none; border-radius: 4px; cursor: pointer;">
                                        <?php echo $lang === 'cs' ? '🔄 Aktualizovat' : '🔄 Update'; ?>
                                    </button>
                                </div>
                            </form>
                            
                            <form method="POST" style="margin-top: 5px;">
                                <input type="hidden" name="action" value="remove">
                                <input type="hidden" name="cart_index" value="<?php echo $item['cart_index']; ?>">
                                <button type="submit" class="btn-remove" style="width: 100%;">
                                    <?php echo $lang === 'cs' ? '🗑️ Odebrat' : '🗑️ Remove'; ?>
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>

                <!-- Cart Summary -->
                <div class="cart-summary">
                    <h2><?php echo $lang === 'cs' ? 'Souhrn objednávky' : 'Order Summary'; ?></h2>
                    <div style="margin-bottom: 10px;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                            <span><?php echo $lang === 'cs' ? 'Celkový poplatek:' : 'Total Fee:'; ?></span>
                            <span><?php echo $total_amount; ?> Kč</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                            <span><?php echo $lang === 'cs' ? 'Celková záloha:' : 'Total Deposit:'; ?></span>
                            <span><?php echo $total_deposit; ?> Kč</span>
                        </div>
                    </div>
                    <div class="total-row">
                        <span><?php echo $lang === 'cs' ? 'Celkem k zaplacení:' : 'Total to Pay:'; ?></span>
                        <span><?php echo ($total_amount + $total_deposit); ?> Kč</span>
                    </div>
                    
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        <input type="hidden" name="action" value="checkout">
                        <button type="submit" class="btn-checkout">
                            <?php echo $lang === 'cs' ? 'Dokončit objednávku' : 'Complete Order'; ?>
                        </button>
                    </form>
                </div>
            <?php endif; ?>
        </main>

        <footer>
            <p>&copy; <span id="year"></span> Tools4Friends</p>
        </footer>
    </div>
    
    <script>
        // Display current year in footer
        document.getElementById('year').textContent = new Date().getFullYear();
    </script>
</body>
</html>
