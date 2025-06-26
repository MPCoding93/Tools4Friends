<?php
// [Previous PHP code remains exactly the same until the <head> section]
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo ($lang === 'cs' ? 'Můj Profil' : 'My Profile'); ?> - Tools4Friends</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="icon" href="/favicon-dark.ico" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Libre+Baskerville:ital,wght@0,400;0,700;1,400&family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
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
            <h1><?php echo ($lang === 'cs' ? 'Můj Profil' : 'My Profile'); ?></h1>
            <div class="line-break"></div>

            <?php if ($error): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="success-message"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <div class="profile-container">
                <div>
                    <section class="profile-section form-card">
                        <div class="profile-header">
                            <img src="<?php echo !empty($user['profile_picture']) ? htmlspecialchars($user['profile_picture']) : 'images/default-profile.jpg'; ?>" 
                                 alt="<?php echo htmlspecialchars($fullName); ?>" 
                                 class="profile-picture" 
                                 id="profile-preview">
                            <div class="profile-name">
                                <h2><?php echo htmlspecialchars($fullName); ?></h2>
                                <p>ID: <?php echo $user_id; ?></p>
                            </div>
                        </div>

                        <form method="POST" enctype="multipart/form-data">
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

                            <div class="picture-upload">
                                <label for="profile_picture" class="upload-button"><?php echo ($lang === 'cs' ? 'Nahrát novou fotku' : 'Upload new photo'); ?></label>
                                <input type="file" id="profile_picture" name="profile_picture" accept="image/*">
                                <div class="picture-preview"></div>
                            </div>

                            <?php if ($profile_error): ?>
                                <div class="error-message"><?php echo htmlspecialchars($profile_error); ?></div>
                            <?php endif; ?>

                            <button type="submit" name="update_profile" class="submit-button" style="margin-top: 20px;">
                                <?php echo ($lang === 'cs' ? 'Aktualizovat Profil' : 'Update Profile'); ?>
                            </button>
                        </form>
                    </section>

                    <section class="profile-section form-card" style="margin-top: 30px;">
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
                </div>

                <div>
                    <section class="profile-section form-card">
                        <h2><?php echo ($lang === 'cs' ? 'Moje Nářadí' : 'My Tools'); ?></h2>
                        <?php if (empty($user_tools)): ?>
                            <p><?php echo ($lang === 'cs' ? 'Zatím nemáte žádné nástroje.' : 'You have not listed any tools yet.'); ?></p>
                            <a href="add_tool.php?lang=<?php echo $lang; ?>" class="availability-button"><?php echo ($lang === 'cs' ? 'Přidat Nový Nástroj' : 'Add New Tool'); ?></a>
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
                                            <div class="tool-actions">
                                                <a href="edit_tool.php?tool_id=<?php echo $tool['tool_id']; ?>&lang=<?php echo $lang; ?>" class="btn btn-blue"><?php echo ($lang === 'cs' ? 'Upravit' : 'Edit'); ?></a>
                                                <a href="tool_availability.php?tool_id=<?php echo $tool['tool_id']; ?>&lang=<?php echo $lang; ?>" class="btn btn-blue"><?php echo ($lang === 'cs' ? 'Dostupnost' : 'Availability'); ?></a>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <a href="add_tool.php?lang=<?php echo $lang; ?>" class="availability-button" style="margin-top: 20px;"><?php echo ($lang === 'cs' ? 'Přidat Nový Nástroj' : 'Add New Tool'); ?></a>
                        <?php endif; ?>
                    </section>
                </div>
            </div>
        </main>

        <footer>
            <p>&copy; <span id="year"></span> Tools4Friends</p>
        </footer>
    </div>
</body>
</html>
