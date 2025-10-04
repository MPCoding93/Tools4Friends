<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../app/security.php';
require_once __DIR__ . '/../app/db_connect.php';

startSecureSession();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['admin']) || $_SESSION['admin'] != 1) {
    header("Location: login.php?lang=" . ($_GET['lang'] ?? 'en'));
    exit();
}

$lang = $_GET['lang'] ?? 'en';
$success_message = '';
$error_message = '';

// Fetch current settings
$settings_query = $conn->prepare("SELECT * FROM Company_Settings LIMIT 1");
$settings_query->execute();
$settings = $settings_query->get_result()->fetch_assoc();

// If no settings exist, create default
if (!$settings) {
    $conn->query("INSERT INTO Company_Settings (company_name) VALUES ('Tools4Friends')");
    $settings = ['company_name' => 'Tools4Friends', 'company_email' => '', 'company_phone' => '', 
                 'bank_name' => '', 'bank_account' => '', 'bank_iban' => '', 'bank_swift' => '', 'qr_code_image' => ''];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $error_message = $lang === 'cs' ? 'Bezpečnostní ověření selhalo' : 'Security validation failed';
    } else {
        $company_name = trim($_POST['company_name']);
        $company_email = trim($_POST['company_email']);
        $company_phone = trim($_POST['company_phone']);
        $bank_name = trim($_POST['bank_name']);
        $bank_account = trim($_POST['bank_account']);
        $bank_iban = trim($_POST['bank_iban']);
        $bank_swift = trim($_POST['bank_swift']);
        
        // Validate email if provided
        if (!empty($company_email) && !validateEmail($company_email)) {
            $error_message = $lang === 'cs' ? 'Neplatná emailová adresa' : 'Invalid email address';
        } else {
            // Handle QR code upload
            $qr_code_path = $settings['qr_code_image'];
            
            if (isset($_FILES['qr_code']) && $_FILES['qr_code']['error'] === UPLOAD_ERR_OK) {
                $upload_result = validateFileUpload($_FILES['qr_code']);
                
                if ($upload_result['valid']) {
                    // Create uploads directory if it doesn't exist
                    $upload_dir = __DIR__ . '/uploads/qr_codes/';
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }
                    
                    // Generate secure filename
                    $filename = 'qr_code_' . time() . '.' . $upload_result['ext'];
                    $upload_path = $upload_dir . $filename;
                    
                    if (move_uploaded_file($_FILES['qr_code']['tmp_name'], $upload_path)) {
                        // Delete old QR code if exists
                        if (!empty($settings['qr_code_image']) && file_exists(__DIR__ . '/' . $settings['qr_code_image'])) {
                            unlink(__DIR__ . '/' . $settings['qr_code_image']);
                        }
                        $qr_code_path = 'uploads/qr_codes/' . $filename;
                    } else {
                        $error_message = $lang === 'cs' ? 'Chyba při nahrávání QR kódu' : 'Error uploading QR code';
                    }
                } else {
                    $error_message = $upload_result['error'];
                }
            }
            
            if (empty($error_message)) {
                // Update settings
                $update_query = $conn->prepare("
                    UPDATE Company_Settings 
                    SET company_name = ?, 
                        company_email = ?, 
                        company_phone = ?, 
                        bank_name = ?, 
                        bank_account = ?, 
                        bank_iban = ?, 
                        bank_swift = ?,
                        qr_code_image = ?
                    WHERE setting_id = ?
                ");
                
                $update_query->bind_param("ssssssssi", 
                    $company_name, 
                    $company_email, 
                    $company_phone, 
                    $bank_name, 
                    $bank_account, 
                    $bank_iban, 
                    $bank_swift,
                    $qr_code_path,
                    $settings['setting_id']
                );
                
                if ($update_query->execute()) {
                    $success_message = $lang === 'cs' ? 'Nastavení bylo úspěšně uloženo' : 'Settings saved successfully';
                    // Refresh settings
                    $settings_query->execute();
                    $settings = $settings_query->get_result()->fetch_assoc();
                } else {
                    $error_message = $lang === 'cs' ? 'Chyba při ukládání nastavení' : 'Error saving settings';
                }
            }
        }
    }
}

// Navbar variables
$loggedIn = true;
$fullName = htmlspecialchars($_SESSION['firstname'] . ' ' . $_SESSION['lastname']);

// Generate CSRF token
$csrf_token = generateCSRFToken();
?>

