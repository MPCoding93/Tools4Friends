<?php
// Start session and check authentication
require_once __DIR__ . '/../app/security.php';
require_once __DIR__ . '/../app/db_connect.php'; // Path from public/myprofile.php to app/db_connect.php

startSecureSession();

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?lang=" . ($_GET['lang'] ?? 'en'));
    exit();
}

// Get the language from the URL or default to English
$lang = $_GET['lang'] ?? 'en';

$user_id = $_SESSION['user_id'];
$error = ''; // General error message
$success = ''; // General success message
$password_error = ''; // Specific error for password change
$profile_error = ''; // Specific error for profile update

// Fetch user data with prepared statement
$stmt_user = $conn->prepare("SELECT firstname, lastname, email, phone FROM Users WHERE user_id = ?");
$stmt_user->bind_param("i", $user_id);
$stmt_user->execute();
$result_user = $stmt_user->get_result();
$user = $result_user->fetch_assoc();
$stmt_user->close();

// Handle profile updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $firstname = trim($_POST['firstname']);
    $lastname = trim($_POST['lastname']);
    $phone = trim($_POST['phone']);

    if (empty($firstname) || empty($lastname)) {
        $profile_error = ($lang === 'cs' ? 'Jméno a příjmení jsou povinné.' : 'First name and last name are required.');
    } else {
        // Update user data - using 'phone' column name
        $stmt_update = $conn->prepare("UPDATE Users SET firstname = ?, lastname = ?, phone = ? WHERE user_id = ?");
        $stmt_update->bind_param("sssi", $firstname, $lastname, $phone, $user_id);
        if ($stmt_update->execute()) {
            // Update session variables immediately
            $_SESSION['firstname'] = $firstname;
            $_SESSION['lastname'] = $lastname;
            // Re-fetch user data to ensure form fields are updated if needed
            $user['firstname'] = $firstname;
            $user['lastname'] = $lastname;
            $user['phone'] = $phone;

            $success = ($lang === 'cs' ? 'Profil byl úspěšně aktualizován.' : 'Profile updated successfully.');
        } else {
            $profile_error = ($lang === 'cs' ? 'Chyba při aktualizaci profilu.' : 'Error updating profile.') . " " . $conn->error;
        }
        $stmt_update->close();
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Fetch current password hash
    $stmt_pass = $conn->prepare("SELECT password_hash FROM Users WHERE user_id = ?");
    $stmt_pass->bind_param("i", $user_id);
    $stmt_pass->execute();
    $result_pass = $stmt_pass->get_result();
    $row_pass = $result_pass->fetch_assoc();
    $stmt_pass->close();

    if (!password_verify($current_password, $row_pass['password_hash'])) {
        $password_error = ($lang === 'cs' ? 'Současné heslo je nesprávné.' : 'Current password is incorrect.');
    } elseif (strlen($new_password) < 8) {
        $password_error = ($lang === 'cs' ? 'Heslo musí obsahovat alespoň 8 znaků.' : 'Password must be at least 8 characters.');
    } elseif ($new_password !== $confirm_password) {
        $password_error = ($lang === 'cs' ? 'Hesla se neshodují.' : 'Passwords do not match.');
    } else {
        // Hash new password and update
        $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt_update_pass = $conn->prepare("UPDATE Users SET password_hash = ? WHERE user_id = ?");
        $stmt_update_pass->bind_param("si", $new_password_hash, $user_id);

        if ($stmt_update_pass->execute()) {
            $success = ($lang === 'cs' ? 'Heslo bylo úspěšně změněno.' : 'Password changed successfully.');
        } else {
            $password_error = ($lang === 'cs' ? 'Chyba při změně hesla.' : 'Error changing password.') . " " . $conn->error;
        }
        $stmt_update_pass->close();
    }
}

