<?php
require_once __DIR__ . '/../app/security.php';
require_once __DIR__ . '/../app/db_connect.php';

startSecureSession();

$lang = $_GET['lang'] ?? 'en';

// Redirect if not logged in
requireLogin($lang);

$user_id = $_SESSION['user_id'];
$tool_id = isset($_GET['tool_id']) ? intval($_GET['tool_id']) : (isset($_POST['tool_id']) ? intval($_POST['tool_id']) : 0);
$error = '';
$success = '';
$tool = null; // Initialize tool data

// Fetch the ownerID for the logged-in user
$stmt_owner = $conn->prepare("SELECT ownerID FROM Users WHERE user_id = ?");
$stmt_owner->bind_param("i", $user_id);
$stmt_owner->execute();
$result_owner = $stmt_owner->get_result();
$user_owner_data = $result_owner->fetch_assoc();
$loggedIn_ownerID = $user_owner_data['ownerID'];
$stmt_owner->close();

// Fetch tool data for pre-population or update
if ($tool_id > 0) {
    $stmt_tool = $conn->prepare("SELECT * FROM Tools WHERE tool_id = ?");
    $stmt_tool->bind_param("i", $tool_id);
    $stmt_tool->execute();
    $result_tool = $stmt_tool->get_result();
    $tool = $result_tool->fetch_assoc();
    $stmt_tool->close();

    // Authorization check: Ensure the logged-in user owns this tool
    if (!$tool || $tool['ownerID'] !== $loggedIn_ownerID) {
        header("Location: myprofile.php?lang=" . $lang . "&error=" . urlencode(($lang === 'cs' ? 'Nemáte oprávnění upravovat toto nářadí.' : 'You are not authorized to edit this tool.')));
        exit();
    }
} else {
    header("Location: myprofile.php?lang=" . $lang . "&error=" . urlencode(($lang === 'cs' ? 'Nebylo zadáno ID nářadí.' : 'No tool ID specified.')));
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $error = "Security validation failed.";
        logSecurityEvent('CSRF validation failed on tool edit', [
            'user_id' => $user_id,
            'tool_id' => $tool_id
        ]);
    } else {
        // Sanitize inputs
        $name = htmlspecialchars(strip_tags(trim($_POST['name'])), ENT_QUOTES, 'UTF-8');
        $name_cs = htmlspecialchars(strip_tags(trim($_POST['name_cs'])), ENT_QUOTES, 'UTF-8');
        $description = htmlspecialchars(strip_tags(trim($_POST['description'])), ENT_QUOTES, 'UTF-8');
        $description_cs = htmlspecialchars(strip_tags(trim($_POST['description_cs'])), ENT_QUOTES, 'UTF-8');
        $brand = htmlspecialchars(strip_tags(trim($_POST['brand'])), ENT_QUOTES, 'UTF-8');
        $model = htmlspecialchars(strip_tags(trim($_POST['model'])), ENT_QUOTES, 'UTF-8');
        $technical_data = htmlspecialchars(strip_tags(trim($_POST['technical_data'])), ENT_QUOTES, 'UTF-8');
        $technical_data_cs = htmlspecialchars(strip_tags(trim($_POST['technical_data_cs'])), ENT_QUOTES, 'UTF-8');
        $current_picture_path = trim($_POST['picture'] ?? $tool['picture']); // Use provided path or keep current

        if (empty($name) || empty($description) || empty($brand) || empty($model)) {
            $error = ($lang === 'cs' ? 'Vyplňte prosím všechna povinná pole (Název, Popis, Značka, Model).' : 'Please fill in all required fields (Name, Description, Brand, Model).');
        }

        if (empty($error)) {
            $stmt_update = $conn->prepare("UPDATE Tools SET name = ?, name_cs = ?, description = ?, description_cs = ?, brand = ?, model = ?, technical_data = ?, technical_data_cs = ?, picture = ? WHERE tool_id = ? AND ownerID = ?");
            $stmt_update->bind_param("sssssssssis", $name, $name_cs, $description, $description_cs, $brand, $model, $technical_data, $technical_data_cs, $current_picture_path, $tool_id, $loggedIn_ownerID);

            if ($stmt_update->execute()) {
                $success = ($lang === 'cs' ? 'Nářadí bylo úspěšně aktualizováno!' : 'Tool updated successfully!');
                
                // Log the event
                logSecurityEvent('Tool edited', [
                    'user_id' => $user_id,
                    'tool_id' => $tool_id
                ]);
                
                // Re-fetch tool data to show updated values in the form immediately
                $stmt_tool_re = $conn->prepare("SELECT * FROM Tools WHERE tool_id = ?");
                $stmt_tool_re->bind_param("i", $tool_id);
                $stmt_tool_re->execute();
                $result_tool_re = $stmt_tool_re->get_result();
                $tool = $result_tool_re->fetch_assoc();
                $stmt_tool_re->close();
            } else {
                error_log("Tool update failed: " . $conn->error);
                $error = ($lang === 'cs' ? 'Chyba při aktualizaci nářadí.' : 'Error updating tool.');
            }
            $stmt_update->close();
        }
    }
}

$csrf_token = generateCSRFToken();

// Navbar variables (ensure these are set before including navbar.php)
$loggedIn = true;
$fullName = sanitizeOutput($_SESSION['firstname'] . ' ' . $_SESSION['lastname']);
?>

