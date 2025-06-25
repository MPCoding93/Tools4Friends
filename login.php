<?php
session_start();
require_once 'db_connect.php'; // Your DB connection file

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];

    if ($action === 'login') {
        $username = trim($_POST['username']);
        $password = $_POST['password'];

        if (!empty($username) && !empty($password)) {
            // LOGIN
            $stmt = $conn->prepare("SELECT user_id, username, password_hash FROM Users WHERE username = ? OR email = ?");
            $stmt->bind_param("ss", $username, $username);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows === 1) {
                $stmt->bind_result($user_id, $db_username, $password_hash);
                $stmt->fetch();

                if (password_verify($password, $password_hash)) {
                    $_SESSION['user_id'] = $user_id;
                    $_SESSION['username'] = $db_username;
                    header("Location: index.html"); // Redirect after successful login
                    exit();
                } else {
                    $error = "Invalid credentials.";
                }
            } else {
                $error = "User  not found.";
            }

            $stmt->close();
        } else {
            $error = "Please enter both email and password.";
        }
    } elseif ($action === 'register') {
        $firstname = trim($_POST['firstname']);
        $lastname = trim($_POST['lastname']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];

        // Validate registration fields
        if (empty($firstname) || empty($lastname) || empty($email) || empty($password) || empty($confirm_password)) {
            $error = "Please fill in all mandatory fields.";
        } elseif ($password !== $confirm_password) {
            $error = "Passwords do not match.";
        } else {
            // Check if email already exists
            $stmt = $conn->prepare("SELECT user_id FROM Users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows > 0) {
                $error = "Email already exists.";
            } else {
                // Insert new user into the database
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt->close();
                $stmt = $conn->prepare("INSERT INTO Users (firstname, lastname, email, phone, password_hash) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("sssss", $firstname, $lastname, $email, $phone, $password_hash);
                if ($stmt->execute()) {
                    // Log the user in after successful registration
                    $_SESSION['user_id'] = $stmt->insert_id;
                    $_SESSION['username'] = $firstname; // or any other identifier
                    header("Location: index.html"); // Redirect after successful registration
                    exit();
                } else {
                    $error = "Registration failed. Please try again.";
                }
            }
            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles.css">
    <link rel="icon" href="/favicon-dark.ico" />
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
            if (action === 'login') {
                loginForm.style.display = 'block';
                registerForm.style.display = 'none';
            } else {
                loginForm.style.display = 'none';
                registerForm.style.display = 'block';
            }
        }
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

        <nav>
            <div class="nav-left">
                <a href="index.html" data-en="Home" data-cs="Domů">Home</a>
                <a href="tools.php" data-en="Tools" data-cs="Nářadí">Tools</a>
                <a href="contacts.html" data-en="Contacts" data-cs="Kontakty">Contacts</a>
            </div>

            <div class="nav-right language-toggle">
                <button onclick="smartLanguageSwitch('en')">English</button>
                <button onclick="smartLanguageSwitch('cs')">Čeština</button>
            </div>
        </nav>
        <div class="container">
    <header>
        <h1>Login / Register</h1>
    </header>
    <div class="line-break"></div>

    <?php if ($error): ?>
        <div class="error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="form-toggle">
        <button class="toggle-button" onclick="toggleForm('login')">Login</button>
        <button class="toggle-button" onclick="toggleForm('register')">Register</button>
    </div>

    <form id="login-form" method="POST" class="form" style="display: block;">
        <input type="hidden" name="action" value="login">
        <label for="username">Email:</label>
        <input type="text" name="username" required>
        <label for="password">Password:</label>
        <input type="password" name="password" required>
        <button type="submit" class="submit-button">Login</button>
    </form>

    <form id="register-form" method="POST" class="form" style="display: none;">
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
</div>

</body>

</html>
