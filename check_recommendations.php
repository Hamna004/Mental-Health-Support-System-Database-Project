<?php
require_once '../config/database.php';

// Only allows GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['success' => false, 'message' => 'Only GET requests allowed']);
    exit;
}

// Checks if the admin is authenticated
$admin_id = isset($_GET['admin_id']) ? intval($_GET['admin_id']) : 0;

if (!$admin_id) {
    echo json_encode(['success' => false, 'message' => 'Admin ID required']);
    exit;
}

$conn = getDBConnection();

// Verification 
$stmt = $conn->prepare("SELECT ROLE FROM USERS WHERE USER_ID = ? AND ROLE = 'ADMIN'");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    $stmt->close();
    $conn->close();
    exit;
}
$stmt->close();

// Getting all users 
$users_query = "SELECT USER_ID, USERNAME, EMAIL, FIRST_NAME, LAST_NAME, ROLE, DATE_CREATED, IS_ACTIVE FROM USERS ORDER BY DATE_CREATED DESC";
$users_result = $conn->query($users_query);

$all_users = [];
$total_users = 0;
$total_therapists = 0;
$total_patients = 0;

while ($row = $users_result->fetch_assoc()) {
    $user_data = [
        'id' => $row['USER_ID'],
        'username' => $row['USERNAME'],
        'email' => $row['EMAIL'],
        'name' => $row['FIRST_NAME'] . ' ' . $row['LAST_NAME'],
        'role' => $row['ROLE'],
        'date_created' => $row['DATE_CREATED'],
        'is_active' => $row['IS_ACTIVE']
    ];
    
    // specialty if therapist
    if ($row['ROLE'] === 'THERAPIST') {
        $therapist_stmt = $conn->prepare("SELECT SPECIALISATION, YEARS_OF_EXPERIENCE, HOURLY_RATE FROM THERAPIST_PROFILES WHERE THERAPIST_ID = ?");
        $therapist_stmt->bind_param("i", $row['USER_ID']);
        $therapist_stmt->execute();
        $therapist_result = $therapist_stmt->get_result();
        
        if ($therapist_result->num_rows > 0) {
            $therapist_data = $therapist_result->fetch_assoc();
            $user_data['specialty'] = $therapist_data['SPECIALISATION'];
            $user_data['experience'] = $therapist_data['YEARS_OF_EXPERIENCE'];
            $user_data['hourly_rate'] = $therapist_data['HOURLY_RATE'];
        }
        $therapist_stmt->close();
        $total_therapists++;
    }
    
    if ($row['ROLE'] === 'PATIENT') {
        $total_patients++;
    }
    
    $all_users[] = $user_data;
    $total_users++;
}

// total sessions
$sessions_query = "SELECT COUNT(*) as total FROM SESSIONS";
$sessions_result = $conn->query($sessions_query);
$total_sessions = $sessions_result->fetch_assoc()['total'];

// total mood logs
$mood_query = "SELECT COUNT(*) as total FROM MOOD_LOGS";
$mood_result = $conn->query($mood_query);
$total_mood_logs = $mood_result->fetch_assoc()['total'];

// total journals
$journal_query = "SELECT COUNT(*) as total FROM JOURNALS";
$journal_result = $conn->query($journal_query);
$total_journals = $journal_result->fetch_assoc()['total'];

// all mood logs with user details
$mood_logs_query = "SELECT ml.MOOD_LOG_ID, ml.MOOD, ml.INTENSITY, ml.LOG_DATE, ml.NOTES, ml.CREATED_AT,
                    u.USER_ID, u.FIRST_NAME, u.LAST_NAME, u.EMAIL
                    FROM MOOD_LOGS ml
                    JOIN USERS u ON ml.USER_ID = u.USER_ID
                    ORDER BY ml.CREATED_AT DESC";
$mood_logs_result = $conn->query($mood_logs_query);

$mood_logs = [];
while ($row = $mood_logs_result->fetch_assoc()) {
    $mood_logs[] = [
        'id' => $row['MOOD_LOG_ID'],
        'user_id' => $row['USER_ID'],
        'user_name' => $row['FIRST_NAME'] . ' ' . $row['LAST_NAME'],
        'user_email' => $row['EMAIL'],
        'mood' => $row['MOOD'],
        'intensity' => $row['INTENSITY'],
        'log_date' => $row['LOG_DATE'],
        'notes' => $row['NOTES'],
        'created_at' => $row['CREATED_AT']
    ];
}

// all sessions with patient and therapist details
$sessions_query = "SELECT s.SESSION_ID, s.SCHEDULED_DATE_TIME, s.DURATION_MINUTES, s.STATUS, s.SESSION_NOTES, s.PATIENT_FEEDBACK, s.CREATED_AT,
                   p.USER_ID as PATIENT_ID, p.FIRST_NAME as PATIENT_FNAME, p.LAST_NAME as PATIENT_LNAME, p.EMAIL as PATIENT_EMAIL,
                   t.USER_ID as THERAPIST_ID, t.FIRST_NAME as THERAPIST_FNAME, t.LAST_NAME as THERAPIST_LNAME, t.EMAIL as THERAPIST_EMAIL,
                   tp.SPECIALISATION
                   FROM SESSIONS s
                   JOIN USERS p ON s.PATIENT_ID = p.USER_ID
                   JOIN USERS t ON s.THERAPIST_ID = t.USER_ID
                   LEFT JOIN THERAPIST_PROFILES tp ON t.USER_ID = tp.THERAPIST_ID
                   ORDER BY s.SCHEDULED_DATE_TIME DESC";
$sessions_result = $conn->query($sessions_query);

$sessions = [];
while ($row = $sessions_result->fetch_assoc()) {
    $sessions[] = [
        'id' => $row['SESSION_ID'],
        'patient_id' => $row['PATIENT_ID'],
        'patient_name' => $row['PATIENT_FNAME'] . ' ' . $row['PATIENT_LNAME'],
        'patient_email' => $row['PATIENT_EMAIL'],
        'therapist_id' => $row['THERAPIST_ID'],
        'therapist_name' => $row['THERAPIST_FNAME'] . ' ' . $row['THERAPIST_LNAME'],
        'therapist_email' => $row['THERAPIST_EMAIL'],
        'specialization' => $row['SPECIALISATION'],
        'scheduled_date_time' => $row['SCHEDULED_DATE_TIME'],
        'duration_minutes' => $row['DURATION_MINUTES'],
        'status' => $row['STATUS'],
        'session_notes' => $row['SESSION_NOTES'],
        'patient_feedback' => $row['PATIENT_FEEDBACK'],
        'created_at' => $row['CREATED_AT']
    ];
}

// Returns dashboard data
echo json_encode([
    'success' => true,
    'data' => [
        'statistics' => [
            'total_users' => $total_users,
            'total_patients' => $total_patients,
            'total_therapists' => $total_therapists,
            'total_sessions' => $total_sessions,
            'total_mood_logs' => $total_mood_logs,
            'total_journals' => $total_journals
        ],
        'users' => $all_users,
        'mood_logs' => $mood_logs,
        'sessions' => $sessions
    ]
]);

$conn->close();
?>