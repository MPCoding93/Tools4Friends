<?php
/**
 * Invoice Generation System
 * Generates PDF invoices for approved orders
 */

require_once __DIR__ . '/../app/security.php';
require_once __DIR__ . '/../app/db_connect.php';

// Check if FPDF is available
$fpdf_path = __DIR__ . '/../vendor/fpdf/fpdf.php';
if (!file_exists($fpdf_path)) {
    die('FPDF library not found. Please install FPDF library in: ' . $fpdf_path);
}

require_once $fpdf_path;

startSecureSession();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['admin']) || $_SESSION['admin'] != 1) {
    die('Unauthorized access');
}

$order_id = intval($_GET['order_id'] ?? 0);

if ($order_id === 0) {
    die('Invalid order ID');
}

// Fetch order details
$order_query = $conn->prepare("
    SELECT 
        o.*,
        u.firstname,
        u.lastname,
        u.email,
        u.phone
    FROM Orders o
    JOIN Users u ON o.user_id = u.user_id
    WHERE o.order_id = ? AND o.status = 'approved'
");
$order_query->bind_param("i", $order_id);
$order_query->execute();
$order = $order_query->get_result()->fetch_assoc();

if (!$order) {
    die('Order not found or not approved');
}

// Fetch order items (tools)
$items_query = $conn->prepare("
    SELECT 
        a.start_date,
        a.end_date,
        t.name,
        t.name_cs,
        t.manipulation_fee,
        t.deposit
    FROM Availability a
    JOIN Tools t ON a.tool_id = t.tool_id
    WHERE a.order_id = ?
");
$items_query->bind_param("i", $order_id);
$items_query->execute();
$items_result = $items_query->get_result();

$items = [];
while ($item = $items_result->fetch_assoc()) {
    $items[] = $item;
}

// Fetch company settings
$settings_query = $conn->query("SELECT * FROM Company_Settings LIMIT 1");
$settings = $settings_query->fetch_assoc();

// Create PDF
class Invoice extends FPDF
{
    private $settings;
    private $order;
    
    function __construct($settings, $order) {
        parent::__construct();
        $this->settings = $settings;
        $this->order = $order;
    }
    
    // Page header
    function Header() {
        // Logo/Banner
        $banner_path = __DIR__ . '/images/banners/tools4friends_dark_Banner_2000x400.png';
        if (file_exists($banner_path)) {
            $this->Image($banner_path, 10, 6, 190);
            $this->Ln(30);
        } else {
            $this->SetFont('Arial', 'B', 20);
            $this->Cell(0, 10, $this->settings['company_name'] ?? 'Tools4Friends', 0, 1, 'C');
            $this->Ln(10);
        }
    }
    
    // Page footer
    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, 'Page ' . $this->PageNo(), 0, 0, 'C');
    }
}

// Initialize PDF
$pdf = new Invoice($settings, $order);
$pdf->AddPage();
$pdf->SetFont('Arial', '', 10);

// Invoice title and number
$pdf->SetFont('Arial', 'B', 16);
$pdf->Cell(0, 10, 'INVOICE / FAKTURA', 0, 1, 'C');
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 8, 'Invoice Number: ' . $order['invoice_number'], 0, 1, 'C');
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(0, 6, 'Date: ' . date('d.m.Y', strtotime($order['approved_date'])), 0, 1, 'C');
$pdf->Ln(10);

// Company and Customer Information (Two columns)
$pdf->SetFont('Arial', 'B', 11);
$pdf->Cell(95, 7, 'From / Od:', 0, 0);
$pdf->Cell(95, 7, 'To / Pro:', 0, 1);

$pdf->SetFont('Arial', '', 10);

// Company info (left column)
$y_start = $pdf->GetY();
$pdf->MultiCell(95, 6, 
    ($settings['company_name'] ?? 'Tools4Friends') . "\n" .
    ($settings['company_email'] ?? '') . "\n" .
    ($settings['company_phone'] ?? ''), 
    0, 'L');

// Customer info (right column)
$pdf->SetXY(105, $y_start);
$pdf->MultiCell(95, 6,
    $order['firstname'] . ' ' . $order['lastname'] . "\n" .
    $order['email'] . "\n" .
    ($order['phone'] ?? ''),
    0, 'L');

$pdf->Ln(5);

