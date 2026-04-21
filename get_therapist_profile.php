<?php
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['success' => false, 'message' => 'Only GET requests allowed']);
    exit;
}

$conn = getDBConnection();

// get all active therapists with their profiles
$sql = "SELECT 
    u.USER_ID,
    u.FIRST_NAME,
    u.LAST_NAME,
    u.EMAIL,
    tp.SPECIALISATION,
    tp.QUALIFICATIONS,
    tp.YEARS_OF_EXPERIENCE,
    tp.BIO,
    tp.HOURLY_RATE,
    tp.IS_ACCEPTING_NEW_CLIENTS
FROM USERS u
INNER JOIN THERAPIST_PROFILES tp ON u.USER_ID = tp.THERAPIST_ID
WHERE u.ROLE = 'THERAPIST' 
AND u.IS_ACTIVE = 'Y'
ORDER BY u.FIRST_NAME ASC";

$result = $conn->query($sql);

if ($result === false) {
    echo json_encode([
        'success' => false, 
        'message' => 'Database query failed: ' . $conn->error
    ]);
    $conn->close();
    exit;
}

$therapists = [];

while ($row = $result->fetch_assoc()) {
    $therapists[] = [
        'id' => $row['USER_ID'],
        'name' => 'Dr. ' . $row['FIRST_NAME'] . ' ' . $row['LAST_NAME'],
        'firstName' => $row['FIRST_NAME'],
        'lastName' => $row['LAST_NAME'],
        'email' => $row['EMAIL'],
        'specialty' => $row['SPECIALISATION'] ?? 'General Mental Health',
        'qualifications' => $row['QUALIFICATIONS'] ?? 'Licensed Professional',
        'bio' => $row['BIO'] ?? 'Dedicated mental health professional',
        'experience' => $row['YEARS_OF_EXPERIENCE'] ?? 0,
        'hourlyRate' => $row['HOURLY_RATE'] ?? 0,
        'acceptingClients' => $row['IS_ACCEPTING_NEW_CLIENTS'] === 'Y'
    ];
}

echo json_encode([
    'success' => true,
    'therapists' => $therapists,
    'count' => count($therapists)
]);

$conn->close();
?>