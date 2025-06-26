<?php
session_start();
require_once __DIR__ . '/../app/db_connect.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authorized']);
    exit;
}

$lang = $_POST['lang'] ?? 'en';
$order_id = intval($_POST['order_id']);
$user_id = $_SESSION['user_id'];

// Verify the order belongs to the user
$verify_stmt = $conn->prepare("SELECT order_id FROM Orders WHERE order_id = ? AND user_id = ?");
$verify_stmt->bind_param("ii", $order_id, $user_id);
$verify_stmt->execute();
$verify_stmt->store_result();

if ($verify_stmt->num_rows === 0) {
    echo json_encode([
        'success' => false, 
        'message' => $lang === 'cs' ? 'Objednávka nebyla nalezena nebo nemáte oprávnění.' : 'Order not found or not authorized.'
    ]);
    exit;
}

// Update order status to cancelled
$update_stmt = $conn->prepare("UPDATE Orders SET status = 'cancelled' WHERE order_id = ?");
if ($update_stmt->execute([$order_id])) {
    echo json_encode([
        'success' => true, 
        'message' => $lang === 'cs' ? 'Objednávka byla úspěšně zrušena.' : 'Order successfully cancelled.'
    ]);
} else {
    echo json_encode([
        'success' => false, 
        'message' => $lang === 'cs' ? 'Chyba při rušení objednávky.' : 'Error cancelling order.'
    ]);
}