// Fetch user's tools with ownerID
$stmt_tools = $conn->prepare("
    SELECT t.tool_id, t.name, t.name_cs, t.picture, t.brand, t.model, t.ownerID
    FROM Tools t
    JOIN Users u ON t.ownerID = u.ownerID
    WHERE u.user_id = ?
");
$stmt_tools->bind_param("i", $user_id);
$stmt_tools->execute();
$user_tools_result = $stmt_tools->get_result();
$user_tools = [];
while ($tool_row = $user_tools_result->fetch_assoc()) {
    $user_tools[] = $tool_row;
}
$stmt_tools->close();

// Navbar variables (ensure these are set before including navbar.php)
$loggedIn = true;
$fullName = htmlspecialchars($_SESSION['firstname'] . ' ' . $_SESSION['lastname']);
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo ($lang === 'cs' ? 'Můj Profil' : 'My Profile'); ?> - Tools4Friends</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="icon" href="favicon/favicon-dark.ico" /> <!-- Updated path -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Libre+Baskerville:ital,wght@0,400;0,700;1,400&family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <script src="script.js" defer></script>
</head>
<body>
    <?php include __DIR__ . '/../app/cart_icon.php'; ?>
    
    <div class="container">
        <header>
            <div class="banner">
                <img src="images/banners/tools4friends_dark_Banner_2000x400.png" alt="Company Logo" /> <!-- Updated path -->
            </div>
        </header>
        <div class="line-break"></div>
        <?php include __DIR__ . '/../app/navbar.php'; ?> <!-- Updated path -->

        <main>
            <h1><?php echo ($lang === 'cs' ? 'Můj Profil' : 'My Profile'); ?></h1>
            <div class="line-break"></div>

            <?php
            // Display general success/error messages
            if ($success): ?>
                <div class="success-message"><?php echo htmlspecialchars($success); ?></div>
            <?php endif;
            if ($error): // This variable is not currently used in your PHP logic, but kept for consistency
                ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <div class="profile-sections">
                <!-- User Details Section -->
                <section class="profile-section-profile form-card">
                    <div class="profile-header">
                        <div class="profile-name">
                            <h2><?php echo htmlspecialchars($fullName); ?></h2>
                            <p>ID: <?php echo $user_id; ?></p>
                        </div>
                    </div>

                    <form method="POST">
                        <div class="form-group">
                            <label for="firstname"><?php echo ($lang === 'cs' ? 'Jméno:' : 'First Name:'); ?></label>
                            <input type="text" id="firstname" name="firstname" value="<?php echo htmlspecialchars($user['firstname']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="lastname"><?php echo ($lang === 'cs' ? 'Příjmení:' : 'Last Name:'); ?></label>
                            <input type="text" id="lastname" name="lastname" value="<?php echo htmlspecialchars($user['lastname']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="email"><?php echo ($lang === 'cs' ? 'Email:' : 'Email:'); ?></label>
                            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
                            <small><?php echo ($lang === 'cs' ? 'Email nelze změnit.' : 'Email cannot be changed.'); ?></small>
                        </div>

                        <div class="form-group">
                            <label for="phone"><?php echo ($lang === 'cs' ? 'Telefon:' : 'Phone:'); ?></label>
                            <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                        </div>

                        <?php if ($profile_error): ?>
                            <div class="error-message"><?php echo htmlspecialchars($profile_error); ?></div>
                        <?php endif; ?>

                        <button type="submit" name="update_profile" class="submit-button mt-20">
                            <?php echo ($lang === 'cs' ? 'Aktualizovat Profil' : 'Update Profile'); ?>
                        </button>
                    </form>
                </section>

                <!-- Reset Password Section -->
                <section class="profile-section-password form-card">
                    <h2><?php echo ($lang === 'cs' ? 'Změnit heslo' : 'Change Password'); ?></h2>
                    <form method="POST">
                        <div class="form-group">
                            <label for="current_password"><?php echo ($lang === 'cs' ? 'Současné heslo:' : 'Current Password:'); ?></label>
                            <input type="password" id="current_password" name="current_password" required>
                        </div>

                        <div class="form-group">
                            <label for="new_password"><?php echo ($lang === 'cs' ? 'Nové heslo:' : 'New Password:'); ?></label>
                            <input type="password" id="new_password" name="new_password" required>
                            <small><?php echo ($lang === 'cs' ? 'Heslo musí obsahovat alespoň 8 znaků' : 'Password must be at least 8 characters'); ?></small>
                        </div>

                        <div class="form-group">
                            <label for="confirm_password"><?php echo ($lang === 'cs' ? 'Potvrďte nové heslo:' : 'Confirm New Password:'); ?></label>
                            <input type="password" id="confirm_password" name="confirm_password" required>
                        </div>

                        <?php if ($password_error): ?>
                            <div class="error-message"><?php echo htmlspecialchars($password_error); ?></div>
                        <?php endif; ?>

                        <button type="submit" name="change_password" class="submit-button">
                            <?php echo ($lang === 'cs' ? 'Změnit heslo' : 'Change Password'); ?>
                        </button>
                    </form>
                </section>

                <!-- Add New Tool Button Section -->
                <section class="profile-section-add">
                    <a href="add_tool.php?lang=<?php echo $lang; ?>" class="availability-button wide-button">
                        <?php echo ($lang === 'cs' ? '➕ Přidat Nový Nástroj' : '➕ Add New Tool'); ?>
                    </a>
                </section>

                <!-- My Tools Section -->
                <section class="profile-section-tools">
                    <h2><?php echo ($lang === 'cs' ? 'Moje Nářadí' : 'My Tools'); ?></h2>
                    <?php if (empty($user_tools)): ?>
                        <p><?php echo ($lang === 'cs' ? 'Zatím nemáte žádné nástroje.' : 'You have not listed any tools yet.'); ?></p>
                    <?php else: ?>
                        <div class="tool-list-grid">
                            <?php foreach ($user_tools as $tool):
                                $tool_name = $lang === 'cs' && !empty($tool['name_cs']) ? $tool['name_cs'] : $tool['name'];
                            ?>
                                <div class="tool-card">
                                    <img src="<?php echo htmlspecialchars($tool['picture']); ?>" alt="<?php echo htmlspecialchars($tool_name); ?>" class="tool-img">
                                    <div class="tool-body">
                                        <h3 class="tool-title"><?php echo htmlspecialchars($tool_name); ?></h3>
                                        <p class="tool-meta"><strong><?php echo ($lang === 'cs' ? 'Značka:' : 'Brand:'); ?></strong> <?php echo htmlspecialchars($tool['brand']); ?></p>
                                        <p class="tool-meta"><strong><?php echo ($lang === 'cs' ? 'Model:' : 'Model:'); ?></strong> <?php echo htmlspecialchars($tool['model']); ?></p>
                                        <p class="tool-meta"><strong><?php echo ($lang === 'cs' ? 'Vlastník:' : 'Owner:'); ?></strong> <?php echo htmlspecialchars($tool['ownerID']); ?></p>
                                        <div class="tool-actions">
                                            <a href="edit_tool.php?tool_id=<?php echo $tool['tool_id']; ?>&lang=<?php echo $lang; ?>" class="btn btn-blue"><?php echo ($lang === 'cs' ? 'Upravit' : 'Edit'); ?></a>
                                            <a href="tool_availability.php?tool_id=<?php echo $tool['tool_id']; ?>&lang=<?php echo $lang; ?>" class="btn btn-blue"><?php echo ($lang === 'cs' ? 'Dostupnost' : 'Availability'); ?></a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </section>
            </div>
        </main>

        <footer>
            <p>&copy; <span id="year"></span> Tools4Friends</p>
        </footer>
    </div>
</body>
</html>
