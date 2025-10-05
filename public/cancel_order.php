<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../app/security.php';
require_once __DIR__ . '/../app/db_connect.php';
require_once __DIR__ . '/../app/email_functions.php';

startSecureSession();

// Set JSON header
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false, 
        'message' => 'Not authorized'
    ]);
    exit;
}

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
    exit;
}

$lang = $_POST['lang'] ?? 'en';
$availability_id = intval($_POST['availability_id'] ?? 0);
$user_id = $_SESSION['user_id'];

if ($availability_id <= 0) {
    echo json_encode([
        'success' => false,
        'message' => $lang === 'cs' ? 'Neplatné ID objednávky.' : 'Invalid order ID.'
    ]);
    exit;
}

// Fetch availability details and verify it belongs to the user
$verify_stmt = $conn->prepare("
    SELECT 
        a.availability_id,
        a.start_date,
        a.end_date,
        a.status,
        a.tool_id,
        a.order_id,
        t.name,
        t.name_cs,
        t.ownerID,
        u_owner.email AS owner_email,
        u_owner.firstname AS owner_firstname,
        u_owner.lastname AS owner_lastname,
        u_renter.email AS renter_email,
        u_renter.firstname AS renter_firstname,
        u_renter.lastname AS renter_lastname
    FROM Availability a
    JOIN Tools t ON a.tool_id = t.tool_id
    JOIN Users u_owner ON t.ownerID = u_owner.ownerID
    JOIN Users u_renter ON a.user_id = u_renter.user_id
    WHERE a.availability_id = ? AND a.user_id = ?
");
$verify_stmt->bind_param("ii", $availability_id, $user_id);
$verify_stmt->execute();
$result = $verify_stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode([
        'success' => false, 
        'message' => $lang === 'cs' 
            ? 'Objednávka nebyla nalezena nebo nemáte oprávnění.' 
            : 'Order not found or not authorized.'
    ]);
    exit;
}

$availability = $result->fetch_assoc();

// Check if order is in approved status
if ($availability['status'] !== 'approved') {
    echo json_encode([
        'success' => false,
        'message' => $lang === 'cs'
            ? 'Lze zrušit pouze schválené objednávky.'
            : 'Only approved orders can be cancelled.'
    ]);
    exit;
}

// Check if cancellation is allowed (more than 24 hours before start)
$hours_until_start = (strtotime($availability['start_date']) - time()) / 3600;

if ($hours_until_start <= 24) {
    echo json_encode([
        'success' => false,
        'message' => $lang === 'cs'
            ? 'Zrušení není možné. Objednávku lze zrušit pouze více než 24 hodin před začátkem.'
            : 'Cancellation not allowed. Orders can only be cancelled more than 24 hours before start.'
    ]);
    exit;
}

// Update availability status to 'cancelled'
$update_stmt = $conn->prepare("UPDATE Availability SET status = 'cancelled' WHERE availability_id = ?");
$update_stmt->bind_param("i", $availability_id);

if (!$update_stmt->execute()) {
    echo json_encode([
        'success' => false,
        'message' => $lang === 'cs'
            ? 'Chyba při rušení objednávky.'
            : 'Error cancelling order.'
    ]);
    exit;
}

// Fetch company settings for email
$settings_query = $conn->query("SELECT * FROM Company_Settings LIMIT 1");
$settings = $settings_query->fetch_assoc();
$company_email = $settings['company_email'] ?? 'noreply@tools4friends.com';
$company_name = $settings['company_name'] ?? 'Tools4Friends';

// Prepare email content
$tool_name = $lang === 'cs' && !empty($availability['name_cs']) 
    ? $availability['name_cs'] 
    : $availability['name'];

$period = date('d.m.Y', strtotime($availability['start_date'])) . ' - ' . 
          date('d.m.Y', strtotime($availability['end_date']));

// Send email to user (renter)
$user_subject = $lang === 'cs'
    ? 'Potvrzení zrušení objednávky - ' . $tool_name
    : 'Order Cancellation Confirmation - ' . $tool_name;

