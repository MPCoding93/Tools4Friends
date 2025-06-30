<?php
session_start();
require_once __DIR__ . '/../app/db_connect.php';
require_once __DIR__ . '/../app/security.php';

$error = '';
$success = '';

// Get selected language from URL or default to English
$lang = $_GET['lang'] ?? 'en';

// Function to generate ownerID
function generateOwnerID($firstname, $lastname) {
    return 't4f_' . strtolower(substr($firstname, 0, 1) . substr($lastname, 0, 2));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !Security::verifyCSRFToken($_POST['csrf_token'])) {
        $error = "Invalid request. Please try again.";
    } else {
        $action = $_POST['action'];

        if ($action === 'login') {
            // Rate limiting for login attempts
            if (!Security::checkRateLimit('login', 5, 900)) { // 5 attempts per 15 minutes
                $error = ($lang === 'cs' ? 'Příliš mnoho pokusů o přihlášení. Zkuste to znovu za 15 minut.' : 'Too many login attempts. Please try again in 15 minutes.');
            } else {
                $username = Security::sanitizeInput($_POST['username']);
                $password = $_POST['password'];

                if (!empty($username) && !empty($password)) {
                    // LOGIN with prepared statement
                    $stmt = $conn->prepare("SELECT user_id, firstname, lastname, password_hash, failed_login_attempts, locked_until FROM Users WHERE username = ? OR email = ?");
                    if ($stmt) {
                        $stmt->bind_param("ss", $username, $username);
                        $stmt->execute();
                        $result = $stmt->get_result();

                        if ($result->num_rows === 1) {
                            $user = $result->fetch_assoc();
                            
                            // Check if account is locked
                            if ($user['locked_until'] && strtotime($user['locked_until']) > time()) {
                                $error = ($lang === 'cs' ? 'Účet je dočasně uzamčen.' : 'Account is temporarily locked.');
                            } else {
                                if (password_verify($password, $user['password_hash'])) {
                                    // Reset failed attempts on successful login
                                    $reset_stmt = $conn->prepare("UPDATE Users SET failed_login_attempts = 0, locked_until = NULL WHERE user_id = ?");
                                    $reset_stmt->bind_param("i", $user['user_id']);
                                    $reset_stmt->execute();
                                    $reset_stmt->close();
                                    
                                    // Regenerate session ID to prevent session fixation
                                    session_regenerate_id(true);
                                    
                                    $_SESSION['user_id'] = $user['user_id'];
                                    $_SESSION['firstname'] = $user['firstname'];
                                    $_SESSION['lastname'] = $user['lastname'];
                                    $_SESSION['login_time'] = time();
                                    
                                    header("Location: /index.php?lang=" . $lang);
                                    exit();
                                } else {
                                    // Increment failed login attempts
                                    $failed_attempts = $user['failed_login_attempts'] + 1;
                                    $locked_until = null;
                                    
                                    if ($failed_attempts >= 5) {
                                        $locked_until = date('Y-m-d H:i:s', time() + 900); // Lock for 15 minutes
                                    }
                                    
                                    $update_stmt = $conn->prepare("UPDATE Users SET failed_login_attempts = ?, locked_until = ? WHERE user_id = ?");
                                    $update_stmt->bind_param("isi", $failed_attempts, $locked_until, $user['user_id']);
                                    $update_stmt->execute();
                                    $update_stmt->close();
                                    
                                    $error = ($lang === 'cs' ? 'Neplatné přihlašovací údaje.' : 'Invalid credentials.');
                                }
                            }
                        } else {
                            $error = ($lang === 'cs' ? 'Uživatel nenalezen.' : 'User not found.');
                        }
                        $stmt->close();
                    }
                } else {
                    $error = ($lang === 'cs' ? 'Zadejte prosím email a heslo.' : 'Please enter both email and password.');
                }
            }
        } elseif ($action === 'register') {
            // Rate limiting for registration
            if (!Security::checkRateLimit('register', 3, 3600)) { // 3 attempts per hour
                $error = ($lang === 'cs' ? 'Příliš mnoho pokusů o registraci. Zkuste to znovu za hodinu.' : 'Too many registration attempts. Please try again in an hour.');
            } else {
                $firstname = Security::sanitizeInput($_POST['firstname']);
                $lastname = Security::sanitizeInput($_POST['lastname']);
                $email = Security::sanitizeInput($_POST['email']);
                $phone = Security::sanitizeInput($_POST['phone']);
                $password = $_POST['password'];
                $confirm_password = $_POST['confirm_password'];

                // Validate input
                if (empty($firstname) || empty($lastname) || empty($email) || empty($password) || empty($confirm_password)) {
                    $error = ($lang === 'cs' ? 'Vyplňte prosím všechna povinná pole.' : 'Please fill in all mandatory fields.');
                } elseif (!Security::validateEmail($email)) {
                    $error = ($lang === 'cs' ? 'Neplatný formát emailu.' : 'Invalid email format.');
                } elseif ($password !== $confirm_password) {
                    $error = ($lang === 'cs' ? 'Hesla se neshodují.' : 'Passwords do not match.');
                } elseif (!Security::validatePassword($password)) {
                    $error = ($lang === 'cs' ? 'Heslo musí obsahovat alespoň 8 znaků, velké a malé písmeno a číslo.' : 'Password must contain at least 8 characters, uppercase, lowercase and number.');
                } else {
                    // Check email uniqueness
                    $stmt_check_email = $conn->prepare("SELECT user_id FROM Users WHERE email = ?");
                    if ($stmt_check_email) {
                        $stmt_check_email->bind_param("s", $email);
                        $stmt_check_email->execute();
                        $stmt_check_email->store_result();

                        if ($stmt_check_email->num_rows > 0) {
                            $error = ($lang === 'cs' ? 'Email je již používán.' : 'Email address is already in use.');
                        } else {
                            // Insert new user
                            $password_hash = password_hash($password, PASSWORD_ARGON2ID);
                            $ownerID = generateOwnerID($firstname, $lastname);

                            $stmt_insert = $conn->prepare("INSERT INTO Users (firstname, lastname, email, phone, password_hash, ownerID, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
                            if ($stmt_insert) {
                                $stmt_insert->bind_param("ssssss", $firstname, $lastname, $email, $phone, $password_hash, $ownerID);

                                if ($stmt_insert->execute()) {
                                    // Regenerate session ID
                                    session_regenerate_id(true);
                                    
                                    $_SESSION['user_id'] = $stmt_insert->insert_id;
                                    $_SESSION['firstname'] = $firstname;
                                    $_SESSION['lastname'] = $lastname;
                                    $_SESSION['login_time'] = time();

                                    header("Location: /index.php?lang=" . $lang);
                                    exit();
                                } else {
                                    error_log("Registration failed: " . $conn->error);
                                    $error = ($lang === 'cs' ? 'Registrace se nezdařila. Zkuste to znovu.' : 'Registration failed. Please try again.');
                                }
                                $stmt_insert->close();
                            }
                        }
                        $stmt_check_email->close();
                    }
                }
            }
        }
    }
}

