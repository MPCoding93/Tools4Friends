<?php
session_start();
require_once 'db_connect.php'; // Your DB connection file

$error = '';
$success = '';

// Get selected language from URL or default to English
$lang = $_GET['lang'] ?? 'en';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];

    if ($action === 'login') {
        $username = trim($_POST['username']); // This can be email or username
        $password = $_POST['password'];

        if (!empty($username) && !empty($password)) {
            // LOGIN
            // Prepare statement to fetch user by username or email
            $stmt = $conn->prepare("SELECT user_id, firstname, lastname, password_hash FROM Users WHERE username = ? OR email = ?");
            $stmt->bind_param("ss", $username, $username);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows === 1) {
                $stmt->bind_result($user_id, $db_firstname, $db_lastname, $password_hash);
                $stmt->fetch();

                if (password_verify($password, $password_hash)) {
                    $_SESSION['user_id'] = $user_id;
                    $_SESSION['firstname'] = $db_firstname;
                    $_SESSION['lastname'] = $db_lastname;
                    // Redirect to index.php with current language if available
                    header("Location: index.php?lang=" . $lang); // Changed to index.php
                    exit();
                } else {
                    $error = "Invalid credentials.";
                }
            } else {
                $error = "User not found.";
            }

            $stmt->close();
        } else {
            $error = "Please enter both email and password.";
        }
    } elseif ($action === 'register') {
        $firstname = trim($_POST['firstname']);
        $lastname = trim($_POST['lastname']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']); // This column should now exist
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];

        // Validate registration fields
        if (empty($firstname) || empty($lastname) || empty($email) || empty($password) || empty($confirm_password)) {
            $error = "Please fill in all mandatory fields.";
        } elseif ($password !== $confirm_password) {
            $error = "Passwords do not match.";
        } else {
            // --- Email Uniqueness Check ---
            $stmt_check_email = $conn->prepare("SELECT user_id FROM Users WHERE email = ?");
            $stmt_check_email->bind_param("s", $email);
            $stmt_check_email->execute();
            $stmt_check_email->store_result();

            if ($stmt_check_email->num_rows > 0) {
                $error = "Email address is already in use. Please use a different email or log in.";
                $stmt_check_email->close();
            } else {
                $stmt_check_email->close(); // Close the check statement

                // Insert new user into the database
                $password_hash = password_hash($password, PASSWORD_DEFAULT);

                $stmt_insert = $conn->prepare("INSERT INTO Users (firstname, lastname, email, phone, password_hash) VALUES (?, ?, ?, ?, ?)");
                $stmt_insert->bind_param("sssss", $firstname, $lastname, $email, $phone, $password_hash);

                if ($stmt_insert->execute()) {
                    // --- Automatic Login after successful registration ---
                    $_SESSION['user_id'] = $stmt_insert->insert_id;
                    $_SESSION['firstname'] = $firstname;
                    $_SESSION['lastname'] = $lastname;

                    // Redirect to index.php with current language if available
                    header("Location: index.php?lang=" . $lang); // Changed to index.php
                    exit();
                } else {
                    $error = "Registration failed. Please try again. " . $conn->error; // Added $conn->error for debugging
                }
                $stmt_insert->close();
            }
        }
    }
}

// --- User Login Status for Navbar ---
// These variables need to be defined BEFORE including navbar.php
$loggedIn = isset($_SESSION['user_id']);
$fullName = '';
if ($loggedIn) {
    $fullName = htmlspecialchars($_SESSION['firstname'] . ' ' . $_SESSION['lastname']);
}
?>

<!DOCTYPE html>
<html lang="<?php echo $lang; ?>"> <!-- Added lang attribute -->

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/styles.css"> <!-- Changed to absolute path -->
    <link rel="icon" href="/favicon-dark.ico" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <meta name="description" content="Borrowing tools from friends for friends" />
    <meta name="keywords" content="Tools for Friends, tools, naradi" />
    <meta name="author" content="MPCoding" />
    <link
        href="https://fonts.googleapis.com/css2?family=Libre+Baskerville:ital,wght@0,400;0,700;1,400&family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap"
        rel="stylesheet" />
    <script src="/script.js" defer></script> <!-- Changed to absolute path -->
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

        // Set initial active state for login/register toggle buttons on page load
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
                <img src="/tools4friends_dark_Banner_2000x400.png" alt="Company Logo" />
            </div>
        </header>
        <div class="line-break"></div>

        <?php include 'navbar.php'; // Include the navbar ?>

        <main> <!-- Added main tag here -->
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
                <label for="username">Email:</label>
                <input type="text" name="username" required>
                <label for="password">Password:</label>
                <input type="password" name="password" required>
                <button type="submit" class="submit-button">Login</button>
            </form>

            <form id="register-form" method="POST" class="form-card" style="display: none;">
                <input type="hidden" name="action" value="register">
                <label for="firstname">First Name:</label>
                <input type="text" name="firstname" required>
                <label for="lastname">Last Name:</label>
                <input type="text" name="lastname" required>
                <label for="email">Email:</label>
                <input type="email" name="email" required>
                <label for="phone">Phone:</label>
                <input type="text" name="phone">
                <label for="password">Password:</label>
                <input type="password" name="password" required>
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
