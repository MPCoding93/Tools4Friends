<?php
session_start();
require_once __DIR__ . '/../app/db_connect.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authorized']);
    exit;
}

$lang = $_POST['lang'] ?? 'en';
$availability_id = intval($_POST['availability_id']);
$user_id = $_SESSION['user_id'];

// Verify the availability record belongs to the user's tool
$verify_stmt = $conn->prepare("
    SELECT a.availability_id 
    FROM Availability a
    JOIN Tools t ON a.tool_id = t.tool_id
    JOIN Users u ON t.ownerID = u.ownerID
    WHERE a.availability_id = ? AND u.user_id = ?
");
$verify_stmt->bind_param("ii", $availability_id, $user_id);
$verify_stmt->execute();
$verify_stmt->store_result();

if ($verify_stmt->num_rows === 0) {
    echo json_encode([
        'success' => false, 
        'message' => $lang === 'cs' ? 'Rezervace nebyla nalezena nebo nemáte oprávnění.' : 'Reservation not found or not authorized.'
    ]);
    exit;
}

// Delete the availability record (or update status if you prefer)
$delete_stmt = $conn->prepare("DELETE FROM Availability WHERE availability_id = ?");
if ($delete_stmt->execute([$availability_id])) {
    echo json_encode([
        'success' => true, 
        'message' => $lang === 'cs' ? 'Rezervace byla úspěšně zrušena.' : 'Reservation successfully cancelled.'
    ]);
} else {
    echo json_encode([
        'success' => false, 
        'message' => $lang === 'cs' ? 'Chyba při rušení rezervace.' : 'Error cancelling reservation.'
    ]);
}