<?php
require_once __DIR__ . '/../app/security.php';
require_once __DIR__ . '/../app/db_connect.php';

startSecureSession();

// Get tool_id from GET parameter
$tool_id = isset($_GET['tool_id']) ? intval($_GET['tool_id']) : 0;

if ($tool_id <= 0) {
    http_response_code(400);
    echo 'Invalid tool_id';
    exit();
}

// Query the database for the picture
$stmt = $conn->prepare("SELECT picture FROM Tools WHERE tool_id = ?");
$stmt->bind_param("i", $tool_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $tool = $result->fetch_assoc();
    // Output the picture value directly (can be URL or HTML)
    echo $tool['picture'];
} else {
    http_response_code(404);
    echo 'Tool not found';
}

$stmt->close();
$conn->close();
?>