<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo ($lang === 'cs' ? 'Nastavení' : 'Settings'); ?> - Tools4Friends</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="icon" href="favicon/favicon-dark.ico" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Libre+Baskerville:ital,wght@0,400;0,700;1,400&family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <script src="script.js" defer></script>
    <style>
        .settings-container {
            max-width: 900px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .settings-section {
            background: white;
            padding: 30px;
            margin-bottom: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .settings-section h2 {
            color: #1F2D5A;
            border-bottom: 2px solid #4a90e2;
            padding-bottom: 10px;
            margin-bottom: 25px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: #1F2D5A;
        }
        
        .form-group input[type="text"],
        .form-group input[type="email"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1em;
        }
        
        .form-group input[type="file"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .form-group small {
            display: block;
            margin-top: 5px;
            color: #666;
            font-size: 0.9em;
        }
        
        .qr-preview {
            margin-top: 15px;
            padding: 15px;
            background: #f9f9f9;
            border-radius: 4px;
            text-align: center;
        }
        
        .qr-preview img {
            max-width: 300px;
            max-height: 300px;
            border: 2px solid #ddd;
            border-radius: 4px;
        }
        
        .btn-save {
            background-color: #28a745;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1.1em;
            width: 100%;
            margin-top: 20px;
        }
        
        .btn-save:hover {
            background-color: #218838;
        }
        
        .btn-back {
            background-color: #6c757d;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 4px;
            display: inline-block;
            margin-bottom: 20px;
        }
        
        .btn-back:hover {
            background-color: #5a6268;
        }
        
        .success-message {
            background-color: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            border: 1px solid #c3e6cb;
        }
        
        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            border: 1px solid #f5c6cb;
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

        <main class="settings-container">
            <a href="admin_orders.php?lang=<?php echo $lang; ?>" class="btn-back">
                <?php echo $lang === 'cs' ? '← Zpět na Administraci' : '← Back to Admin Panel'; ?>
            </a>
            
            <h1><?php echo $lang === 'cs' ? 'Nastavení Společnosti' : 'Company Settings'; ?></h1>
            <div class="line-break"></div>

            <?php if ($success_message): ?>
                <div class="success-message"><?php echo $success_message; ?></div>
            <?php endif; ?>

            <?php if ($error_message): ?>
                <div class="error-message"><?php echo $error_message; ?></div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                
                <!-- Company Information Section -->
                <div class="settings-section">
                    <h2><?php echo $lang === 'cs' ? 'Informace o Společnosti' : 'Company Information'; ?></h2>
                    
                    <div class="form-group">
                        <label for="company_name"><?php echo $lang === 'cs' ? 'Název společnosti:' : 'Company Name:'; ?></label>
                        <input type="text" id="company_name" name="company_name" 
                               value="<?php echo htmlspecialchars($settings['company_name'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="company_email"><?php echo $lang === 'cs' ? 'Email společnosti:' : 'Company Email:'; ?></label>
                        <input type="email" id="company_email" name="company_email" 
                               value="<?php echo htmlspecialchars($settings['company_email'] ?? ''); ?>">
                        <small><?php echo $lang === 'cs' ? 'Bude zobrazen na fakturách' : 'Will be displayed on invoices'; ?></small>
                    </div>
                    
                    <div class="form-group">
                        <label for="company_phone"><?php echo $lang === 'cs' ? 'Telefon společnosti:' : 'Company Phone:'; ?></label>
                        <input type="text" id="company_phone" name="company_phone" 
                               value="<?php echo htmlspecialchars($settings['company_phone'] ?? ''); ?>">
                        <small><?php echo $lang === 'cs' ? 'Bude zobrazen na fakturách' : 'Will be displayed on invoices'; ?></small>
                    </div>
                </div>

                <!-- Bank Information Section -->
                <div class="settings-section">
                    <h2><?php echo $lang === 'cs' ? 'Bankovní Údaje' : 'Bank Information'; ?></h2>
                    
                    <div class="form-group">
                        <label for="bank_name"><?php echo $lang === 'cs' ? 'Název banky:' : 'Bank Name:'; ?></label>
                        <input type="text" id="bank_name" name="bank_name" 
                               value="<?php echo htmlspecialchars($settings['bank_name'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="bank_account"><?php echo $lang === 'cs' ? 'Číslo účtu:' : 'Account Number:'; ?></label>
                        <input type="text" id="bank_account" name="bank_account" 
                               value="<?php echo htmlspecialchars($settings['bank_account'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="bank_iban"><?php echo $lang === 'cs' ? 'IBAN:' : 'IBAN:'; ?></label>
                        <input type="text" id="bank_iban" name="bank_iban" 
                               value="<?php echo htmlspecialchars($settings['bank_iban'] ?? ''); ?>">
                        <small><?php echo $lang === 'cs' ? 'Pro mezinárodní platby' : 'For international payments'; ?></small>
                    </div>
                    
                    <div class="form-group">
                        <label for="bank_swift"><?php echo $lang === 'cs' ? 'SWIFT/BIC:' : 'SWIFT/BIC:'; ?></label>
                        <input type="text" id="bank_swift" name="bank_swift" 
                               value="<?php echo htmlspecialchars($settings['bank_swift'] ?? ''); ?>">
                        <small><?php echo $lang === 'cs' ? 'Pro mezinárodní platby' : 'For international payments'; ?></small>
                    </div>
                </div>

                <!-- QR Code Section -->
                <div class="settings-section">
                    <h2><?php echo $lang === 'cs' ? 'QR Kód pro Platbu' : 'Payment QR Code'; ?></h2>
                    
                    <div class="form-group">
                        <label for="qr_code"><?php echo $lang === 'cs' ? 'Nahrát QR kód:' : 'Upload QR Code:'; ?></label>
                        <input type="file" id="qr_code" name="qr_code" accept="image/*">
                        <small><?php echo $lang === 'cs' ? 'Maximální velikost: 5MB. Formáty: JPG, PNG, GIF' : 'Maximum size: 5MB. Formats: JPG, PNG, GIF'; ?></small>
                    </div>
                    
                    <?php if (!empty($settings['qr_code_image'])): ?>
                        <div class="qr-preview">
                            <p><strong><?php echo $lang === 'cs' ? 'Aktuální QR kód:' : 'Current QR Code:'; ?></strong></p>
                            <img src="<?php echo htmlspecialchars($settings['qr_code_image']); ?>" alt="QR Code">
                        </div>
                    <?php endif; ?>
                </div>

                <button type="submit" class="btn-save">
                    <?php echo $lang === 'cs' ? '💾 Uložit Nastavení' : '💾 Save Settings'; ?>
                </button>
            </form>
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
