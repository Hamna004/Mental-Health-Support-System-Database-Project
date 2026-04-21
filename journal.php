<?php
// api/get_therapist_report.php - Therapist activity report
header('Content-Type: application/json');

$host = 'localhost';
$dbname = 'dbproject';
$username = 'root';
$password = '';

try {
    $conn = new mysqli($host, $username, $password, $dbname);
    if ($conn->connect_error) {
        die(json_encode(['success' => false, 'message' => 'Connection failed']));
    }
} catch (Exception $e) {
    die(json_encode(['success' => false, 'message' => 'Database error']));
}

$therapist_id = isset($_GET['therapist_id']) ? intval($_GET['therapist_id']) : 0;
$period = isset($_GET['period']) ? $_GET['period'] : 'month';

if (!$therapist_id) {
    die(json_encode(['success' => false, 'message' => 'Therapist ID required']));
}

$days = $period === 'week' ? 7 : ($period === 'month' ? 30 : 365);

// 1. Total sessions conducted
$sessionsQuery = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN STATUS = 'COMPLETED' THEN 1 ELSE 0 END) as completed,
                    SUM(CASE WHEN STATUS = 'BOOKED' THEN 1 ELSE 0 END) as upcoming,
                    SUM(CASE WHEN STATUS = 'CANCELLED' THEN 1 ELSE 0 END) as cancelled
                  FROM SESSIONS 
                  WHERE THERAPIST_ID = ? AND CREATED_AT >= DATE_SUB(NOW(), INTERVAL ? DAY)";
$stmt = $conn->prepare($sessionsQuery);
$stmt->bind_param("ii", $therapist_id, $days);
$stmt->execute();
$sessionsData = $stmt->get_result()->fetch_assoc();

// 2. Unique patients
$patientsQuery = "SELECT COUNT(DISTINCT PATIENT_ID) as unique_patients 
                  FROM SESSIONS 
                  WHERE THERAPIST_ID = ? AND CREATED_AT >= DATE_SUB(NOW(), INTERVAL ? DAY)";
$stmt = $conn->prepare($patientsQuery);
$stmt->bind_param("ii", $therapist_id, $days);
$stmt->execute();
$uniquePatients = $stmt->get_result()->fetch_assoc()['unique_patients'];

// 3. Resources uploaded
$resourcesQuery = "SELECT COUNT(*) as total FROM RESOURCES WHERE THERAPIST_ID = ? AND DATE_UPLOADED >= DATE_SUB(NOW(), INTERVAL ? DAY)";
$stmt = $conn->prepare($resourcesQuery);
$stmt->bind_param("ii", $therapist_id, $days);
$stmt->execute();
$resourcesUploaded = $stmt->get_result()->fetch_assoc()['total'];

// 4. Sessions per day trend
$trendQuery = "SELECT DATE(SCHEDULED_DATE_TIME) as date, COUNT(*) as sessions
               FROM SESSIONS 
               WHERE THERAPIST_ID = ? AND SCHEDULED_DATE_TIME >= DATE_SUB(NOW(), INTERVAL 14 DAY)
               GROUP BY DATE(SCHEDULED_DATE_TIME)
               ORDER BY date ASC";
$stmt = $conn->prepare($trendQuery);
$stmt->bind_param("i", $therapist_id);
$stmt->execute();
$trendResult = $stmt->get_result();

$sessionTrend = [];
while ($row = $trendResult->fetch_assoc()) {
    $sessionTrend[] = $row;
}

// 5. Patient list
$patientListQuery = "SELECT DISTINCT 
                        u.USER_ID,
                        u.FIRST_NAME,
                        u.LAST_NAME,
                        u.EMAIL,
                        COUNT(s.SESSION_ID) as total_sessions,
                        MAX(s.SCHEDULED_DATE_TIME) as last_session
                     FROM SESSIONS s
                     JOIN USERS u ON s.PATIENT_ID = u.USER_ID
                     WHERE s.THERAPIST_ID = ?
                     GROUP BY u.USER_ID
                     ORDER BY total_sessions DESC
                     LIMIT 10";
$stmt = $conn->prepare($patientListQuery);
$stmt->bind_param("i", $therapist_id);
$stmt->execute();
$patientResult = $stmt->get_result();

$patientList = [];
while ($row = $patientResult->fetch_assoc()) {
    $patientList[] = $row;
}

echo json_encode([
    'success' => true,
    'period' => $period,
    'days' => $days,
    'report' => [
        'summary' => [
            'total_sessions' => $sessionsData['total'],
            'completed_sessions' => $sessionsData['completed'],
            'upcoming_sessions' => $sessionsData['upcoming'],
            'cancelled_sessions' => $sessionsData['cancelled'],
            'unique_patients' => $uniquePatients,
            'resources_uploaded' => $resourcesUploaded
        ],
        'session_trend' => $sessionTrend,
        'top_patients' => $patientList
    ]
]);

$conn->close();
?>