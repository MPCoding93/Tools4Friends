<?php
require_once __DIR__ . '/../app/security.php';
require_once __DIR__ . '/../app/db_connect.php';

startSecureSession();

$error = '';
$success = '';
$lang = $_GET['lang'] ?? 'en';

if (isset($_GET['timeout'])) {
    $error = $lang === 'cs' ? 'Vaše relace vypršela. Přihlaste se prosím znovu.' : 'Your session has expired. Please log in again.';
}

// Function to generate ownerID
function generateOwnerID($firstname, $lastname) {
    return 't4f_' . strtolower(substr($firstname, 0, 1) . substr($lastname, 0, 2));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'login') {
        if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
            $error = "Security validation failed.";
        } else {
            $username = trim($_POST['username']);
            $password = $_POST['password'] ?? '';

            if (!empty($username) && !empty($password)) {
                $rate_check = checkLoginAttempts($username);
                if (!$rate_check['allowed']) {
                    $error = $rate_check['message'];
                } else {
                    $username = htmlspecialchars($username, ENT_QUOTES, 'UTF-8');

                    $stmt = $conn->prepare("SELECT user_id, firstname, lastname, password_hash, admin FROM Users WHERE username = ? OR email = ?");
                    $stmt->bind_param("ss", $username, $username);
                    $stmt->execute();
                    $stmt->store_result();

                    if ($stmt->num_rows === 1) {
                        $stmt->bind_result($user_id, $db_firstname, $db_lastname, $password_hash, $admin);
                        $stmt->fetch();

                        if (password_verify($password, $password_hash)) {
                            clearLoginAttempts($username);
                            regenerateSession();
                            
                            $_SESSION['user_id'] = $user_id;
                            $_SESSION['firstname'] = $db_firstname;
                            $_SESSION['lastname'] = $db_lastname;
                            $_SESSION['admin'] = $admin;
                            $_SESSION['last_activity'] = time();
                            
                            logSecurityEvent('Successful login', ['user_id' => $user_id]);
                            
                            header("Location: ../index.php?lang=" . $lang);
                            exit();
                        } else {
                            recordFailedLogin($username);
                            $error = $lang === 'cs' ? 'Neplatné přihlašovací údaje.' : 'Invalid credentials.';
                        }
                    } else {
                        recordFailedLogin($username);
                        $error = $lang === 'cs' ? 'Neplatné přihlašovací údaje.' : 'Invalid credentials.';
                    }

                    $stmt->close();
                }
            } else {
                $error = $lang === 'cs' ? 'Vyplňte prosím email a heslo.' : 'Please enter both email and password.';
            }
        }
    } elseif ($action === 'register') {
        if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
            $error = "Security validation failed.";
        } else {
            $firstname = trim($_POST['firstname']);
            $lastname = trim($_POST['lastname']);
            $email = trim($_POST['email']);
            $phone = trim($_POST['phone']);
            $password = $_POST['password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';

            if (empty($firstname) || empty($lastname) || empty($email) || empty($password) || empty($confirm_password)) {
                $error = $lang === 'cs' ? 'Vyplňte prosím všechna povinná pole.' : 'Please fill in all mandatory fields.';
            } elseif (!validateEmail($email)) {
                $error = $lang === 'cs' ? 'Neplatná emailová adresa.' : 'Invalid email address.';
            } elseif ($password !== $confirm_password) {
                $error = $lang === 'cs' ? 'Hesla se neshodují.' : 'Passwords do not match.';
            } elseif (strlen($password) < 8) {
                $error = $lang === 'cs' ? 'Heslo musí mít alespoň 8 znaků.' : 'Password must be at least 8 characters.';
            } else {
                $firstname = htmlspecialchars($firstname, ENT_QUOTES, 'UTF-8');
                $lastname = htmlspecialchars($lastname, ENT_QUOTES, 'UTF-8');
                
                if (!empty($phone)) {
                    $phone = validatePhone($phone);
                    if ($phone === false) {
                        $error = $lang === 'cs' ? 'Neplatné telefonní číslo.' : 'Invalid phone number.';
                    }
                }

                if (empty($error)) {
                    $stmt_check_email = $conn->prepare("SELECT user_id FROM Users WHERE email = ?");
                    $stmt_check_email->bind_param("s", $email);
                    $stmt_check_email->execute();
                    $stmt_check_email->store_result();

                    if ($stmt_check_email->num_rows > 0) {
                        $error = $lang === 'cs' ? 'Email je již používán.' : 'Email address is already in use.';
                        $stmt_check_email->close();
                    } else {
                        $stmt_check_email->close();

                        $password_hash = password_hash($password, PASSWORD_DEFAULT);
                        $ownerID = generateOwnerID($firstname, $lastname);

                        $stmt_insert = $conn->prepare("INSERT INTO Users (firstname, lastname, email, phone, password_hash, ownerID) VALUES (?, ?, ?, ?, ?, ?)");
                        $stmt_insert->bind_param("ssssss", $firstname, $lastname, $email, $phone, $password_hash, $ownerID);

                        if ($stmt_insert->execute()) {
                            regenerateSession();
                            
                            $_SESSION['user_id'] = $stmt_insert->insert_id;
                            $_SESSION['firstname'] = $firstname;
                            $_SESSION['lastname'] = $lastname;
                            $_SESSION['admin'] = 0; // New users are not admins by default
                            $_SESSION['last_activity'] = time();
                            
                            logSecurityEvent('New user registration', ['user_id' => $_SESSION['user_id'], 'email' => $email]);

                            header("Location: ../index.php?lang=" . $lang);
                            exit();
                        } else {
                            error_log("Registration failed: " . $conn->error);
                            $error = $lang === 'cs' ? 'Registrace se nezdařila. Zkuste to prosím znovu.' : 'Registration failed. Please try again.';
                        }
                        $stmt_insert->close();
                    }
                }
            }
        }
    }
}