$user_message = buildCancellationEmailHTML(
    $availability['renter_firstname'],
    $tool_name,
    $period,
    $company_name,
    $company_email,
    $lang,
    'renter'
);

$user_headers = "MIME-Version: 1.0\r\n";
$user_headers .= "Content-type:text/html;charset=UTF-8\r\n";
$user_headers .= "From: {$company_email}\r\n";

mail($availability['renter_email'], $user_subject, $user_message, $user_headers);

// Send email to company
$company_subject = $lang === 'cs'
    ? 'Zrušení objednávky - ' . $tool_name
    : 'Order Cancellation - ' . $tool_name;

$company_message = buildCancellationEmailHTML(
    'Admin',
    $tool_name,
    $period,
    $company_name,
    $company_email,
    $lang,
    'company',
    $availability['renter_firstname'] . ' ' . $availability['renter_lastname']
);

$company_headers = "MIME-Version: 1.0\r\n";
$company_headers .= "Content-type:text/html;charset=UTF-8\r\n";
$company_headers .= "From: {$company_email}\r\n";

mail($company_email, $company_subject, $company_message, $company_headers);

// Return success response
echo json_encode([
    'success' => true,
    'message' => $lang === 'cs'
        ? 'Objednávka byla úspěšně zrušena. Obdržíte potvrzovací email.'
        : 'Order successfully cancelled. You will receive a confirmation email.'
]);

/**
 * Build cancellation email HTML
 */
function buildCancellationEmailHTML($recipient_name, $tool_name, $period, $company_name, $company_email, $lang, $recipient_type, $renter_name = null) {
    $greeting = $lang === 'cs' 
        ? "Dobrý den " . htmlspecialchars($recipient_name) . ","
        : "Hello " . htmlspecialchars($recipient_name) . ",";
    
    if ($recipient_type === 'renter') {
        $intro = $lang === 'cs'
            ? "Vaše objednávka byla úspěšně zrušena."
            : "Your order has been successfully cancelled.";
    } else {
        $intro = $lang === 'cs'
            ? "Objednávka byla zrušena uživatelem."
            : "An order has been cancelled by the user.";
    }
    
    $html = "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background-color: #dc3545; color: white; padding: 20px; text-align: center; }
            .content { background-color: #f9f9f9; padding: 20px; }
            .order-details { background-color: white; padding: 15px; margin: 15px 0; border-radius: 5px; border-left: 4px solid #dc3545; }
            .footer { text-align: center; padding: 20px; color: #666; font-size: 0.9em; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>{$company_name}</h1>
                <p>" . ($lang === 'cs' ? 'Zrušení objednávky' : 'Order Cancellation') . "</p>
            </div>
            
            <div class='content'>
                <p>{$greeting}</p>
                <p>{$intro}</p>
                
                <div class='order-details'>
                    <h3>" . ($lang === 'cs' ? 'Detaily objednávky' : 'Order Details') . ":</h3>";
    
    if ($recipient_type === 'company' && $renter_name) {
        $html .= "<p><strong>" . ($lang === 'cs' ? 'Zákazník' : 'Customer') . ":</strong> " . htmlspecialchars($renter_name) . "</p>";
    }
    
    $html .= "
                    <p><strong>" . ($lang === 'cs' ? 'Nástroj' : 'Tool') . ":</strong> " . htmlspecialchars($tool_name) . "</p>
                    <p><strong>" . ($lang === 'cs' ? 'Období' : 'Period') . ":</strong> {$period}</p>
                    <p><strong>" . ($lang === 'cs' ? 'Datum zrušení' : 'Cancellation Date') . ":</strong> " . date('d.m.Y H:i') . "</p>
                </div>";
    
    if ($recipient_type === 'renter') {
        $html .= "
                <p>" . ($lang === 'cs'
                    ? 'Pokud máte jakékoli dotazy, neváhejte nás kontaktovat.'
                    : 'If you have any questions, please do not hesitate to contact us.') . "</p>";
    }
    
    $html .= "
            </div>
            
            <div class='footer'>
                <p>{$company_name}</p>
                <p>{$company_email}</p>
            </div>
        </div>
    </body>
    </html>";
    
    return $html;
}
?>
