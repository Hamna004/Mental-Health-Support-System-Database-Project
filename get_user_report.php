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
    
    // therapist full profile
    $query = "SELECT 
                u.USER_ID,
                u.USERNAME,
                u.EMAIL,
                u.FIRST_NAME,
                u.LAST_NAME,
                u.PHONE_NUMBER,
                tp.SPECIALISATION,
                tp.QUALIFICATIONS,
                tp.YEARS_OF_EXPERIENCE,
                tp.BIO,
                tp.HOURLY_RATE,
                tp.IS_ACCEPTING_NEW_CLIENTS
              FROM USERS u
              JOIN THERAPIST_PROFILES tp ON u.USER_ID = tp.THERAPIST_ID
              WHERE u.USER_ID = ? AND u.ROLE = 'THERAPIST' AND u.IS_ACTIVE = 'Y'";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([$therapist_id]);
    $therapist = $stmt->fetch();
    
    if (!$therapist) {
        throw new Exception('Therapist not found');
    }
    
    //therapist's specializations
    $spec_query = "SELECT s.SPECIALIZATION_NAME
                   FROM THERAPIST_SPECIALIZATIONS ts
                   JOIN SPECIALIZATIONS s ON ts.SPECIALIZATION_ID = s.SPECIALIZATION_ID
                   WHERE ts.THERAPIST_ID = ?";
    $spec_stmt = $conn->prepare($spec_query);
    $spec_stmt->execute([$therapist_id]);
    $specializations = $spec_stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // completed sessions count
    $sessions_query = "SELECT COUNT(*) as session_count 
                      FROM SESSIONS 
                      WHERE THERAPIST_ID = ? AND STATUS = 'COMPLETED'";
    $sessions_stmt = $conn->prepare($sessions_query);
    $sessions_stmt->execute([$therapist_id]);
    $session_data = $sessions_stmt->fetch();
    

    $profile = [
        'id' => $therapist['USER_ID'],
        'name' => $therapist['FIRST_NAME'] . ' ' . $therapist['LAST_NAME'],
        'username' => $therapist['USERNAME'],
        'email' => $therapist['EMAIL'],
        'phone' => $therapist['PHONE_NUMBER'],
        'specialty' => $therapist['SPECIALISATION'],
        'specializations' => $specializations,
        'qualifications' => $therapist['QUALIFICATIONS'],
        'experience' => $therapist['YEARS_OF_EXPERIENCE'],
        'bio' => $therapist['BIO'],
        'hourlyRate' => $therapist['HOURLY_RATE'],
        'acceptingClients' => $therapist['IS_ACCEPTING_NEW_CLIENTS'] === 'Y',
        'completedSessions' => $session_data['session_count']
    ];
    
    echo json_encode([
        'success' => true,
        'profile' => $profile
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>