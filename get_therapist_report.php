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
    
    $therapist_id = $_GET['therapist_id'] ?? null;
    
    if (!$therapist_id) {
        throw new Exception('Therapist ID is required');
    }
    
    // all bookings for therapist
    $query = "SELECT 
                s.SESSION_ID,
                s.SCHEDULED_DATE_TIME,
                s.DURATION_MINUTES,
                s.STATUS,
                s.SESSION_NOTES,
                s.PATIENT_FEEDBACK,
                s.CREATED_AT,
                u.USER_ID as patient_id,
                u.FIRST_NAME as patient_first_name,
                u.LAST_NAME as patient_last_name,
                u.EMAIL as patient_email,
                u.PHONE_NUMBER as patient_phone
              FROM SESSIONS s
              JOIN USERS u ON s.PATIENT_ID = u.USER_ID
              WHERE s.THERAPIST_ID = ?
              ORDER BY s.SCHEDULED_DATE_TIME DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([$therapist_id]);
    $bookings = $stmt->fetchAll();
    
    
    $formatted_bookings = array_map(function($booking) {
        return [
            'session_id' => $booking['SESSION_ID'],
            'scheduled_datetime' => $booking['SCHEDULED_DATE_TIME'],
            'duration' => $booking['DURATION_MINUTES'],
            'status' => $booking['STATUS'],
            'notes' => $booking['SESSION_NOTES'],
            'feedback' => $booking['PATIENT_FEEDBACK'],
            'created_at' => $booking['CREATED_AT'],
            'patient' => [
                'id' => $booking['patient_id'],
                'name' => $booking['patient_first_name'] . ' ' . $booking['patient_last_name'],
                'email' => $booking['patient_email'],
                'phone' => $booking['patient_phone']
            ]
        ];
    }, $bookings);
    
    echo json_encode([
        'success' => true,
        'bookings' => $formatted_bookings,
        'count' => count($formatted_bookings)
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>