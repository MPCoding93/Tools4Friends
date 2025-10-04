<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../app/security.php';
require_once __DIR__ . '/../app/db_connect.php';
require_once __DIR__ . '/../app/email_functions.php';

startSecureSession();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['admin']) || $_SESSION['admin'] != 1) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$lang = $_GET['lang'] ?? $_POST['lang'] ?? 'en';
$admin_id = $_SESSION['user_id'];

// Handle GET request for order details
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'details') {
    $order_id = intval($_GET['order_id']);
    
    // Fetch order details with all tools
    $query = $conn->prepare("
        SELECT 
            o.order_id,
            o.user_id,
            o.order_date,
            o.status,
            o.total_amount,
            o.total_deposit,
            u.firstname,
            u.lastname,
            u.email,
            u.phone
        FROM Orders o
        JOIN Users u ON o.user_id = u.user_id
        WHERE o.order_id = ?
    ");
    $query->bind_param("i", $order_id);
    $query->execute();
    $order = $query->get_result()->fetch_assoc();
    
    if (!$order) {
        echo json_encode(['success' => false, 'message' => 'Order not found']);
        exit();
    }
    
    // Fetch tools in this order
    $tools_query = $conn->prepare("
        SELECT 
            a.availability_id,
            a.start_date,
            a.end_date,
            t.name,
            t.name_cs,
            t.manipulation_fee,
            t.deposit,
            t.picture
        FROM Availability a
        JOIN Tools t ON a.tool_id = t.tool_id
        WHERE a.order_id = ?
    ");
    $tools_query->bind_param("i", $order_id);
    $tools_query->execute();
    $tools_result = $tools_query->get_result();
    
    $tools = [];
    while ($tool = $tools_result->fetch_assoc()) {
        $tools[] = $tool;
    }
    
    // Build HTML
    $html = '<div style="padding: 10px;">';
    $html .= '<h3>' . ($lang === 'cs' ? 'Informace o zákazníkovi' : 'Customer Information') . '</h3>';
    $html .= '<p><strong>' . ($lang === 'cs' ? 'Jméno:' : 'Name:') . '</strong> ' . htmlspecialchars($order['firstname'] . ' ' . $order['lastname']) . '</p>';
    $html .= '<p><strong>' . ($lang === 'cs' ? 'Email:' : 'Email:') . '</strong> ' . htmlspecialchars($order['email']) . '</p>';
    if ($order['phone']) {
        $html .= '<p><strong>' . ($lang === 'cs' ? 'Telefon:' : 'Phone:') . '</strong> ' . htmlspecialchars($order['phone']) . '</p>';
    }
    $html .= '<p><strong>' . ($lang === 'cs' ? 'Datum objednávky:' : 'Order Date:') . '</strong> ' . date('d.m.Y H:i', strtotime($order['order_date'])) . '</p>';
    
    $html .= '<h3 style="margin-top: 20px;">' . ($lang === 'cs' ? 'Objednané nástroje' : 'Ordered Tools') . '</h3>';
    
    foreach ($tools as $tool) {
        $tool_name = $lang === 'cs' && !empty($tool['name_cs']) ? $tool['name_cs'] : $tool['name'];
        $start = new DateTime($tool['start_date']);
        $end = new DateTime($tool['end_date']);
        $days = $start->diff($end)->days + 1;
        
        $html .= '<div style="border: 1px solid #ddd; padding: 15px; margin-bottom: 10px; border-radius: 4px;">';
        $html .= '<div style="display: flex; gap: 15px; align-items: start;">';
        $html .= '<img src="' . htmlspecialchars($tool['picture']) . '" style="width: 80px; height: 80px; object-fit: cover; border-radius: 4px;">';
        $html .= '<div style="flex: 1;">';
        $html .= '<h4 style="margin: 0 0 10px 0;">' . htmlspecialchars($tool_name) . '</h4>';
        $html .= '<p style="margin: 5px 0;"><strong>' . ($lang === 'cs' ? 'Období:' : 'Period:') . '</strong> ' . date('d.m.Y', strtotime($tool['start_date'])) . ' - ' . date('d.m.Y', strtotime($tool['end_date'])) . ' (' . $days . ' ' . ($lang === 'cs' ? 'dní' : 'days') . ')</p>';
        $html .= '<p style="margin: 5px 0;"><strong>' . ($lang === 'cs' ? 'Poplatek:' : 'Fee:') . '</strong> ' . number_format($tool['manipulation_fee'], 2) . ' Kč</p>';
        $html .= '<p style="margin: 5px 0;"><strong>' . ($lang === 'cs' ? 'Záloha:' : 'Deposit:') . '</strong> ' . number_format($tool['deposit'], 2) . ' Kč</p>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
    }
    
    $html .= '<div style="margin-top: 20px; padding-top: 20px; border-top: 2px solid #ddd;">';
    $html .= '<p style="font-size: 1.1em;"><strong>' . ($lang === 'cs' ? 'Celkový poplatek:' : 'Total Fee:') . '</strong> ' . number_format($order['total_amount'], 2) . ' Kč</p>';
    $html .= '<p style="font-size: 1.1em;"><strong>' . ($lang === 'cs' ? 'Celková záloha:' : 'Total Deposit:') . '</strong> ' . number_format($order['total_deposit'], 2) . ' Kč</p>';
    $html .= '<p style="font-size: 1.2em; font-weight: bold;"><strong>' . ($lang === 'cs' ? 'Celkem k zaplacení:' : 'Total to Pay:') . '</strong> ' . number_format($order['total_amount'] + $order['total_deposit'], 2) . ' Kč</p>';
    $html .= '</div>';
    $html .= '</div>';
    
    echo json_encode(['success' => true, 'html' => $html]);
    exit();
}

// Handle POST requests for approve/deny
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        echo json_encode([
            'success' => false, 
            'message' => $lang === 'cs' ? 'Bezpečnostní ověření selhalo' : 'Security validation failed'
        ]);
        exit();
    }
    
    $order_id = intval($_POST['order_id']);
    
    // Verify order exists and is pending
    $check_query = $conn->prepare("SELECT status, user_id FROM Orders WHERE order_id = ?");
    $check_query->bind_param("i", $order_id);
    $check_query->execute();
    $order_check = $check_query->get_result()->fetch_assoc();
    
    if (!$order_check) {
        echo json_encode([
            'success' => false, 
            'message' => $lang === 'cs' ? 'Objednávka nenalezena' : 'Order not found'
        ]);
        exit();
    }
    
    if ($order_check['status'] !== 'pending') {
        echo json_encode([
            'success' => false, 
            'message' => $lang === 'cs' ? 'Objednávka již byla zpracována' : 'Order has already been processed'
        ]);
        exit();
    }
    
    if ($action === 'approve') {
        $conn->begin_transaction();
        
        try {
            // Generate invoice number
            $year = date('Y');
            $month = date('m');
            
            // Get the last invoice number for this month
            $invoice_query = $conn->prepare("
                SELECT invoice_number 
                FROM Orders 
                WHERE invoice_number LIKE ? 
                ORDER BY invoice_number DESC 
                LIMIT 1
            ");
            $invoice_pattern = "T4F-$year-$month-%";
            $invoice_query->bind_param("s", $invoice_pattern);
            $invoice_query->execute();
            $last_invoice = $invoice_query->get_result()->fetch_assoc();
            
            if ($last_invoice) {
                // Extract the number and increment
                $last_num = intval(substr($last_invoice['invoice_number'], -4));
                $new_num = $last_num + 1;
            } else {
                $new_num = 1;
            }
            
            $invoice_number = sprintf("T4F-%s-%s-%04d", $year, $month, $new_num);
            
            // Update order status
            $update_order = $conn->prepare("
                UPDATE Orders 
                SET status = 'approved', 
                    invoice_number = ?, 
                    approved_by = ?, 
                    approved_date = NOW() 
                WHERE order_id = ?
            ");
            $update_order->bind_param("sii", $invoice_number, $admin_id, $order_id);
            $update_order->execute();
            
            // Update all availability records for this order
            $update_avail = $conn->prepare("
                UPDATE Availability 
                SET status = 'approved' 
                WHERE order_id = ?
            ");
            $update_avail->bind_param("i", $order_id);
            $update_avail->execute();
            
            $conn->commit();
            
            // Send approval email
            $email_result = sendApprovalEmail($order_id, $conn, $lang);
            
            $message = $lang === 'cs' ? 'Objednávka byla schválena' : 'Order has been approved';
            if ($email_result['success']) {
                $message .= $lang === 'cs' ? '. Email byl odeslán zákazníkovi.' : '. Email sent to customer.';
            } else {
                $message .= $lang === 'cs' ? '. Varování: Email se nepodařilo odeslat.' : '. Warning: Failed to send email.';
            }
            
            echo json_encode([
                'success' => true, 
                'message' => $message,
                'invoice_number' => $invoice_number,
                'email_sent' => $email_result['success']
            ]);
            
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode([
                'success' => false, 
                'message' => $lang === 'cs' ? 'Chyba při schvalování: ' . $e->getMessage() : 'Error approving order: ' . $e->getMessage()
            ]);
        }
        
    } elseif ($action === 'deny') {
        $denial_reason = trim($_POST['denial_reason'] ?? '');
        
        if (empty($denial_reason)) {
            echo json_encode([
                'success' => false, 
                'message' => $lang === 'cs' ? 'Důvod zamítnutí je povinný' : 'Denial reason is required'
            ]);
            exit();
        }
        
        $conn->begin_transaction();
        
        try {
            // Update order status
            $update_order = $conn->prepare("
                UPDATE Orders 
                SET status = 'denied', 
                    denial_reason = ?, 
                    approved_by = ?, 
                    approved_date = NOW() 
                WHERE order_id = ?
            ");
            $update_order->bind_param("sii", $denial_reason, $admin_id, $order_id);
            $update_order->execute();
            
            // Update all availability records for this order
            $update_avail = $conn->prepare("
                UPDATE Availability 
                SET status = 'denied' 
                WHERE order_id = ?
            ");
            $update_avail->bind_param("i", $order_id);
            $update_avail->execute();
            
            $conn->commit();
            
            // Send denial email
            $email_result = sendDenialEmail($order_id, $denial_reason, $conn, $lang);
            
            $message = $lang === 'cs' ? 'Objednávka byla zamítnuta' : 'Order has been denied';
            if ($email_result['success']) {
                $message .= $lang === 'cs' ? '. Email byl odeslán zákazníkovi.' : '. Email sent to customer.';
            } else {
                $message .= $lang === 'cs' ? '. Varování: Email se nepodařilo odeslat.' : '. Warning: Failed to send email.';
            }
            
            echo json_encode([
                'success' => true, 
                'message' => $message,
                'email_sent' => $email_result['success']
            ]);
            
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode([
                'success' => false, 
                'message' => $lang === 'cs' ? 'Chyba při zamítání: ' . $e->getMessage() : 'Error denying order: ' . $e->getMessage()
            ]);
        }
        
    } else {
        echo json_encode([
            'success' => false, 
            'message' => $lang === 'cs' ? 'Neplatná akce' : 'Invalid action'
        ]);
    }
    
    exit();
}

// Invalid request
echo json_encode([
    'success' => false, 
    'message' => $lang === 'cs' ? 'Neplatný požadavek' : 'Invalid request'
]);
?>
