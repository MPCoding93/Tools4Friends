<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require_once __DIR__ . '/../app/db_connect.php';

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

// Handle add to cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add' && isset($_POST['tool_id'])) {
        $tool_id = intval($_POST['tool_id']);
        $start_date = $_POST['start_date'] ?? '';
        $end_date = $_POST['end_date'] ?? '';
        
        // Add to cart session
        $_SESSION['cart'][$tool_id] = [
            'tool_id' => $tool_id,
            'start_date' => $start_date,
            'end_date' => $end_date
        ];
        
        header("Location: cart.php?lang=" . $lang);
        exit();
    }
    
    if ($_POST['action'] === 'remove' && isset($_POST['tool_id'])) {
        $tool_id = intval($_POST['tool_id']);
        unset($_SESSION['cart'][$tool_id]);
        
        header("Location: cart.php?lang=" . $lang);
        exit();
    }
    
    if ($_POST['action'] === 'checkout') {
        // Process checkout - create availability records
        $success = true;
        $conn->begin_transaction();
        
        try {
            foreach ($_SESSION['cart'] as $item) {
                $stmt = $conn->prepare("
                    INSERT INTO Availability (tool_id, user_id, start_date, end_date, status, created_at)
                    VALUES (?, ?, ?, ?, 'reserved', NOW())
                ");
                $stmt->bind_param("iiss", $item['tool_id'], $user_id, $item['start_date'], $item['end_date']);
                $stmt->execute();
            }
            
            $conn->commit();
            $_SESSION['cart'] = []; // Clear cart
            $_SESSION['checkout_success'] = true;
            
            header("Location: myorders.php?lang=" . $lang);
            exit();
            
        } catch (Exception $e) {
            $conn->rollback();
            $error_message = $lang === 'cs' ? 'Chyba při zpracování objednávky' : 'Error processing order';
        }
    }
}

// Fetch cart items details
$cart_items = [];
if (!empty($_SESSION['cart'])) {
    $tool_ids = array_keys($_SESSION['cart']);
    $placeholders = implode(',', array_fill(0, count($tool_ids), '?'));
    
    $query = $conn->prepare("
        SELECT 
            t.tool_id,
            t.name,
            t.name_cs,
            t.picture,
            t.manipulation_fee,
            t.ownerID,
            u.firstname AS owner_firstname,
            u.lastname AS owner_lastname
        FROM Tools t
        JOIN Users u ON t.ownerID = u.ownerID
        WHERE t.tool_id IN ($placeholders)
    ");
    
    $types = str_repeat('i', count($tool_ids));
    $query->bind_param($types, ...$tool_ids);
    $query->execute();
    $result = $query->get_result();
    
    while ($tool = $result->fetch_assoc()) {
        $tool['start_date'] = $_SESSION['cart'][$tool['tool_id']]['start_date'];
        $tool['end_date'] = $_SESSION['cart'][$tool['tool_id']]['end_date'];
        
        // Calculate days and total fee
        $days = (strtotime($tool['end_date']) - strtotime($tool['start_date'])) / (60 * 60 * 24) + 1;
        $tool['days'] = $days;
        $tool['total_fee'] = $tool['manipulation_fee'] * $days;
        
        $cart_items[] = $tool;
    }
}

// Calculate total
$total_amount = 0;
foreach ($cart_items as $item) {
    $total_amount += $item['total_fee'];
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
                            <p><strong><?php echo $lang === 'cs' ? 'Období výpůjčky:' : 'Rental period:'; ?></strong> 
                                <?php echo date('d.m.Y', strtotime($item['start_date'])) . ' - ' . date('d.m.Y', strtotime($item['end_date'])); ?>
                            </p>
                            <p><strong><?php echo $lang === 'cs' ? 'Počet dní:' : 'Number of days:'; ?></strong> 
                                <?php echo $item['days']; ?>
                            </p>
                            <p><strong><?php echo $lang === 'cs' ? 'Poplatek za den:' : 'Fee per day:'; ?></strong> 
                                <?php echo $item['manipulation_fee']; ?> Kč
                            </p>
                            <p><strong><?php echo $lang === 'cs' ? 'Celkem:' : 'Total:'; ?></strong> 
                                <?php echo $item['total_fee']; ?> Kč
                            </p>
                            <form method="POST" style="margin-top: 10px;">
                                <input type="hidden" name="action" value="remove">
                                <input type="hidden" name="tool_id" value="<?php echo $item['tool_id']; ?>">
                                <button type="submit" class="btn-remove">
                                    <?php echo $lang === 'cs' ? 'Odebrat' : 'Remove'; ?>
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>

                <!-- Cart Summary -->
                <div class="cart-summary">
                    <h2><?php echo $lang === 'cs' ? 'Souhrn objednávky' : 'Order Summary'; ?></h2>
                    <div class="total-row">
                        <span><?php echo $lang === 'cs' ? 'Celková částka:' : 'Total Amount:'; ?></span>
                        <span><?php echo $total_amount; ?> Kč</span>
                    </div>
                    
                    <form method="POST">
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
