<?php
// Include database connection
include 'db_connect.php';

// Get selected language from URL or default to English
$lang = $_GET['lang'] ?? 'en';

// Get selected category or default to 'vše'
$selected_category = $_GET['category'] ?? 'vše';

// Fetch categories
$category_sql = "SELECT DISTINCT category_name FROM Categories";
$category_result = $conn->query($category_sql);

// Fetch tools based on selected category
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
?>
<?php
// Include database connection
include 'db_connect.php';

// Get selected language from URL or default to English
$lang = $_GET['lang'] ?? 'en';

// Get selected category or default to 'vše'
$selected_category = $_GET['category'] ?? 'vše';

// Fetch categories
$category_sql = "SELECT DISTINCT category_name FROM Categories";
$category_result = $conn->query($category_sql);

// Fetch tools based on selected category
if ($selected_category === 'vše') {
    $tools_sql = "SELECT * FROM Tools";
} else {
    $tools_sql = "SELECT Tools.* FROM Tools 
                  JOIN ToolCategories ON Tools.tool_id = ToolCategories.tool_id 
                  JOIN Categories ON ToolCategories.category_id = Categories.category_id 
                  WHERE Categories.category_name = '$selected_category'";
}
$tools_result = $conn-&gt;query($tools_sql);
$tools = [];
while ($tool_row = $tools_result-&gt;fetch_assoc()) {
    $tools[] = $tool_row;
}
?&gt;

<!DOCTYPE html>

<html lang="&lt;?php echo $lang; ?&gt;">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<meta content="Borrowing tools from friends for friends" name="description"/>
<meta content="Tools for Friends, tools, naradi" name="keywords"/>
<meta content="MPCoding" name="author"/>

<link href="/favicon-dark.ico" rel="icon"/>
<title>Tools4Friends</title>
<link href="styles 1.css" rel="stylesheet"/></head>
<body>
<div class="container">

<header>
<div class="banner">
<img alt="Company Logo" src="/tools4friends_dark_Banner_2000x400.png"/>
</div>
</header>
<div class="line-break"></div>

<div class="line-break"></div>
<nav>
<div class="nav-left">
<a data-cs="Domů" data-en="Home" href="index.html?lang=&lt;?php echo $lang; ?&gt;">Home</a> 
                <a data-cs="Nářadí" data-en="Tools" href="tools.php?lang=&lt;?php echo $lang; ?&gt;">Tools</a> 
                <a data-cs="Kontakty" data-en="Contacts" href="contacts.html?lang=&lt;?php echo $lang; ?&gt;">Contacts</a>
</div>
<div class="nav-right language-toggle">
<button onclick="window.location.href='tools.php?lang=en'">English</button>   
                <button onclick="window.location.href='tools.php?lang=cs'">Čeština</button> 
            </div>
</nav>
<div class="content">
<h1 class="page_title"><?php echo $lang === 'cs' ? 'Nářadí' : 'Tools'; ?></h1>
<nav class="category-nav">
<a href="tools.php?category=vše&amp;lang=&lt;?php echo $lang; ?&gt;">
<?php echo $lang === 'cs' ? 'Vše' : 'All'; ?>
</a>
<?php while ($category_row = $category_result->fetch_assoc()): ?&gt;
                    <a href="tools.php?category=&lt;?php echo urlencode($category_row['category_name']); ?&gt;&amp;lang=&lt;?php echo $lang; ?&gt;">
<?php echo htmlspecialchars($category_row['category_name']); ?>
</a>
<?php endwhile; ?>
</nav>
<div class="tool-list">
<?php foreach ($tools as $tool): 
                    $name = $lang === 'cs' && !empty($tool['name_cs']) ? $tool['name_cs'] : $tool['name'];
                    $description = $lang === 'cs' && !empty($tool['description_cs']) ? $tool['description_cs'] : $tool['description'];
                    $technical_data = $lang === 'cs' && !empty($tool['technical_data_cs']) ? $tool['technical_data_cs'] : $tool['technical_data'];
                ?>
<div class="tool-block">
<img alt="&lt;?php echo htmlspecialchars($name); ?&gt;" src="&lt;?php echo $tool['picture']; ?&gt;"/>
<h3><?php echo htmlspecialchars($name); ?></h3>
<div class="left-text">
<p><strong><?php echo $lang === 'cs' ? 'Popis:' : 'Description:'; ?></strong> <?php echo htmlspecialchars($description); ?></p>
<p><strong><?php echo $lang === 'cs' ? 'Značka:' : 'Brand:'; ?></strong> <?php echo htmlspecialchars($tool['brand']); ?></p>
<p><strong><?php echo $lang === 'cs' ? 'Model:' : 'Model:'; ?></strong> <?php echo htmlspecialchars($tool['model']); ?></p>
<p><strong><?php echo $lang === 'cs' ? 'Technické Detaily:' : 'Technical Details:'; ?></strong> <?php echo htmlspecialchars($technical_data); ?></p>
<p><strong><?php echo $lang === 'cs' ? 'Majitel:' : 'Owner:'; ?></strong> <?php echo htmlspecialchars($tool['ownerID']); ?></p>
</div>
<div>
<a class="availability-button" href="&lt;?php echo $tool['availability_link']; ?&gt;">
<?php echo $lang === 'cs' ? 'Zkontrolovat Dostupnost' : 'Check Availability'; ?>
</a>
</div>
</div>
<?php endforeach; ?>
</div>
</div>

<footer>
<p>© <span id="year"></span> Tools4Friends</p>
<script>
            document.getElementById('year').textContent = new Date().getFullYear();
        </script>
</footer>

</div>
</body>
</html>
