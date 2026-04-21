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
    
    $patient_id = $input['patient_id'] ?? null;
    $therapist_id = $input['therapist_id'] ?? null;
    $scheduled_date = $input['scheduled_date'] ?? null;
    $scheduled_time = $input['scheduled_time'] ?? null;
    $duration = $input['duration'] ?? 60;
    $notes = $input['notes'] ?? null;
    
    // Validation
    if (!$patient_id || !$therapist_id) {
        throw new Exception('Patient ID and Therapist ID are required');
    }
    
    if (!$scheduled_date || !$scheduled_time) {
        throw new Exception('Date and time are required');
    }
    
    $scheduled_datetime = $scheduled_date . ' ' . $scheduled_time . ':00';
    
    // Check if therapist is accepting clients
    $check_query = "SELECT IS_ACCEPTING_NEW_CLIENTS 
                   FROM THERAPIST_PROFILES 
                   WHERE THERAPIST_ID = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->execute([$therapist_id]);
    $therapist = $check_stmt->fetch();
    
    if (!$therapist || $therapist['IS_ACCEPTING_NEW_CLIENTS'] !== 'Y') {
        throw new Exception('Therapist is not accepting new clients');
    }
    
    // next session ID
    $id_query = "SELECT COALESCE(MAX(SESSION_ID), 0) + 1 as next_id FROM SESSIONS";
    $id_result = $conn->query($id_query);
    $next_id = $id_result->fetch()['next_id'];
    
    // Insert session
    $insert_query = "INSERT INTO SESSIONS 
                    (SESSION_ID, PATIENT_ID, THERAPIST_ID, SCHEDULED_DATE_TIME, 
                     DURATION_MINUTES, STATUS, SESSION_NOTES, CREATED_AT)
                    VALUES (?, ?, ?, ?, ?, 'BOOKED', ?, NOW())";
    
    $stmt = $conn->prepare($insert_query);
    $stmt->execute([
        $next_id,
        $patient_id,
        $therapist_id,
        $scheduled_datetime,
        $duration,
        $notes
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Session booked successfully!',
        'session_id' => $next_id,
        'scheduled_for' => $scheduled_datetime
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>