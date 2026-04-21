<?php
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['success' => false, 'message' => 'Only GET requests allowed']);
    exit;
}

$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
$period = isset($_GET['period']) ? $_GET['period'] : 'week'; // week or month

if (!$user_id) {
    echo json_encode(['success' => false, 'message' => 'User ID required']);
    exit;
}

$conn = getDBConnection();

$stmt = $conn->prepare("SELECT USER_ID FROM USERS WHERE USER_ID = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'User not found']);
    $stmt->close();
    $conn->close();
    exit;
}
$stmt->close();
if ($period === 'week') {
    $date_condition = "LOG_DATE >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
    $period_label = "This Week";
} else {
    $date_condition = "LOG_DATE >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
    $period_label = "This Month";
}

$query = "SELECT MOOD, INTENSITY, LOG_DATE, NOTES, CREATED_AT 
          FROM MOOD_LOGS 
          WHERE USER_ID = ? AND $date_condition
          ORDER BY LOG_DATE DESC, CREATED_AT DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$mood_logs = [];
$mood_counts = [];
$intensity_sum = 0;
$intensity_count = 0;
$daily_data = []; 

while ($row = $result->fetch_assoc()) {
    $mood_logs[] = [
        'mood' => $row['MOOD'],
        'intensity' => $row['INTENSITY'],
        'log_date' => $row['LOG_DATE'],
        'notes' => $row['NOTES'],
        'created_at' => $row['CREATED_AT']
    ];
    
    // Count moods
    if (!isset($mood_counts[$row['MOOD']])) {
        $mood_counts[$row['MOOD']] = 0;
    }
    $mood_counts[$row['MOOD']]++;
    
    $intensity_sum += $row['INTENSITY'];
    $intensity_count++;
    
    $date = $row['LOG_DATE'];
    if (!isset($daily_data[$date])) {
        $daily_data[$date] = [
            'date' => $date,
            'moods' => [],
            'avg_intensity' => 0,
            'count' => 0,
            'total_intensity' => 0
        ];
    }
    $daily_data[$date]['moods'][] = $row['MOOD'];
    $daily_data[$date]['total_intensity'] += $row['INTENSITY'];
    $daily_data[$date]['count']++;
}

$stmt->close();

$total_logs = count($mood_logs);
$avg_intensity = $intensity_count > 0 ? round($intensity_sum / $intensity_count, 1) : 0;

$most_common_mood = null;
$max_count = 0;
foreach ($mood_counts as $mood => $count) {
    if ($count > $max_count) {
        $max_count = $count;
        $most_common_mood = $mood;
    }
}


$chart_data = [];
foreach ($daily_data as $date => $data) {
    $daily_data[$date]['avg_intensity'] = round($data['total_intensity'] / $data['count'], 1);
    $chart_data[] = [
        'date' => $date,
        'avg_intensity' => $daily_data[$date]['avg_intensity'],
        'log_count' => $data['count'],
        'moods' => array_unique($data['moods'])
    ];
}

usort($chart_data, function($a, $b) {
    return strtotime($a['date']) - strtotime($b['date']);
});

$mood_distribution = [];
foreach ($mood_counts as $mood => $count) {
    $mood_distribution[] = [
        'mood' => $mood,
        'count' => $count,
        'percentage' => round(($count / $total_logs) * 100, 1)
    ];
}

usort($mood_distribution, function($a, $b) {
    return $b['count'] - $a['count'];
});

$positive_moods = ['HAPPY', 'CALM', 'EXCITED'];
$negative_moods = ['SAD', 'ANXIOUS', 'STRESSED', 'ANGRY', 'TIRED'];

$positive_count = 0;
$negative_count = 0;

foreach ($mood_counts as $mood => $count) {
    if (in_array($mood, $positive_moods)) {
        $positive_count += $count;
    } else if (in_array($mood, $negative_moods)) {
        $negative_count += $count;
    }
}

$positive_percentage = $total_logs > 0 ? round(($positive_count / $total_logs) * 100, 1) : 0;
$negative_percentage = $total_logs > 0 ? round(($negative_count / $total_logs) * 100, 1) : 0;

echo json_encode([
    'success' => true,
    'period' => $period,
    'period_label' => $period_label,
    'summary' => [
        'total_logs' => $total_logs,
        'avg_intensity' => $avg_intensity,
        'most_common_mood' => $most_common_mood,
        'most_common_count' => $max_count,
        'positive_percentage' => $positive_percentage,
        'negative_percentage' => $negative_percentage
    ],
    'mood_distribution' => $mood_distribution,
    'chart_data' => $chart_data,
    'recent_logs' => array_slice($mood_logs, 0, 10) 
]);

$conn->close();
?>