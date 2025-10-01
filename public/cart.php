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

// Initialize cart if it doesn't exist
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Handle cart operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        echo json_encode(['success' => false, 'message' => 'Security validation failed.']);
        exit;
    }
    
    $action = $_POST['action'] ?? '';
    
    if ($action === 'remove_item') {
        $index = intval($_POST['index'] ?? -1);
        if ($index >= 0 && isset($_SESSION['cart'][$index])) {
            array_splice($_SESSION['cart'], $index, 1);
            $_SESSION['cart'] = array_values($_SESSION['cart']); // Re-index array
            
            logSecurityEvent('Item removed from cart', [
                'user_id' => $user_id,
                'index' => $index
            ]);
            
            echo json_encode([
                'success' => true, 
                'message' => $lang === 'cs' ? 'Položka odstraněna z košíku' : 'Item removed from cart',
                'cart_count' => count($_SESSION['cart'])
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => $lang === 'cs' ? 'Položka nenalezena' : 'Item not found']);
        }
        exit;
    }
    
    if ($action === 'clear_cart') {
        $_SESSION['cart'] = [];
        
        logSecurityEvent('Cart cleared', [
            'user_id' => $user_id
        ]);
        
        echo json_encode([
            'success' => true, 
            'message' => $lang === 'cs' ? 'Košík byl vyprázdněn' : 'Cart has been cleared',
            'cart_count' => 0
        ]);
        exit;
    }
    
    if ($action === 'checkout') {
        if (empty($_SESSION['cart'])) {
            echo json_encode(['success' => false, 'message' => $lang === 'cs' ? 'Košík je prázdný' : 'Cart is empty']);
            exit;
        }
        
        // Begin transaction
        $conn->begin_transaction();
        
        try {
            $success_count = 0;
            $failed_items = [];
            
            foreach ($_SESSION['cart'] as $index => $item) {
                $tool_id = intval($item['tool_id']);
                $start_date = $item['start_date'];
                $end_date = $item['end_date'];
                
                // Double-check availability before inserting
                $check_stmt = $conn->prepare("
                    SELECT COUNT(*) as conflicts 
                    FROM Availability 
                    WHERE tool_id = ? 
                    AND ((start_date <= ? AND end_date >= ?) 
                         OR (start_date <= ? AND end_date >= ?) 
                         OR (start_date >= ? AND end_date <= ?))
                ");
                $check_stmt->bind_param("issssss", $tool_id, $start_date, $start_date, $end_date, $end_date, $start_date, $end_date);
                $check_stmt->execute();
                $conflict_result = $check_stmt->get_result();
                $conflicts = $conflict_result->fetch_assoc()['conflicts'];
                
                if ($conflicts > 0) {
                    // Get tool name for error message
                    $tool_stmt = $conn->prepare("SELECT name, name_cs FROM Tools WHERE tool_id = ?");
                    $tool_stmt->bind_param("i", $tool_id);
                    $tool_stmt->execute();
                    $tool_result = $tool_stmt->get_result();
                    $tool_data = $tool_result->fetch_assoc();
                    $tool_name = $lang === 'cs' && !empty($tool_data['name_cs']) ? $tool_data['name_cs'] : $tool_data['name'];
                    
                    $failed_items[] = $tool_name;
                    continue;
                }
                
                // Insert reservation
                $insert_stmt = $conn->prepare("
                    INSERT INTO Availability (tool_id, user_id, start_date, end_date, status, created_at) 
                    VALUES (?, ?, ?, ?, 'reserved', NOW())
                ");
                $insert_stmt->bind_param("iiss", $tool_id, $user_id, $start_date, $end_date);
                
                if ($insert_stmt->execute()) {
                    $success_count++;
                } else {
                    throw new Exception("Failed to insert reservation");
                }
            }
            
            if (!empty($failed_items)) {
                $conn->rollback();
                $failed_list = implode(', ', $failed_items);
                echo json_encode([
                    'success' => false, 
                    'message' => $lang === 'cs' 
                        ? "Některé položky již nejsou dostupné: $failed_list" 
                        : "Some items are no longer available: $failed_list"
                ]);
                exit;
            }
            
            // Commit transaction
            $conn->commit();
            
            // Clear cart after successful checkout
            $_SESSION['cart'] = [];
            
            logSecurityEvent('Checkout completed', [
                'user_id' => $user_id,
                'items_count' => $success_count
            ]);
            
            echo json_encode([
                'success' => true, 
                'message' => $lang === 'cs' 
                    ? "Objednávka úspěšně dokončena! Rezervováno $success_count položek." 
                    : "Order completed successfully! $success_count items reserved.",
                'cart_count' => 0,
                'redirect' => "myorders.php?lang=$lang"
            ]);
            exit;
            
        } catch (Exception $e) {
            $conn->rollback();
            logSecurityEvent('Checkout failed', [
                'user_id' => $user_id,
                'error' => $e->getMessage()
            ]);
            
            echo json_encode([
                'success' => false, 
                'message' => $lang === 'cs' 
                    ? 'Chyba při zpracování objednávky. Zkuste to prosím znovu.' 
                    : 'Error processing order. Please try again.'
            ]);
            exit;
        }
    }
}

// Fetch cart items with tool details
$cart_items = [];
if (!empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $index => $item) {
        $tool_id = intval($item['tool_id']);
        
        $stmt = $conn->prepare("
            SELECT t.*, u.firstname, u.lastname 
            FROM Tools t 
            LEFT JOIN Users u ON t.ownerID = u.ownerID 
            WHERE t.tool_id = ?
        ");
        $stmt->bind_param("i", $tool_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($tool = $result->fetch_assoc()) {
            $tool['cart_index'] = $index;
            $tool['start_date'] = $item['start_date'];
            $tool['end_date'] = $item['end_date'];
            $tool['added_at'] = $item['added_at'];
            
            // Calculate rental duration
            $start = new DateTime($item['start_date']);
            $end = new DateTime($item['end_date']);
            $duration = $start->diff($end)->days + 1;
            $tool['duration'] = $duration;
            
            $cart_items[] = $tool;
        }
    }
}

// Generate CSRF token
$csrf_token = generateCSRFToken();

// Navbar variables
$loggedIn = true;
$fullName = htmlspecialchars($_SESSION['firstname'] . ' ' . $_SESSION['lastname']);
?>

<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $lang === 'cs' ? 'Košík' : 'Cart'; ?> - Tools4Friends</title>
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
        }
        
        .cart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #4a90e2;
        }
        
        .cart-header h1 {
            margin: 0;
        }
        
        .cart-count-badge {
            background: linear-gradient(135deg, #4a90e2 0%, #1F2D5A 100%);
            color: white;
            padding: 8px 20px;
            border-radius: 25px;
            font-weight: 600;
            font-size: 1.1em;
        }
        
        .cart-items {
            display: flex;
            flex-direction: column;
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .cart-item {
            display: flex;
            gap: 20px;
            padding: 20px;
            background: linear-gradient(145deg, #ffffff 0%, #f8f9fa 100%);
            border-radius: 16px;
            box-shadow: 0 8px 25px rgba(31, 45, 90, 0.1);
            transition: all 0.3s ease;
            border-left: 4px solid #4a90e2;
        }
        
        .cart-item:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 35px rgba(31, 45, 90, 0.15);
        }
        
        .cart-item-image {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border-radius: 12px;
            flex-shrink: 0;
        }
        
        .cart-item-details {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        
        .cart-item-title {
            font-size: 1.4em;
            color: #1F2D5A;
            margin: 0;
            font-weight: 700;
        }
        
        .cart-item-info {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .cart-item-info p {
            margin: 0;
            font-size: 1em;
            color: #495057;
        }
        
        .cart-item-info strong {
            color: #1F2D5A;
            font-weight: 600;
        }
        
        .cart-item-dates {
            background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
            padding: 12px 16px;
            border-radius: 8px;
            border-left: 4px solid #2196F3;
            margin-top: 10px;
        }
        
        .cart-item-actions {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .btn-remove {
            padding: 10px 20px;
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(220, 53, 69, 0.3);
        }
        
        .btn-remove:hover {
            background: linear-gradient(135deg, #c82333 0%, #bd2130 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(220, 53, 69, 0.4);
        }
        
        .empty-cart {
            text-align: center;
            padding: 60px 20px;
            background: linear-gradient(145deg, #ffffff 0%, #f8f9fa 100%);
            border-radius: 20px;
            box-shadow: 0 8px 25px rgba(31, 45, 90, 0.1);
        }
        
        .empty-cart-icon {
            font-size: 5em;
            margin-bottom: 20px;
            opacity: 0.5;
        }
        
        .empty-cart h2 {
            color: #6c757d;
            margin-bottom: 20px;
        }
        
        .cart-summary {
            background: linear-gradient(145deg, #ffffff 0%, #f8f9fa 100%);
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(31, 45, 90, 0.15);
            border-left: 4px solid #28a745;
            margin-bottom: 30px;
        }
        
        .cart-summary h2 {
            margin-top: 0;
            color: #1F2D5A;
            border-bottom: 2px solid #4a90e2;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            font-size: 1.1em;
        }
        
        .summary-row.total {
            border-top: 2px solid #4a90e2;
            margin-top: 15px;
            padding-top: 15px;
            font-weight: 700;
            font-size: 1.3em;
            color: #1F2D5A;
        }
        
        .cart-actions {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            margin-top: 30px;
        }
        
        .btn-primary {
            flex: 1;
            min-width: 200px;
            padding: 15px 30px;
            background: linear-gradient(135deg, #28a745 0%, #218838 100%);
            color: white;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            font-size: 1.1em;
            font-weight: 700;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
            text-align: center;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, #218838 0%, #1e7e34 100%);
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(40, 167, 69, 0.4);
        }
        
        .btn-secondary {
            flex: 1;
            min-width: 200px;
            padding: 15px 30px;
            background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%);
            color: white;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            font-size: 1.1em;
            font-weight: 700;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(108, 117, 125, 0.3);
            text-align: center;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-secondary:hover {
            background: linear-gradient(135deg, #5a6268 0%, #545b62 100%);
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(108, 117, 125, 0.4);
        }
        
        .btn-danger {
            flex: 1;
            min-width: 200px;
            padding: 15px 30px;
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            font-size: 1.1em;
            font-weight: 700;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(220, 53, 69, 0.3);
        }
        
        .btn-danger:hover {
            background: linear-gradient(135deg, #c82333 0%, #bd2130 100%);
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(220, 53, 69, 0.4);
        }
        
        .alert {
            padding: 15px 20px;
            margin: 20px 0;
            border-radius: 12px;
            display: none;
            font-weight: 500;
        }
        
        .alert.success {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            color: #155724;
            border-left: 4px solid #28a745;
        }
        
        .alert.error {
            background: linear-gradient(135deg, #f8d7da 0%, #f1b0b7 100%);
            color: #721c24;
            border-left: 4px solid #dc3545;
        }
        
        .duration-badge {
            display: inline-block;
            background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%);
            color: #000;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 0.9em;
            font-weight: 600;
            margin-left: 10px;
        }
        
        @media (max-width: 768px) {
            .cart-item {
                flex-direction: column;
            }
            
            .cart-item-image {
                width: 100%;
                height: 200px;
            }
            
            .cart-actions {
                flex-direction: column;
            }
            
            .btn-primary,
            .btn-secondary,
            .btn-danger {
                width: 100%;
            }
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
            <div class="cart-container">
                <div class="cart-header">
                    <h1>🛒 <?php echo $lang === 'cs' ? 'Váš košík' : 'Your Cart'; ?></h1>
                    <?php if (!empty($cart_items)): ?>
                        <div class="cart-count-badge">
                            <?php 
                            $count = count($cart_items);
                            echo $count . ' ' . ($lang === 'cs' 
                                ? ($count === 1 ? 'položka' : ($count < 5 ? 'položky' : 'položek'))
                                : ($count === 1 ? 'item' : 'items'));
                            ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div id="cart-alert" class="alert"></div>

                <?php if (empty($cart_items)): ?>
                    <div class="empty-cart">
                        <div class="empty-cart-icon">🛒</div>
                        <h2><?php echo $lang === 'cs' ? 'Váš košík je prázdný' : 'Your cart is empty'; ?></h2>
                        <p><?php echo $lang === 'cs' 
                            ? 'Přidejte nějaké nářadí do košíku a začněte rezervovat!' 
                            : 'Add some tools to your cart to get started!'; ?></p>
                        <div style="margin-top: 30px;">
                            <a href="tools.php?lang=<?php echo $lang; ?>" class="btn-primary">
                                <?php echo $lang === 'cs' ? '🔧 Procházet nářadí' : '🔧 Browse Tools'; ?>
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="cart-items">
                        <?php foreach ($cart_items as $item): 
                            $tool_name = $lang === 'cs' && !empty($item['name_cs']) ? $item['name_cs'] : $item['name'];
                            $description = $lang === 'cs' && !empty($item['description_cs']) ? $item['description_cs'] : $item['description'];
                        ?>
                            <div class="cart-item" data-index="<?php echo $item['cart_index']; ?>">
                                <img src="<?php echo htmlspecialchars($item['picture']); ?>" 
                                     alt="<?php echo htmlspecialchars($tool_name); ?>" 
                                     class="cart-item-image">
                                
                                <div class="cart-item-details">
                                    <h3 class="cart-item-title">
                                        <?php echo htmlspecialchars($tool_name); ?>
                                        <span class="duration-badge">
                                            <?php echo $item['duration'] . ' ' . ($lang === 'cs' 
                                                ? ($item['duration'] === 1 ? 'den' : ($item['duration'] < 5 ? 'dny' : 'dní'))
                                                : ($item['duration'] === 1 ? 'day' : 'days')); ?>
                                        </span>
                                    </h3>
                                    
                                    <div class="cart-item-info">
                                        <p><strong><?php echo $lang === 'cs' ? 'Značka:' : 'Brand:'; ?></strong> 
                                            <?php echo htmlspecialchars($item['brand']); ?></p>
                                        <p><strong><?php echo $lang === 'cs' ? 'Model:' : 'Model:'; ?></strong> 
                                            <?php echo htmlspecialchars($item['model']); ?></p>
                                        <?php if (!empty($item['firstname']) && !empty($item['lastname'])): ?>
                                            <p><strong><?php echo $lang === 'cs' ? 'Vlastník:' : 'Owner:'; ?></strong> 
                                                <?php echo htmlspecialchars($item['firstname'] . ' ' . $item['lastname']); ?></p>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="cart-item-dates">
                                        <p style="margin: 0;">
                                            <strong>📅 <?php echo $lang === 'cs' ? 'Období výpůjčky:' : 'Rental Period:'; ?></strong><br>
                                            <?php 
                                            echo date('d.m.Y', strtotime($item['start_date'])) . ' - ' . 
                                                 date('d.m.Y', strtotime($item['end_date'])); 
                                            ?>
                                        </p>
                                    </div>
                                </div>
                                
                                <div class="cart-item-actions">
                                    <button class="btn-remove" onclick="removeItem(<?php echo $item['cart_index']; ?>)">
                                        🗑️ <?php echo $lang === 'cs' ? 'Odstranit' : 'Remove'; ?>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="cart-summary">
                        <h2><?php echo $lang === 'cs' ? 'Souhrn objednávky' : 'Order Summary'; ?></h2>
                        <div class="summary-row">
                            <span><?php echo $lang === 'cs' ? 'Celkem položek:' : 'Total Items:'; ?></span>
                            <strong><?php echo count($cart_items); ?></strong>
                        </div>
                        <div class="summary-row">
                            <span><?php echo $lang === 'cs' ? 'Celkem dní:' : 'Total Days:'; ?></span>
                            <strong><?php echo array_sum(array_column($cart_items, 'duration')); ?></strong>
                        </div>
                        <div class="summary-row total">
                            <span><?php echo $lang === 'cs' ? 'Připraveno k rezervaci' : 'Ready to Reserve'; ?></span>
                            <span>✓</span>
                        </div>
                    </div>

                    <div class="cart-actions">
                        <a href="tools.php?lang=<?php echo $lang; ?>" class="btn-secondary">
                            ← <?php echo $lang === 'cs' ? 'Pokračovat v nákupu' : 'Continue Shopping'; ?>
                        </a>
                        <button class="btn-danger" onclick="clearCart()">
                            🗑️ <?php echo $lang === 'cs' ? 'Vyprázdnit košík' : 'Clear Cart'; ?>
                        </button>
                        <button class="btn-primary" onclick="checkout()">
                            ✓ <?php echo $lang === 'cs' ? 'Dokončit objednávku' : 'Complete Order'; ?>
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </main>

        <footer>
            <p>&copy; <span id="year"></span> Tools4Friends</p>
        </footer>
    </div>

    <script>
        const lang = '<?php echo $lang; ?>';
        const csrfToken = '<?php echo $csrf_token; ?>';

        function showAlert(type, message) {
            const alert = document.getElementById('cart-alert');
            alert.className = `alert ${type}`;
            alert.textContent = message;
            alert.style.display = 'block';
            
            setTimeout(() => {
                alert.style.display = 'none';
            }, 5000);
            
            // Scroll to alert
            alert.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }

        async function removeItem(index) {
            if (!confirm(lang === 'cs' 
                ? 'Opravdu chcete odstranit tuto položku z košíku?' 
                : 'Are you sure you want to remove this item from the cart?')) {
                return;
            }

            try {
                const formData = new FormData();
                formData.append('action', 'remove_item');
                formData.append('index', index);
                formData.append('csrf_token', csrfToken);

                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    showAlert('success', result.message);
                    
                    // Remove item from DOM
                    const itemElement = document.querySelector(`[data-index="${index}"]`);
                    if (itemElement) {
                        itemElement.style.transition = 'all 0.3s ease';
                        itemElement.style.opacity = '0';
                        itemElement.style.transform = 'translateX(-100%)';
                        
                        setTimeout(() => {
                            location.reload();
                        }, 300);
                    }
                } else {
                    showAlert('error', result.message);
                }
            } catch (error) {
                console.error('Error:', error);
                showAlert('error', lang === 'cs' 
                    ? 'Došlo k chybě. Zkuste to prosím znovu.' 
                    : 'An error occurred. Please try again.');
            }
        }

        async function clearCart() {
            if (!confirm(lang === 'cs' 
                ? 'Opravdu chcete vyprázdnit celý košík?' 
                : 'Are you sure you want to clear the entire cart?')) {
                return;
            }

            try {
                const formData = new FormData();
                formData.append('action', 'clear_cart');
                formData.append('csrf_token', csrfToken);

                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    showAlert('success', result.message);
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                } else {
                    showAlert('error', result.message);
                }
            } catch (error) {
                console.error('Error:', error);
                showAlert('error', lang === 'cs' 
                    ? 'Došlo k chybě. Zkuste to prosím znovu.' 
                    : 'An error occurred. Please try again.');
            }
        }

        async function checkout() {
            if (!confirm(lang === 'cs' 
                ? 'Dokončit objednávku a rezervovat vybrané nářadí
