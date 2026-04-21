<?php

header('Content-Type: application/json');

$host = 'localhost';
$dbname = 'dbproject';
$username = 'root';
$password = '';

try {
    $conn = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    $session_id = $input['session_id'] ?? null;
    $status = $input['status'] ?? null;
    
    if (!$session_id || !$status) {
        throw new Exception('Session ID and status are required');
    }
    
    $allowed_statuses = ['BOOKED', 'COMPLETED', 'CANCELLED', 'NO-SHOW'];
    if (!in_array($status, $allowed_statuses)) {
        throw new Exception('Invalid status');
    }
    
    $query = "UPDATE SESSIONS SET STATUS = ? WHERE SESSION_ID = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$status, $session_id]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Booking status updated successfully'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>