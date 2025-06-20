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

// Fetch availability ranges using MySQLi
$availability_stmt = $conn->prepare("SELECT start_date, end_date FROM Availability WHERE tool_id = ? AND is_available = 0");
$availability_stmt->bind_param("i", $tool_id);
$availability_stmt->execute();
$availability_result = $availability_stmt->get_result();
$unavailable_ranges = [];

while ($row = $availability_result->fetch_assoc()) {
    $unavailable_ranges[] = $row;
}
?>

<!DOCTYPE html>
<html>

<head>
    <title><?php echo htmlspecialchars($tool['name']); ?> - Availability</title>
    <link rel="stylesheet" href="styles.css">
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
    <script>
        function setLanguage(lang) {
            document.querySelectorAll("[data-en]").forEach((el) => {
                el.textContent = el.getAttribute(`data-${lang}`);
            });
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
                <a href="index.html" data-en="Home" data-cs="Domů">Home</a> &nbsp;
                <a href="tools.php" data-en="Tools" data-cs="Nářadí">Tools</a> &nbsp;
                <a href="contacts.html" data-en="Contacts" data-cs="Kontakty">Contacts</a>
            </div>

            <div class="nav-right language-toggle">
                <button onclick="setLanguage('en')">English</button> &nbsp;&nbsp;&nbsp;
                <button onclick="setLanguage('cs')">Čeština</button> &nbsp;
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

        <script>
            const unavailableRanges = <?php echo json_encode($unavailable_ranges); ?>;
            let currentDate = new Date();

            function renderCalendar(date) {
                const calendar = document.getElementById('calendar');
                const monthLabel = document.getElementById('calendar-month');
                calendar.innerHTML = '';

                const year = date.getFullYear();
                const month = date.getMonth();
                const firstDay = new Date(year, month, 1);
                const lastDay = new Date(year, month + 1, 0);
                const startDay = firstDay.getDay();

                monthLabel.textContent = date.toLocaleString('default', { month: 'long', year: 'numeric' });

                for (let i = 0; i < startDay; i++) {
                    const emptyCell = document.createElement('div');
                    calendar.appendChild(emptyCell);
                }

                for (let day = 1; day <= lastDay.getDate(); day++) {
                    const cell = document.createElement('div');
                    cell.className = 'calendar-day';
                    const cellDate = new Date(year, month, day);
                    const cellDateStr = cellDate.toISOString().split('T')[0];

                    for (const range of unavailableRanges) {
                        if (cellDateStr >= range.start_date && cellDateStr <= range.end_date) {
                            cell.classList.add('unavailable');
                            break;
                        }
                    }

                    cell.textContent = day;
                    calendar.appendChild(cell);
                }
            }

            function changeMonth(offset) {
                currentDate.setMonth(currentDate.getMonth() + offset);
                renderCalendar(currentDate);
            }

            renderCalendar(currentDate);
        </script>
    </div>
</body>

</html>
