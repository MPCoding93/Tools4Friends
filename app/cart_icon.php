<?php
// Cart icon component - displays fixed cart icon on all pages
// Requires session to be started and $lang variable to be set

// Get cart count for display
$cart_count = isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0;
?>

<!-- Fixed Cart Icon - Always visible in bottom right -->
<a href="/public/cart.php?lang=<?php echo sanitizeOutput($lang); ?>" class="cart-link">
    🛒 <?php echo $lang === 'cs' ? 'Košík' : 'Cart'; ?>
    <?php if ($cart_count > 0): ?>
        <span class="cart-count"><?php echo $cart_count; ?></span>
    <?php endif; ?>
</a>