<!DOCTYPE html>
<html lang="<?php echo sanitizeOutput($lang); ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo ($lang === 'cs' ? 'Upravit Nářadí' : 'Edit Tool'); ?> - Tools4Friends</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="icon" href="favicon/favicon-dark.ico" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Libre+Baskerville:ital,wght@0,400;0,700;1,400&family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap"
        rel="stylesheet">
    <script src="script.js" defer></script>
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
            <div style="margin-bottom: 20px;">
                <a href="myprofile.php?lang=<?php echo sanitizeOutput($lang); ?>" class="btn btn-blue">
                    <?php echo $lang === 'cs' ? '← Zpět na Můj Profil' : '← Back to My Profile'; ?>
                </a>
            </div>
            <h1><?php echo ($lang === 'cs' ? 'Upravit Nářadí' : 'Edit Tool'); ?>: <?php echo sanitizeOutput($tool['name'] ?? ''); ?></h1>
            <div class="line-break"></div>

            <?php if ($error): ?>
                <div class="error-message"><?php echo sanitizeOutput($error); ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="success-message"><?php echo sanitizeOutput($success); ?></div>
            <?php endif; ?>

            <form method="POST" class="form-card">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <input type="hidden" name="tool_id" value="<?php echo sanitizeOutput($tool_id); ?>">

                <div class="form-group">
                    <label for="name"><?php echo ($lang === 'cs' ? 'Název (Anglicky):' : 'Name (English):'); ?></label>
                    <input type="text" id="name" name="name" value="<?php echo sanitizeOutput($tool['name'] ?? ''); ?>" required maxlength="200">
                </div>
                <div class="form-group">
                    <label for="name_cs"><?php echo ($lang === 'cs' ? 'Název (Česky):' : 'Name (Czech):'); ?></label>
                    <input type="text" id="name_cs" name="name_cs" value="<?php echo sanitizeOutput($tool['name_cs'] ?? ''); ?>" maxlength="200">
                </div>

                <div class="form-group">
                    <label for="description"><?php echo ($lang === 'cs' ? 'Popis (Anglicky):' : 'Description (English):'); ?></label>
                    <textarea id="description" name="description" rows="4" required maxlength="1000"><?php echo sanitizeOutput($tool['description'] ?? ''); ?></textarea>
                </div>
                <div class="form-group">
                    <label for="description_cs"><?php echo ($lang === 'cs' ? 'Popis (Česky):' : 'Description (Czech):'); ?></label>
                    <textarea id="description_cs" name="description_cs" rows="4" maxlength="1000"><?php echo sanitizeOutput($tool['description_cs'] ?? ''); ?></textarea>
                </div>

                <div class="form-group">
                    <label for="brand"><?php echo ($lang === 'cs' ? 'Značka:' : 'Brand:'); ?></label>
                    <input type="text" id="brand" name="brand" value="<?php echo sanitizeOutput($tool['brand'] ?? ''); ?>" required maxlength="100">
                </div>

                <div class="form-group">
                    <label for="model"><?php echo ($lang === 'cs' ? 'Model:' : 'Model:'); ?></label>
                    <input type="text" id="model" name="model" value="<?php echo sanitizeOutput($tool['model'] ?? ''); ?>" required maxlength="100">
                </div>

                <div class="form-group">
                    <label for="technical_data"><?php echo ($lang === 'cs' ? 'Technické Detaily (Anglicky):' : 'Technical Details (English):'); ?></label>
                    <textarea id="technical_data" name="technical_data" rows="4" maxlength="1000"><?php echo sanitizeOutput($tool['technical_data'] ?? ''); ?></textarea>
                </div>
                <div class="form-group">
                    <label for="technical_data_cs"><?php echo ($lang === 'cs' ? 'Technické Detaily (Česky):' : 'Technical Details (Czech):'); ?></label>
                    <textarea id="technical_data_cs" name="technical_data_cs" rows="4" maxlength="1000"><?php echo sanitizeOutput($tool['technical_data_cs'] ?? ''); ?></textarea>
                </div>

                <div class="form-group">
                    <label><?php echo ($lang === 'cs' ? 'Aktuální Obrázek:' : 'Current Picture:'); ?></label>
                    <?php if (!empty($tool['picture'])): ?>
                        <img src="<?php echo sanitizeOutput($tool['picture']); ?>" alt="Current Tool Picture" style="max-width: 200px; height: auto; display: block; margin-top: 10px; border-radius: 8px;">
                    <?php else: ?>
                        <p><?php echo ($lang === 'cs' ? 'Žádný obrázek.' : 'No picture available.'); ?></p>
                    <?php endif; ?>
                    <label for="picture" style="margin-top: 15px;"><?php echo ($lang === 'cs' ? 'Změnit Obrázek:' : 'Change Picture:'); ?></label>
                    <input type="text" id="picture" name="picture" value="<?php echo sanitizeOutput($tool['picture'] ?? ''); ?>" placeholder="e.g., images/categories/nail guns/Parkside_PDT40F4.jpeg">
                    <small><?php echo ($lang === 'cs' ? 'Zadejte cestu k obrázku v public/images/categories/... Pokud není zadáno, zůstane stávající.' : 'Enter the path to the image in public/images/categories/... If not provided, the current one will be kept.'); ?></small>
                </div>

                <button type="submit" class="submit-button">
                    <?php echo ($lang === 'cs' ? 'Aktualizovat Nářadí' : 'Update Tool'); ?>
                </button>
            </form>
        </main>

        <footer>
            <p>&copy; <span id="year"></span> Tools4Friends</p>
        </footer>
    </div>
</body>

</html>