// Generate CSRF token
$csrf_token = Security::generateCSRFToken();

// User Login Status for Navbar
$loggedIn = isset($_SESSION['user_id']);
$fullName = '';
if ($loggedIn) {
    $fullName = htmlspecialchars($_SESSION['firstname'] . ' ' . $_SESSION['lastname']);
}
?>

<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($lang); ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles.css">
    <link rel="icon" href="favicon/favicon-dark.ico" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <meta name="description" content="Borrowing tools from friends for friends" />
    <meta name="keywords" content="Tools for Friends, tools, naradi" />
    <meta name="author" content="MPCoding" />
    <link
        href="https://fonts.googleapis.com/css2?family=Libre+Baskerville:ital,wght@0,400;0,700;1,400&family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap"
        rel="stylesheet" />
    <script src="script.js" defer></script>
    <title>Login / Register</title>
    <script>
        function toggleForm(action) {
            const loginForm = document.getElementById('login-form');
            const registerForm = document.getElementById('register-form');
            const loginButton = document.querySelector('.form-toggle button:nth-child(1)');
            const registerButton = document.querySelector('.form-toggle button:nth-child(2)');

            if (action === 'login') {
                loginForm.style.display = 'block';
                registerForm.style.display = 'none';
                loginButton.classList.add('active');
                registerButton.classList.remove('active');
            } else {
                loginForm.style.display = 'none';
                registerForm.style.display = 'block';
                loginButton.classList.remove('active');
                registerButton.classList.add('active');
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            const loginForm = document.getElementById('login-form');
            if (loginForm && loginForm.style.display === 'block') {
                document.querySelector('.form-toggle button:nth-child(1)').classList.add('active');
            } else {
                document.querySelector('.form-toggle button:nth-child(2)').classList.add('active');
            }
        });
    </script>
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
            <header>
                <h1>Login / Register</h1>
            </header>
            <div class="line-break"></div>

            <?php if ($error): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <div class="form-toggle">
                <button class="toggle-button" onclick="toggleForm('login')">Login</button>
                <button class="toggle-button" onclick="toggleForm('register')">Register</button>
            </div>

            <form id="login-form" method="POST" class="form-card" style="display: block;">
                <input type="hidden" name="action" value="login">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                <label for="username">Email:</label>
                <input type="text" name="username" required maxlength="255">
                <label for="password">Password:</label>
                <input type="password" name="password" required>
                <button type="submit" class="submit-button">Login</button>
            </form>
            <p style="text-align: center; margin-top: 15px;">
                <a href="forgot_password.php?lang=<?php echo htmlspecialchars($lang); ?>">
                    <?php echo ($lang === 'cs' ? 'Zapomenuté heslo?' : 'Forgot Password?'); ?>
                </a>
            </p>

            <form id="register-form" method="POST" class="form-card" style="display: none;">
                <input type="hidden" name="action" value="register">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                <label for="firstname">First Name:</label>
                <input type="text" name="firstname" required maxlength="50" pattern="[A-Za-z\s]+">
                <label for="lastname">Last Name:</label>
                <input type="text" name="lastname" required maxlength="50" pattern="[A-Za-z\s]+">
                <label for="email">Email:</label>
                <input type="email" name="email" required maxlength="255">
                <label for="phone">Phone:</label>
                <input type="tel" name="phone" maxlength="20">
                <label for="password">Password:</label>
                <input type="password" name="password" required minlength="8" 
                       title="Password must contain at least 8 characters, including uppercase, lowercase and number">
                <label for="confirm_password">Confirm Password:</label>
                <input type="password" name="confirm_password" required>
                <button type="submit" class="submit-button">Register</button>
            </form>
        </main>
        <footer>
            <p>&copy; <span id="year"></span> Tools4Friends</p>
        </footer>
    </div>
</body>

</html>