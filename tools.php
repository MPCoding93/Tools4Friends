<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Borrowing tools from friends for friends">
    <meta name="keywords" content="Tools for Friends, tools, naradi">
    <meta name="author" content="MPCoding">
    <link rel="stylesheet" href="styles.css">
    <link rel="icon" href="favicon.ico" type="image/x-icon">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Libre+Baskerville:ital,wght@0,400;0,700;1,400&family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap"
        rel="stylesheet">
    <title>Tools4Friends</title>

</head>

<body>
    <div class="container">
        <header>
            <div class="banner">
                <img src="/tools4friends_dark_Banner_2000x400.png" alt="Company Logo">
            </div>
        </header>
        <div class="line-break"></div>
        <nav>
            <a href="index.html">Home</a>
            <a href="tools.php">Tools</a>
            <a href="contacts.html">Contacts</a>
        </nav>
        <div class="content">
            <h1 class="page_title">Tools</h1>
            <nav class="category-nav">
                <?php
                // Include database connection
                include 'db_connect.php';

                // Add "vše" category manually
                //echo '<a href="tools.php?category=vše">Vše</a>';
                
                $category_sql = "SELECT DISTINCT category_name FROM Categories";
                $category_result = $conn->query($category_sql);
                while ($category_row = $category_result->fetch_assoc()) {
                    echo '<a href="tools.php?category=' . $category_row['category_name'] . '">' . $category_row['category_name'] . '</a>';
                }
                ?>
            </nav>
            <div class="tool-list">
                <?php
                // Fetch tools based on selected category
                $selected_category = $_GET['category'] ?? 'vše';
                if ($selected_category === 'vše') {
                    $tools_sql = "SELECT * FROM Tools";
                } else {
                    $tools_sql = "SELECT Tools.* FROM Tools 
                                      JOIN ToolCategories ON Tools.tool_id = ToolCategories.tool_id 
                                      JOIN Categories ON ToolCategories.category_id = Categories.category_id 
                                      WHERE Categories.category_name = '$selected_category'";
                }
                $tools_result = $conn->query($tools_sql);
                $tools = [];
                while ($tool_row = $tools_result->fetch_assoc()) {
                    $tools[] = $tool_row;
                }

                foreach ($tools as $tool): ?>
                    <div class="tool-block">
                        <img src="<?php echo $tool['picture']; ?>" alt="<?php echo $tool['name']; ?>">
                        <h3><?php echo $tool['name']; ?></h3>
                        <div class="left-text">
                            <p><strong>Description:</strong> <?php echo $tool['description']; ?></p>
                            <p><strong>Brand:</strong> <?php echo $tool['brand']; ?></p>
                            <p><strong>Model:</strong> <?php echo $tool['model']; ?></p>
                            <p><strong>Technical Details:</strong> <?php echo $tool['technical_data']; ?></p>
                            <p><strong>Deposit:</strong> <?php echo $tool['deposit']; ?>Kč</p>
                            <p><strong>Owner:</strong> <?php echo $tool['ownerID']; ?></p>
                        </div>
                        <div><a href="comingsoon.html" class="availability-button">Check Availability</a></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <footer>
            <p>&copy; <span id="year"></span> Tools4Friends</p>
            <script>
                document.getElementById('year').textContent = new Date().getFullYear();
            </script>
        </footer>

</body>

</html>
