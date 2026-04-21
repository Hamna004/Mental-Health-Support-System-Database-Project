<?php
require_once '../config/database.php';

// Allow all methods for debugging
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Only POST requests allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['recommendation_id']) || !isset($data['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Recommendation ID and User ID required']);
    exit;
}

$recommendation_id = intval($data['recommendation_id']);
$user_id = intval($data['user_id']);

try {
    // Use PDO like your resources.php
    $host = 'localhost';
    $dbname = 'dbproject';
    $username = 'root';
    $password = '';

    $conn = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );

    // Update only if it belongs to the user
    $sql = "UPDATE RECOMMENDATIONS SET IS_READ = 'Y' WHERE RECOMMENDATION_ID = ? AND USER_ID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$recommendation_id, $user_id]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Recommendation marked as read']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Recommendation not found or already read']);
    }

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>