// Items table header
$pdf->SetFillColor(31, 45, 90);
$pdf->SetTextColor(255, 255, 255);
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(80, 8, 'Tool / Nastroj', 1, 0, 'L', true);
$pdf->Cell(40, 8, 'Period / Obdobi', 1, 0, 'C', true);
$pdf->Cell(30, 8, 'Fee / Poplatek', 1, 0, 'R', true);
$pdf->Cell(30, 8, 'Deposit / Zaloha', 1, 1, 'R', true);

// Items
$pdf->SetTextColor(0, 0, 0);
$pdf->SetFont('Arial', '', 9);

foreach ($items as $item) {
    $tool_name = $item['name'];
    $period = date('d.m.Y', strtotime($item['start_date'])) . ' - ' . date('d.m.Y', strtotime($item['end_date']));
    
    // Calculate days
    $start = new DateTime($item['start_date']);
    $end = new DateTime($item['end_date']);
    $days = $start->diff($end)->days + 1;
    
    $pdf->Cell(80, 7, $tool_name, 1, 0, 'L');
    $pdf->Cell(40, 7, $period . ' (' . $days . 'd)', 1, 0, 'C');
    $pdf->Cell(30, 7, number_format($item['manipulation_fee'], 2) . ' CZK', 1, 0, 'R');
    $pdf->Cell(30, 7, number_format($item['deposit'], 2) . ' CZK', 1, 1, 'R');
}

// Totals
$pdf->Ln(2);
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(120, 7, 'Total Fee / Celkovy Poplatek:', 0, 0, 'R');
$pdf->Cell(30, 7, number_format($order['total_amount'], 2) . ' CZK', 1, 1, 'R');

$pdf->Cell(120, 7, 'Total Deposit / Celkova Zaloha:', 0, 0, 'R');
$pdf->Cell(30, 7, number_format($order['total_deposit'], 2) . ' CZK', 1, 1, 'R');

$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(120, 8, 'TOTAL TO PAY / CELKEM K ZAPLACENI:', 0, 0, 'R');
$pdf->SetFillColor(31, 45, 90);
$pdf->SetTextColor(255, 255, 255);
$pdf->Cell(30, 8, number_format($order['total_amount'] + $order['total_deposit'], 2) . ' CZK', 1, 1, 'R', true);
$pdf->SetTextColor(0, 0, 0);

$pdf->Ln(10);

// Payment Instructions
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 8, 'Payment Instructions / Platebni Instrukce', 0, 1, 'L');
$pdf->SetFont('Arial', '', 10);

if (!empty($settings['bank_name'])) {
    $pdf->Cell(50, 6, 'Bank / Banka:', 0, 0);
    $pdf->Cell(0, 6, $settings['bank_name'], 0, 1);
}

if (!empty($settings['bank_account'])) {
    $pdf->Cell(50, 6, 'Account / Ucet:', 0, 0);
    $pdf->Cell(0, 6, $settings['bank_account'], 0, 1);
}

if (!empty($settings['bank_iban'])) {
    $pdf->Cell(50, 6, 'IBAN:', 0, 0);
    $pdf->Cell(0, 6, $settings['bank_iban'], 0, 1);
}

if (!empty($settings['bank_swift'])) {
    $pdf->Cell(50, 6, 'SWIFT/BIC:', 0, 0);
    $pdf->Cell(0, 6, $settings['bank_swift'], 0, 1);
}

$pdf->Ln(5);

// QR Code
if (!empty($settings['qr_code_image'])) {
    $qr_path = __DIR__ . '/' . $settings['qr_code_image'];
    if (file_exists($qr_path)) {
        $pdf->Cell(0, 6, 'Payment QR Code / QR Kod pro Platbu:', 0, 1);
        $pdf->Image($qr_path, 10, $pdf->GetY(), 60);
        $pdf->Ln(65);
    }
}

// Thank you message
$pdf->Ln(5);
$pdf->SetFont('Arial', 'I', 10);
$pdf->MultiCell(0, 6, 
    "Thank you for your order! Please make the payment within 7 days.\n" .
    "Dekujeme za vasi objednavku! Prosim provedte platbu do 7 dni.",
    0, 'C');

// Output PDF
$pdf->Output('D', 'Invoice_' . $order['invoice_number'] . '.pdf');
?>
