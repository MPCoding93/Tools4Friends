<?php
session_start();
require_once __DIR__ . '/../app/db_connect.php';
require_once __DIR__ . '/../app/security.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?lang=" . ($_GET['lang'] ?? 'en'));
    exit();
}

$lang = $_GET['lang'] ?? 'en';
$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Fetch the ownerID for the logged-in user
$stmt_owner = $conn->prepare("SELECT ownerID FROM Users WHERE user_id = ?");
$stmt_owner->bind_param("i", $user_id);
$stmt_owner->execute();
$result_owner = $stmt_owner->get_result();
$user_owner_data = $result_owner->fetch_assoc();
$ownerID = $user_owner_data['ownerID'];
$stmt_owner->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !Security::verifyCSRFToken($_POST['csrf_token'])) {
        $error = ($lang === 'cs' ? 'Neplatný požadavek.' : 'Invalid request.');
    } else {
        $name = Security::sanitizeInput($_POST['name']);
        $name_cs = Security::sanitizeInput($_POST['name_cs']);
        $description = Security::sanitizeInput($_POST['description']);
        $description_cs = Security::sanitizeInput($_POST['description_cs']);
        $brand = Security::sanitizeInput($_POST['brand']);
        $model = Security::sanitizeInput($_POST['model']);
        $technical_data = Security::sanitizeInput($_POST['technical_data']);
        $technical_data_cs = Security::sanitizeInput($_POST['technical_data_cs']);
        $manipulation_fee = filter_var($_POST['manipulation_fee'], FILTER_VALIDATE_FLOAT);
        $picture_path = '';

        // Validate manipulation fee
        if ($manipulation_fee === false || $manipulation_fee < 0) {
            $manipulation_fee = 0;
        }

        // Handle image upload with security validation
        if (isset($_FILES['picture']) && $_FILES['picture']['error'] === UPLOAD_ERR_OK) {
            $uploadErrors = Security::validateFileUpload($_FILES['picture']);
            
            if (empty($uploadErrors)) {
                $target_dir = "uploads/tools/";
                if (!is_dir($target_dir)) {
                    mkdir($target_dir, 0755, true);
                }

                $secure_filename = Security::generateSecureFilename($_FILES['picture']['name']);
                $target_file = $target_dir . $secure_filename;

                if (move_uploaded_file($_FILES['picture']['tmp_name'], $target_file)) {
                    $picture_path = $target_file;
                } else {
                    $error = ($lang === 'cs' ? 'Chyba při nahrávání obrázku.' : 'Error uploading image.');
                }
            } else {
                $error = implode(', ', $uploadErrors);
            }
        } else if ($_FILES['picture']['error'] !== UPLOAD_ERR_NO_FILE) {
            $error = ($lang === 'cs' ? 'Chyba při nahrávání obrázku: ' : 'Image upload error: ') . $_FILES['picture']['error'];
        }

        // Set default picture if none uploaded
        if (empty($picture_path) && empty($error)) {
            $picture_path = 'uploads/tools/default_tool.png';
        }

        // Validate required fields
        if (empty($name) || empty($description) || empty($brand) || empty($model)) {
            $error = ($lang === 'cs' ? 'Vyplňte prosím všechna povinná pole.' : 'Please fill in all required fields.');
        }

        if (empty($error)) {
            $stmt_insert = $conn->prepare("INSERT INTO Tools (name, name_cs, description, description_cs, brand, model, technical_data, technical_data_cs, picture, ownerID, manipulation_fee, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            $stmt_insert->bind_param("ssssssssssd", $name, $name_cs, $description, $description_cs, $brand, $model, $technical_data, $technical_data_cs, $picture_path, $ownerID, $manipulation_fee);

            if ($stmt_insert->execute()) {
                $success = ($lang === 'cs' ? 'Nářadí bylo úspěšně přidáno!' : 'Tool added successfully!');
                header("Location: myprofile.php?lang=" . $lang . "&success=" . urlencode($success));
                exit();
            } else {
                error_log("Tool insertion failed: " . $conn->error);
                $error = ($lang === 'cs' ? 'Chyba při přidávání nářadí.' : 'Error adding tool.');
            }
            $stmt_insert->close();
        }
    }
}

