<?php
// Start session and check authentication
session_start();
require_once 'db_connect.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?lang=" . ($_GET['lang'] ?? 'en'));
    exit();
}

// Get the language from the URL or default to English
$lang = $_GET['lang'] ?? 'en';

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';
$password_error = '';
$profile_error = '';

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
        $stmt_update = $conn->prepare("UPDATE Users SET firstname = ?, lastname = ?, phone = ? WHERE user_id = ?");
        $stmt_update->bind_param("sssi", $firstname, $lastname, $phone, $user_id);
        if ($stmt_update->execute()) {
            $_SESSION['firstname'] = $firstname;
            $_SESSION['lastname'] = $lastname;
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

// Navbar variables
$loggedIn = true;
$fullName = htmlspecialchars($_SESSION['firstname'] . ' ' . $_SESSION['lastname']);
?>
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
