<?php
session_start();
require_once 'db_connect.php';

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
    $name = trim($_POST['name']);
    $name_cs = trim($_POST['name_cs']);
    $description = trim($_POST['description']);
    $description_cs = trim($_POST['description_cs']);
    $brand = trim($_POST['brand']);
    $model = trim($_POST['model']);
    $technical_data = trim($_POST['technical_data']);
    $technical_data_cs = trim($_POST['technical_data_cs']);
    $picture_path = ''; // Default empty path

    // Handle image upload
    if (isset($_FILES['picture']) && $_FILES['picture']['error'] === UPLOAD_ERR_OK) {
        $target_dir = "uploads/";
        // Ensure the uploads directory exists
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        $image_file_type = strtolower(pathinfo($_FILES['picture']['name'], PATHINFO_EXTENSION));
        $allowed_extensions = array("jpg", "jpeg", "png", "gif");

        if (in_array($image_file_type, $allowed_extensions)) {
            $unique_filename = uniqid() . "." . $image_file_type;
            $target_file = $target_dir . $unique_filename;

            if (move_uploaded_file($_FILES['picture']['tmp_name'], $target_file)) {
                $picture_path = $target_file;
            } else {
                $error = ($lang === 'cs' ? 'Chyba při nahrávání obrázku.' : 'Error uploading image.');
            }
        } else {
            $error = ($lang === 'cs' ? 'Pouze soubory JPG, JPEG, PNG a GIF jsou povoleny.' : 'Only JPG, JPEG, PNG & GIF files are allowed.');
        }
    } else if ($_FILES['picture']['error'] !== UPLOAD_ERR_NO_FILE) {
        $error = ($lang === 'cs' ? 'Chyba při nahrávání obrázku: ' : 'Image upload error: ') . $_FILES['picture']['error'];
    }

    // If no picture was uploaded and no error occurred, set a default picture
    if (empty($picture_path) && empty($error)) {
        $picture_path = 'uploads/default_tool.png'; // Ensure you have a default_tool.png in your uploads folder
    }

    if (empty($name) || empty($description) || empty($brand) || empty($model)) {
        $error = ($lang === 'cs' ? 'Vyplňte prosím všechna povinná pole (Název, Popis, Značka, Model).' : 'Please fill in all required fields (Name, Description, Brand, Model).');
    }

    if (empty($error)) {
        $stmt_insert = $conn->prepare("INSERT INTO Tools (name, name_cs, description, description_cs, brand, model, technical_data, technical_data_cs, picture, ownerID) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt_insert->bind_param("ssssssssss", $name, $name_cs, $description, $description_cs, $brand, $model, $technical_data, $technical_data_cs, $picture_path, $ownerID);

        if ($stmt_insert->execute()) {
            $success = ($lang === 'cs' ? 'Nářadí bylo úspěšně přidáno!' : 'Tool added successfully!');
            // Optionally redirect after success
            header("Location: myprofile.php?lang=" . $lang . "&success=" . urlencode($success));
            exit();
        } else {
            $error = ($lang === 'cs' ? 'Chyba při přidávání nářadí: ' : 'Error adding tool: ') . $conn->error;
        }
        $stmt_insert->close();
    }
}

// Navbar variables (ensure these are set before including navbar.php)
$loggedIn = true;
$fullName = htmlspecialchars($_SESSION['firstname'] . ' ' . $_SESSION['lastname']);
?>

<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo ($lang === 'cs' ? 'Přidat Nářadí' : 'Add Tool'); ?> - Tools4Friends</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="icon" href="/favicon-dark.ico" />
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
                <img src="/tools4friends_dark_Banner_2000x400.png" alt="Company Logo" />
            </div>
        </header>
        <div class="line-break"></div>
        <?php include 'navbar.php'; ?>

        <main>
            <div style="margin-bottom: 20px;">
                <a href="myprofile.php?lang=<?php echo $lang; ?>" class="btn btn-blue">
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
                <div class="form-group">
                    <label for="name"><?php echo ($lang === 'cs' ? 'Název (Anglicky):' : 'Name (English):'); ?></label>
                    <input type="text" id="name" name="name" required>
                </div>
                <div class="form-group">
                    <label for="name_cs"><?php echo ($lang === 'cs' ? 'Název (Česky):' : 'Name (Czech):'); ?></label>
                    <input type="text" id="name_cs" name="name_cs">
                </div>

                <div class="form-group">
                    <label for="description"><?php echo ($lang === 'cs' ? 'Popis (Anglicky):' : 'Description (English):'); ?></label>
                    <textarea id="description" name="description" rows="4" required></textarea>
                </div>
                <div class="form-group">
                    <label for="description_cs"><?php echo ($lang === 'cs' ? 'Popis (Česky):' : 'Description (Czech):'); ?></label>
                    <textarea id="description_cs" name="description_cs" rows="4"></textarea>
                </div>

                <div class="form-group">
                    <label for="brand"><?php echo ($lang === 'cs' ? 'Značka:' : 'Brand:'); ?></label>
                    <input type="text" id="brand" name="brand" required>
                </div>

                <div class="form-group">
                    <label for="model"><?php echo ($lang === 'cs' ? 'Model:' : 'Model:'); ?></label>
                    <input type="text" id="model" name="model" required>
                </div>

                <div class="form-group">
                    <label for="technical_data"><?php echo ($lang === 'cs' ? 'Technické Detaily (Anglicky):' : 'Technical Details (English):'); ?></label>
                    <textarea id="technical_data" name="technical_data" rows="4"></textarea>
                </div>
                <div class="form-group">
                    <label for="technical_data_cs"><?php echo ($lang === 'cs' ? 'Technické Detaily (Česky):' : 'Technical Details (Czech):'); ?></label>
                    <textarea id="technical_data_cs" name="technical_data_cs" rows="4"></textarea>
                </div>

                <div class="form-group">
                    <label for="picture"><?php echo ($lang === 'cs' ? 'Obrázek Nářadí:' : 'Tool Picture:'); ?></label>
                    <input type="file" id="picture" name="picture" accept="image/*">
                    <small><?php echo ($lang === 'cs' ? 'Pokud není nahrán žádný obrázek, použije se výchozí.' : 'If no image is uploaded, a default will be used.'); ?></small>
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