$csrf_token = generateCSRFToken();

$loggedIn = isset($_SESSION['user_id']);
$fullName = '';
if ($loggedIn) {
    $fullName = sanitizeOutput($_SESSION['firstname'] . ' ' . $_SESSION['lastname']);
}
?>

<!DOCTYPE html>
<html lang="<?php echo sanitizeOutput($lang); ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles.css"> <!-- Changed to relative path -->
    <link rel="icon" href="favicon/favicon-dark.ico" /> <!-- Updated path -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <meta name="description" content="Borrowing tools from friends for friends" />
    <meta name="keywords" content="Tools for Friends, tools, naradi" />
    <meta name="author" content="MPCoding" />
    <link
        href="https://fonts.googleapis.com/css2?family=Libre+Baskerville:ital,wght@0,400;0,700;1,400&family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap"
        rel="stylesheet" />
    <script src="script.js" defer></script> <!-- Changed to relative path -->
    <title>Login / Register</title>
</head>

<body>
    <div class="container">
        <header>
            <div class="banner">
                <img src="images/banners/tools4friends_dark_Banner_2000x400.png" alt="Company Logo" /> <!-- Updated path -->
            </div>
        </header>
        <div class="line-break"></div>

        <?php include __DIR__ . '/../app/navbar.php'; // Include the navbar - Updated path ?>

        <main> <!-- Added main tag here -->
            <header>
                <h1>Login / Register</h1>
            </header>
            <div class="line-break"></div>

            <?php if ($error): ?>
                <div class="error-message"><?php echo sanitizeOutput($error); ?></div>
            <?php endif; ?>

            <div class="form-toggle">
                <button class="toggle-button" onclick="toggleForm('login')">Login</button>
                <button class="toggle-button" onclick="toggleForm('register')">Register</button>
            </div>

            <form id="login-form" method="POST" class="form-card">
                <input type="hidden" name="action" value="login">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <label for="username">Email:</label>
                <input type="text" name="username" required maxlength="255">
                <label for="password">Password:</label>
                <input type="password" name="password" required>
                <button type="submit" class="submit-button">Login</button>
            </form>
            <p class="text-center mt-15">
                <a href="./forgot_password.php?lang=<?php echo sanitizeOutput($lang); ?>">
                    <?php echo ($lang === 'cs' ? 'Zapomenuté heslo?' : 'Forgot Password?'); ?>
                </a>
            </p>


            <form id="register-form" method="POST" class="form-card form-hidden">
                <input type="hidden" name="action" value="register">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <label for="firstname">First Name:</label>
                <input type="text" name="firstname" required maxlength="100">
                <label for="lastname">Last Name:</label>
                <input type="text" name="lastname" required maxlength="100">
                <label for="email">Email:</label>
                <input type="email" name="email" required maxlength="255">
                <label for="phone">Phone:</label>
                <input type="text" name="phone" maxlength="20">
                <label for="password">Password:</label>
                <input type="password" name="password" required minlength="8">
                <small>Minimum 8 characters</small>
                <label for="confirm_password">Confirm Password:</label>
                <input type="password" name="confirm_password" required minlength="8">
                <button type="submit" class="submit-button">Register</button>
            </form>
        </main>
        <footer>
            <p>&copy; <span id="year"></span> Tools4Friends</p>
        </footer>
    </div>
</body>

</html>
