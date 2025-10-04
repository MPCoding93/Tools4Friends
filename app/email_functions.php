<?php
/**
 * Email Functions for Order Notifications
 */

/**
 * Generate invoice PDF and return file path
 */
function generateInvoicePDF($order_id, $conn) {
    // Check if FPDF is available
    $fpdf_path = __DIR__ . '/../vendor/fpdf/fpdf.php';
    if (!file_exists($fpdf_path)) {
        return ['success' => false, 'message' => 'FPDF library not found'];
    }
    
    require_once $fpdf_path;
    
    // Fetch order details (same as generate_invoice.php)
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
        return ['success' => false, 'message' => 'Order not found'];
    }
    
    // Fetch order items
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
    
    // Create PDF (simplified version)
    $pdf = new FPDF();
    $pdf->AddPage();
    $pdf->SetFont('Arial', 'B', 16);
    $pdf->Cell(0, 10, 'INVOICE / FAKTURA', 0, 1, 'C');
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 8, 'Invoice Number: ' . $order['invoice_number'], 0, 1, 'C');
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(0, 6, 'Date: ' . date('d.m.Y', strtotime($order['approved_date'])), 0, 1, 'C');
    $pdf->Ln(10);
    
    // Company and Customer info
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->Cell(95, 7, 'From / Od:', 0, 0);
    $pdf->Cell(95, 7, 'To / Pro:', 0, 1);
    $pdf->SetFont('Arial', '', 10);
    
    $y_start = $pdf->GetY();
    $pdf->MultiCell(95, 6, 
        ($settings['company_name'] ?? 'Tools4Friends') . "\n" .
        ($settings['company_email'] ?? '') . "\n" .
        ($settings['company_phone'] ?? ''), 
        0, 'L');
    
    $pdf->SetXY(105, $y_start);
    $pdf->MultiCell(95, 6,
        $order['firstname'] . ' ' . $order['lastname'] . "\n" .
        $order['email'] . "\n" .
        ($order['phone'] ?? ''),
        0, 'L');
    
    $pdf->Ln(5);
    
    // Items table
    $pdf->SetFillColor(31, 45, 90);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(80, 8, 'Tool / Nastroj', 1, 0, 'L', true);
    $pdf->Cell(40, 8, 'Period / Obdobi', 1, 0, 'C', true);
    $pdf->Cell(30, 8, 'Fee / Poplatek', 1, 0, 'R', true);
    $pdf->Cell(30, 8, 'Deposit / Zaloha', 1, 1, 'R', true);
    
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFont('Arial', '', 9);
    
    foreach ($items as $item) {
        $tool_name = $item['name'];
        $period = date('d.m.Y', strtotime($item['start_date'])) . ' - ' . date('d.m.Y', strtotime($item['end_date']));
        
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
    
    // Save PDF to temp file
    $temp_dir = __DIR__ . '/../public/uploads/temp/';
    if (!is_dir($temp_dir)) {
        mkdir($temp_dir, 0755, true);
    }
    
    $filename = 'Invoice_' . $order['invoice_number'] . '_' . time() . '.pdf';
    $filepath = $temp_dir . $filename;
    
    $pdf->Output('F', $filepath);
    
    return ['success' => true, 'filepath' => $filepath, 'filename' => 'Invoice_' . $order['invoice_number'] . '.pdf'];
}

/**
 * Send order approval email with invoice
 */
