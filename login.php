<?php
// api/get_user_report.php - Generate user mental health report
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

$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
$period = isset($_GET['period']) ? $_GET['period'] : 'month'; // week, month, year

if (!$user_id) {
    die(json_encode(['success' => false, 'message' => 'User ID required']));
}

$days = $period === 'week' ? 7 : ($period === 'month' ? 30 : 365);

// 1. Mood Statistics
$moodStats = [];
$moodQuery = "SELECT 
                MOOD,
                COUNT(*) as count,
                AVG(INTENSITY) as avg_intensity,
                MAX(INTENSITY) as max_intensity,
                MIN(INTENSITY) as min_intensity
              FROM MOOD_LOGS 
              WHERE USER_ID = ? AND CREATED_AT >= DATE_SUB(NOW(), INTERVAL ? DAY)
              GROUP BY MOOD
              ORDER BY count DESC";

$stmt = $conn->prepare($moodQuery);
$stmt->bind_param("ii", $user_id, $days);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $moodStats[] = $row;
}

// 2. Total mood logs
$totalQuery = "SELECT COUNT(*) as total FROM MOOD_LOGS WHERE USER_ID = ? AND CREATED_AT >= DATE_SUB(NOW(), INTERVAL ? DAY)";
$stmt = $conn->prepare($totalQuery);
$stmt->bind_param("ii", $user_id, $days);
$stmt->execute();
$totalLogs = $stmt->get_result()->fetch_assoc()['total'];

// 3. Average intensity
$avgQuery = "SELECT AVG(INTENSITY) as avg_intensity FROM MOOD_LOGS WHERE USER_ID = ? AND CREATED_AT >= DATE_SUB(NOW(), INTERVAL ? DAY)";
$stmt = $conn->prepare($avgQuery);
$stmt->bind_param("ii", $user_id, $days);
$stmt->execute();
$avgIntensity = round($stmt->get_result()->fetch_assoc()['avg_intensity'] ?? 0, 1);

// 4. Mood trend (last 14 days)
$trendQuery = "SELECT DATE(CREATED_AT) as date, AVG(INTENSITY) as avg_intensity, COUNT(*) as logs
               FROM MOOD_LOGS 
               WHERE USER_ID = ? AND CREATED_AT >= DATE_SUB(NOW(), INTERVAL 14 DAY)
               GROUP BY DATE(CREATED_AT)
               ORDER BY date ASC";
$stmt = $conn->prepare($trendQuery);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$trendResult = $stmt->get_result();

$moodTrend = [];
while ($row = $trendResult->fetch_assoc()) {
    $moodTrend[] = $row;
}

// 5. Journal activity
$journalQuery = "SELECT COUNT(*) as total FROM JOURNALS WHERE USER_ID = ? AND CREATED_AT >= DATE_SUB(NOW(), INTERVAL ? DAY)";
$stmt = $conn->prepare($journalQuery);
$stmt->bind_param("ii", $user_id, $days);
$stmt->execute();
$journalCount = $stmt->get_result()->fetch_assoc()['total'];

// 6. Therapy sessions
$sessionsQuery = "SELECT COUNT(*) as total, 
                         SUM(CASE WHEN STATUS = 'COMPLETED' THEN 1 ELSE 0 END) as completed,
                         SUM(CASE WHEN STATUS = 'BOOKED' THEN 1 ELSE 0 END) as upcoming
                  FROM SESSIONS 
                  WHERE PATIENT_ID = ? AND CREATED_AT >= DATE_SUB(NOW(), INTERVAL ? DAY)";
$stmt = $conn->prepare($sessionsQuery);
$stmt->bind_param("ii", $user_id, $days);
$stmt->execute();
$sessionsData = $stmt->get_result()->fetch_assoc();

// 7. Recommendations received
$recQuery = "SELECT COUNT(*) as total FROM RECOMMENDATIONS WHERE USER_ID = ? AND CREATED_AT >= DATE_SUB(NOW(), INTERVAL ? DAY)";
$stmt = $conn->prepare($recQuery);
$stmt->bind_param("ii", $user_id, $days);
$stmt->execute();
$recommendationsCount = $stmt->get_result()->fetch_assoc()['total'];

// 8. Most common mood
$mostCommonMood = $moodStats[0]['MOOD'] ?? 'N/A';

// 9. Positive vs Negative moods
$positiveQuery = "SELECT COUNT(*) as positive FROM MOOD_LOGS 
                  WHERE USER_ID = ? AND MOOD IN ('HAPPY', 'EXCITED', 'CALM') 
                  AND CREATED_AT >= DATE_SUB(NOW(), INTERVAL ? DAY)";
$stmt = $conn->prepare($positiveQuery);
$stmt->bind_param("ii", $user_id, $days);
$stmt->execute();
$positiveCount = $stmt->get_result()->fetch_assoc()['positive'];

$negativeQuery = "SELECT COUNT(*) as negative FROM MOOD_LOGS 
                  WHERE USER_ID = ? AND MOOD IN ('SAD', 'ANXIOUS', 'STRESSED', 'ANGRY') 
                  AND CREATED_AT >= DATE_SUB(NOW(), INTERVAL ? DAY)";
$stmt = $conn->prepare($negativeQuery);
$stmt->bind_param("ii", $user_id, $days);
$stmt->execute();
$negativeCount = $stmt->get_result()->fetch_assoc()['negative'];

echo json_encode([
    'success' => true,
    'period' => $period,
    'days' => $days,
    'report' => [
        'summary' => [
            'total_mood_logs' => $totalLogs,
            'average_intensity' => $avgIntensity,
            'most_common_mood' => $mostCommonMood,
            'journal_entries' => $journalCount,
            'therapy_sessions' => $sessionsData['total'],
            'completed_sessions' => $sessionsData['completed'],
            'upcoming_sessions' => $sessionsData['upcoming'],
            'recommendations_received' => $recommendationsCount,
            'positive_moods' => $positiveCount,
            'negative_moods' => $negativeCount
        ],
        'mood_statistics' => $moodStats,
        'mood_trend' => $moodTrend
    ]
]);

$conn->close();
?>