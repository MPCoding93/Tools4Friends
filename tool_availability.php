<?php
// tool_availability.php

// Include database connection
include 'db_connect.php';

// Get tool ID
$tool_id = isset($_GET['tool_id']) ? intval($_GET['tool_id']) : 0;

// Fetch tool details
$stmt = $conn->prepare("SELECT * FROM Tools WHERE tool_id = ?");
$stmt->bind_param("i", $tool_id);
$stmt->execute();
$result = $stmt->get_result();
$tool = $result->fetch_assoc();

if (!$tool) {
    echo "<h2>Tool not found.</h2>";
    exit;
}
?>

// Fetch availability ranges using MySQLi
$availability_stmt = $conn-&gt;prepare("SELECT start_date, end_date FROM Availability WHERE tool_id = ? AND is_available
= 0");
$availability_stmt-&gt;bind_param("i", $tool_id);
$availability_stmt-&gt;execute();
$availability_result = $availability_stmt-&gt;get_result();
$unavailable_ranges = [];

while ($row = $availability_result-&gt;fetch_assoc()) {
$unavailable_ranges[] = $row;
}
?&gt;

<!DOCTYPE html>

<html>

<head>
    <title><?php echo htmlspecialchars($tool['name']); ?> - Availability</title>
    <link href="styles.css" rel="stylesheet" />
    <style>
        .calendar-nav {
            display: flex;
            justify-content: space-between;
            margin: 10px 0;
        }

        .calendar {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 5px;
        }

        .calendar-day {
            padding: 10px;
            border: 1px solid #ccc;
            text-align: center;
        }

        .unavailable {
            background-color: #f88;
        }
    </style>

</head>

<body>
    <div class="container">
        <header>
            <div class="banner">
                <img alt="Company Logo" src="/tools4friends_dark_Banner_2000x400.png" />
            </div>
        </header>
        <div class="line-break"></div>
        <nav>
            <div class="nav-left">
                <a data-cs="Domů" data-en="Home" href="index.html">Home</a>  
                <a data-cs="Nářadí" data-en="Tools" href="tools.php">Tools</a>  
                <a data-cs="Kontakty" data-en="Contacts" href="contacts.html">Contacts</a>
            </div>
            <div class="nav-right language-toggle">
                <button onclick="setLanguage('en')">English</button>    
                <button onclick="setLanguage('cs')">Čeština</button>  
            </div>
        </nav>
        <main>
            <h1><?php echo htmlspecialchars($tool['name']); ?></h1>
            <p><strong>Description:</strong> <?php echo htmlspecialchars($tool['description']); ?></p>
            <p><strong>Brand:</strong> <?php echo htmlspecialchars($tool['brand']); ?></p>
            <p><strong>Model:</strong> <?php echo htmlspecialchars($tool['model']); ?></p>
            <p><strong>Technical Data:</strong> <?php echo htmlspecialchars($tool['technical_data']); ?></p>
            <p><strong>Deposit:</strong> $<?php echo htmlspecialchars($tool['deposit']); ?></p>
            <div class="calendar-nav">
                <button onclick="changeMonth(-1)">← Previous</button>
                <h2 id="calendar-month"></h2>
                <button onclick="changeMonth(1)">Next →</button>
            </div>
            <div class="calendar" id="calendar"></div>
        </main>

    </div>
    <script src="script.js"></script>
</body>

</html>