function sendApprovalEmail($order_id, $conn, $lang = 'en') {
    // Fetch order details
    $order_query = $conn->prepare("
        SELECT 
            o.*,
            u.firstname,
            u.lastname,
            u.email
        FROM Orders o
        JOIN Users u ON o.user_id = u.user_id
        WHERE o.order_id = ?
    ");
    $order_query->bind_param("i", $order_id);
    $order_query->execute();
    $order = $order_query->get_result()->fetch_assoc();
    
    if (!$order) {
        return ['success' => false, 'message' => 'Order not found'];
    }
    
    // Fetch order items
    $items_query = $conn->prepare("
        SELECT 
            t.name,
            t.name_cs,
            a.start_date,
            a.end_date,
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
    
    // Build email content
    $subject = $lang === 'cs' 
        ? 'Vaše objednávka byla schválena - ' . $order['invoice_number']
        : 'Your order has been approved - ' . $order['invoice_number'];
    
    $message = buildApprovalEmailHTML($order, $items, $settings, $lang);
    
    // Generate PDF invoice
    $pdf_result = generateInvoicePDF($order_id, $conn);
    
    if (!$pdf_result['success']) {
        // Send email without attachment if PDF generation fails
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: " . ($settings['company_email'] ?? 'noreply@tools4friends.com') . "\r\n";
        
        $sent = mail($order['email'], $subject, $message, $headers);
        
        if ($sent) {
            return ['success' => true, 'message' => 'Email sent (without PDF attachment)'];
        } else {
            return ['success' => false, 'message' => 'Failed to send email'];
        }
    }
    
    // Prepare email with PDF attachment
    $filepath = $pdf_result['filepath'];
    $filename = $pdf_result['filename'];
    
    // Read PDF file
    $file_content = file_get_contents($filepath);
    $file_content = chunk_split(base64_encode($file_content));
    
    // Generate boundary
    $boundary = md5(time());
    
    // Email headers with attachment
    $headers = "From: " . ($settings['company_email'] ?? 'noreply@tools4friends.com') . "\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: multipart/mixed; boundary=\"{$boundary}\"\r\n";
    
    // Email body
    $email_body = "--{$boundary}\r\n";
    $email_body .= "Content-Type: text/html; charset=UTF-8\r\n";
    $email_body .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
    $email_body .= $message . "\r\n\r\n";
    
    // PDF attachment
    $email_body .= "--{$boundary}\r\n";
    $email_body .= "Content-Type: application/pdf; name=\"{$filename}\"\r\n";
    $email_body .= "Content-Transfer-Encoding: base64\r\n";
    $email_body .= "Content-Disposition: attachment; filename=\"{$filename}\"\r\n\r\n";
    $email_body .= $file_content . "\r\n\r\n";
    $email_body .= "--{$boundary}--";
    
    // Send email
    $sent = mail($order['email'], $subject, $email_body, $headers);
    
    // Delete temporary PDF file
    if (file_exists($filepath)) {
        unlink($filepath);
    }
    
    if ($sent) {
        return ['success' => true, 'message' => 'Email sent successfully'];
    } else {
        return ['success' => false, 'message' => 'Failed to send email'];
    }
}

/**
 * Build approval email HTML
 */
function buildApprovalEmailHTML($order, $items, $settings, $lang) {
    $company_name = $settings['company_name'] ?? 'Tools4Friends';
    $total_to_pay = $order['total_amount'] + $order['total_deposit'];
    
    $greeting = $lang === 'cs' 
        ? "Dobrý den " . htmlspecialchars($order['firstname']) . ","
        : "Hello " . htmlspecialchars($order['firstname']) . ",";
    
    $intro = $lang === 'cs'
        ? "Vaše objednávka byla schválena! Níže naleznete detaily vaší objednávky."
        : "Your order has been approved! Below you will find the details of your order.";
    
    $html = "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background-color: #1F2D5A; color: white; padding: 20px; text-align: center; }
            .content { background-color: #f9f9f9; padding: 20px; }
            .order-details { background-color: white; padding: 15px; margin: 15px 0; border-radius: 5px; }
            .item { border-bottom: 1px solid #ddd; padding: 10px 0; }
            .item:last-child { border-bottom: none; }
            .total { background-color: #1F2D5A; color: white; padding: 15px; text-align: center; font-size: 1.2em; font-weight: bold; }
            .payment-info { background-color: #fff3cd; padding: 15px; margin: 15px 0; border-left: 4px solid #ffc107; }
            .footer { text-align: center; padding: 20px; color: #666; font-size: 0.9em; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>{$company_name}</h1>
                <p>" . ($lang === 'cs' ? 'Potvrzení objednávky' : 'Order Confirmation') . "</p>
            </div>
            
            <div class='content'>
                <p>{$greeting}</p>
                <p>{$intro}</p>
                
                <div class='order-details'>
                    <h2>" . ($lang === 'cs' ? 'Číslo faktury' : 'Invoice Number') . ": {$order['invoice_number']}</h2>
                    <p><strong>" . ($lang === 'cs' ? 'Datum schválení' : 'Approval Date') . ":</strong> " . date('d.m.Y', strtotime($order['approved_date'])) . "</p>
                    
                    <h3>" . ($lang === 'cs' ? 'Objednané nástroje' : 'Ordered Tools') . ":</h3>";
    
    foreach ($items as $item) {
        $tool_name = $lang === 'cs' && !empty($item['name_cs']) ? $item['name_cs'] : $item['name'];
        $period = date('d.m.Y', strtotime($item['start_date'])) . ' - ' . date('d.m.Y', strtotime($item['end_date']));
        
        $html .= "
                    <div class='item'>
                        <strong>{$tool_name}</strong><br>
                        " . ($lang === 'cs' ? 'Období' : 'Period') . ": {$period}<br>
                        " . ($lang === 'cs' ? 'Poplatek' : 'Fee') . ": " . number_format($item['manipulation_fee'], 2) . " Kč<br>
                        " . ($lang === 'cs' ? 'Záloha' : 'Deposit') . ": " . number_format($item['deposit'], 2) . " Kč
                    </div>";
    }
    
    $html .= "
                </div>
                
                <div class='total'>
                    " . ($lang === 'cs' ? 'CELKEM K ZAPLACENÍ' : 'TOTAL TO PAY') . ": " . number_format($total_to_pay, 2) . " Kč
                </div>
                
                <div class='payment-info'>
                    <h3>" . ($lang === 'cs' ? 'Platební instrukce' : 'Payment Instructions') . ":</h3>";
    
    if (!empty($settings['bank_name'])) {
        $html .= "<p><strong>" . ($lang === 'cs' ? 'Banka' : 'Bank') . ":</strong> {$settings['bank_name']}</p>";
    }
    if (!empty($settings['bank_account'])) {
        $html .= "<p><strong>" . ($lang === 'cs' ? 'Číslo účtu' : 'Account Number') . ":</strong> {$settings['bank_account']}</p>";
    }
    if (!empty($settings['bank_iban'])) {
        $html .= "<p><strong>IBAN:</strong> {$settings['bank_iban']}</p>";
    }
    
    $html .= "
                    <p>" . ($lang === 'cs' 
                        ? 'Prosím proveďte platbu do 7 dnů od obdržení této faktury.'
                        : 'Please make the payment within 7 days of receiving this invoice.') . "</p>
                </div>
                
                <p>" . ($lang === 'cs'
                    ? 'Faktura je připojena k tomuto emailu jako PDF soubor.'
                    : 'The invoice is attached to this email as a PDF file.') . "</p>
                
                <p>" . ($lang === 'cs'
                    ? 'Děkujeme za vaši objednávku!'
                    : 'Thank you for your order!') . "</p>
            </div>
            
            <div class='footer'>
                <p>{$company_name}</p>
                <p>" . ($settings['company_email'] ?? '') . " | " . ($settings['company_phone'] ?? '') . "</p>
            </div>
        </div>
    </body>
    </html>";
    
    return $html;
}

/**
 * Send order denial email
 */
function sendDenialEmail($order_id, $denial_reason, $conn, $lang = 'en') {
    // Fetch order details
    $order_query = $conn->prepare("
        SELECT 
            o.*,
            u.firstname,
            u.lastname,
            u.email
        FROM Orders o
        JOIN Users u ON o.user_id = u.user_id
        WHERE o.order_id = ?
    ");
    $order_query->bind_param("i", $order_id);
    $order_query->execute();
    $order = $order_query->get_result()->fetch_assoc();
    
    if (!$order) {
        return ['success' => false, 'message' => 'Order not found'];
    }
    
    // Fetch order items
    $items_query = $conn->prepare("
        SELECT 
            t.name,
            t.name_cs,
            a.start_date,
            a.end_date
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
    
    // Build email content
    $subject = $lang === 'cs' 
        ? 'Vaše objednávka byla zamítnuta - Objednávka #' . $order_id
        : 'Your order has been denied - Order #' . $order_id;
    
    $message = buildDenialEmailHTML($order, $items, $denial_reason, $settings, $lang);
    
    // Email headers
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: " . ($settings['company_email'] ?? 'noreply@tools4friends.com') . "\r\n";
    
    // Send email
    $sent = mail($order['email'], $subject, $message, $headers);
    
    if ($sent) {
        return ['success' => true, 'message' => 'Email sent successfully'];
    } else {
        return ['success' => false, 'message' => 'Failed to send email'];
    }
}

/**
 * Build denial email HTML
 */
function buildDenialEmailHTML($order, $items, $denial_reason, $settings, $lang) {
    $company_name = $settings['company_name'] ?? 'Tools4Friends';
    
    $greeting = $lang === 'cs' 
        ? "Dobrý den " . htmlspecialchars($order['firstname']) . ","
        : "Hello " . htmlspecialchars($order['firstname']) . ",";
    
    $apology = $lang === 'cs'
        ? "Omlouváme se, ale vaše objednávka nemohla být schválena."
        : "We apologize, but your order could not be approved.";
    
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
            .order-details { background-color: white; padding: 15px; margin: 15px 0; border-radius: 5px; }
            .item { border-bottom: 1px solid #ddd; padding: 10px 0; }
            .item:last-child { border-bottom: none; }
            .reason-box { background-color: #f8d7da; padding: 15px; margin: 15px 0; border-left: 4px solid #dc3545; border-radius: 4px; }
            .footer { text-align: center; padding: 20px; color: #666; font-size: 0.9em; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>{$company_name}</h1>
                <p>" . ($lang === 'cs' ? 'Oznámení o objednávce' : 'Order Notification') . "</p>
            </div>
            
            <div class='content'>
                <p>{$greeting}</p>
                <p>{$apology}</p>
                
                <div class='reason-box'>
                    <h3>" . ($lang === 'cs' ? 'Důvod zamítnutí' : 'Reason for Denial') . ":</h3>
                    <p>" . htmlspecialchars($denial_reason) . "</p>
                </div>
                
                <div class='order-details'>
                    <h3>" . ($lang === 'cs' ? 'Detaily objednávky' : 'Order Details') . ":</h3>
                    <p><strong>" . ($lang === 'cs' ? 'Číslo objednávky' : 'Order Number') . ":</strong> #{$order['order_id']}</p>
                    <p><strong>" . ($lang === 'cs' ? 'Datum objednávky' : 'Order Date') . ":</strong> " . date('d.m.Y', strtotime($order['order_date'])) . "</p>
                    
                    <h4>" . ($lang === 'cs' ? 'Požadované nástroje' : 'Requested Tools') . ":</h4>";
    
    foreach ($items as $item) {
        $tool_name = $lang === 'cs' && !empty($item['name_cs']) ? $item['name_cs'] : $item['name'];
        $period = date('d.m.Y', strtotime($item['start_date'])) . ' - ' . date('d.m.Y', strtotime($item['end_date']));
        
        $html .= "
                    <div class='item'>
                        <strong>{$tool_name}</strong><br>
                        " . ($lang === 'cs' ? 'Období' : 'Period') . ": {$period}
                    </div>";
    }
    
    $html .= "
                </div>
                
                <p>" . ($lang === 'cs'
                    ? 'Pokud máte jakékoli dotazy nebo byste chtěli objednat znovu, neváhejte nás kontaktovat.'
                    : 'If you have any questions or would like to place a new order, please do not hesitate to contact us.') . "</p>
                
                <p>" . ($lang === 'cs'
                    ? 'Děkujeme za pochopení.'
                    : 'Thank you for your understanding.') . "</p>
            </div>
            
            <div class='footer'>
                <p>{$company_name}</p>
                <p>" . ($settings['company_email'] ?? '') . " | " . ($settings['company_phone'] ?? '') . "</p>
            </div>
        </div>
    </body>
    </html>";
    
    return $html;
}
?>
