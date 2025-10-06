<?php
// Cart icon component - displays fixed cart icon on all pages
// Requires session to be started and $lang variable to be set

// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Ensure $lang is set
$lang = $lang ?? $_GET['lang'] ?? 'en';

// Get cart count for display
$cart_count = isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0;

// Determine if we're in the public folder or root
$inPublicFolder = (strpos($_SERVER['PHP_SELF'], '/public/') !== false);
$cartLink = $inPublicFolder ? './cart.php' : './public/cart.php';
?>

<!-- Fixed Cart Icon - Always visible in bottom right -->
<a href="<?php echo $cartLink; ?>?lang=<?php echo sanitizeOutput($lang); ?>" class="cart-link">
    🛒 <?php echo $lang === 'cs' ? 'Košík' : 'Cart'; ?>
    <?php if ($cart_count > 0): ?>
        <span class="cart-count"><?php echo $cart_count; ?></span>
    <?php endif; ?>
</a>