// Generate CSRF token
$csrf_token = Security::generateCSRFToken();

// Navbar variables
$loggedIn = true;
$fullName = htmlspecialchars($_SESSION['firstname'] . ' ' . $_SESSION['lastname']);
?>

<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($lang); ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo ($lang === 'cs' ? 'Přidat Nářadí' : 'Add Tool'); ?> - Tools4Friends</title>
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
                <a href="myprofile.php?lang=<?php echo htmlspecialchars($lang); ?>" class="btn btn-blue">
                    <?php echo $lang === 'cs' ? '← Zpět na Můj Profil' : '← Back to My Profile'; ?>
                </a>
            </div>
            <h1><?php echo ($lang === 'cs' ? 'Přidat Nové Nářadí' : 'Add New Tool'); ?></h1>
            <div class="line-break"></div>

            <?php if ($error): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="success-message"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" class="form-card">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                
                <div class="form-group">
                    <label for="name"><?php echo ($lang === 'cs' ? 'Název (Anglicky):' : 'Name (English):'); ?></label>
                    <input type="text" id="name" name="name" required maxlength="100">
                </div>
                <div class="form-group">
                    <label for="name_cs"><?php echo ($lang === 'cs' ? 'Název (Česky):' : 'Name (Czech):'); ?></label>
                    <input type="text" id="name_cs" name="name_cs" maxlength="100">
                </div>

                <div class="form-group">
                    <label for="description"><?php echo ($lang === 'cs' ? 'Popis (Anglicky):' : 'Description (English):'); ?></label>
                    <textarea id="description" name="description" rows="4" required maxlength="1000"></textarea>
                </div>
                <div class="form-group">
                    <label for="description_cs"><?php echo ($lang === 'cs' ? 'Popis (Česky):' : 'Description (Czech):'); ?></label>
                    <textarea id="description_cs" name="description_cs" rows="4" maxlength="1000"></textarea>
                </div>

                <div class="form-group">
                    <label for="brand"><?php echo ($lang === 'cs' ? 'Značka:' : 'Brand:'); ?></label>
                    <input type="text" id="brand" name="brand" required maxlength="50">
                </div>

                <div class="form-group">
                    <label for="model"><?php echo ($lang === 'cs' ? 'Model:' : 'Model:'); ?></label>
                    <input type="text" id="model" name="model" required maxlength="50">
                </div>

                <div class="form-group">
                    <label for="manipulation_fee"><?php echo ($lang === 'cs' ? 'Manipulační poplatek (Kč):' : 'Manipulation Fee (CZK):'); ?></label>
                    <input type="number" id="manipulation_fee" name="manipulation_fee" min="0" step="0.01" value="0">
                </div>

                <div class="form-group">
                    <label for="technical_data"><?php echo ($lang === 'cs' ? 'Technické Detaily (Anglicky):' : 'Technical Details (English):'); ?></label>
                    <textarea id="technical_data" name="technical_data" rows="4" maxlength="1000"></textarea>
                </div>
                <div class="form-group">
                    <label for="technical_data_cs"><?php echo ($lang === 'cs' ? 'Technické Detaily (Česky):' : 'Technical Details (Czech):'); ?></label>
                    <textarea id="technical_data_cs" name="technical_data_cs" rows="4" maxlength="1000"></textarea>
                </div>

                <div class="form-group">
                    <label for="picture"><?php echo ($lang === 'cs' ? 'Obrázek Nářadí:' : 'Tool Picture:'); ?></label>
                    <input type="file" id="picture" name="picture" accept="image/jpeg,image/jpg,image/png,image/gif">
                    <small><?php echo ($lang === 'cs' ? 'Maximální velikost: 5MB. Povolené formáty: JPG, PNG, GIF' : 'Maximum size: 5MB. Allowed formats: JPG, PNG, GIF'); ?></small>
                </div>

                <button type="submit" class="submit-button">
                    <?php echo ($lang === 'cs' ? 'Přidat Nářadí' : 'Add Tool'); ?>
                </button>
            </form>
        </main>

        <footer>
            <p>&copy; <span id="year"></span> Tools4Friends</p>
        </footer>
    </div>
</body>

</html>