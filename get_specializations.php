<?php
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

if (!$user_id) {
    die(json_encode(['success' => false, 'message' => 'User ID required']));
}

// recent mood logs for last 7 days
$moodQuery = "SELECT MOOD, INTENSITY, COUNT(*) as count 
              FROM MOOD_LOGS 
              WHERE USER_ID = ? AND CREATED_AT >= DATE_SUB(NOW(), INTERVAL 7 DAY)
              GROUP BY MOOD, INTENSITY 
              ORDER BY count DESC";

$stmt = $conn->prepare($moodQuery);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$moods = [];
while ($row = $result->fetch_assoc()) {
    $moods[] = $row;
}

if (empty($moods)) {
    die(json_encode(['success' => true, 'message' => 'No mood data to analyze', 'recommendations_created' => 0]));
}

$recommendedCategories = [];
$reasons = [];

foreach ($moods as $mood) {
    $moodType = $mood['MOOD'];
    $intensity = $mood['INTENSITY'];
    
    // Mood → Category Mappings
    if ($moodType === 'SAD' && $intensity >= 7) {
        $recommendedCategories[] = 'Depression';
        $reasons['Depression'] = "You've been experiencing sadness with high intensity recently.";
    } elseif ($moodType === 'SAD' || $moodType === 'ANXIOUS') {
        $recommendedCategories[] = 'Anxiety Management';
        $reasons['Anxiety Management'] = "Your recent mood patterns suggest you might benefit from anxiety management techniques.";
    }
    
    if ($moodType === 'STRESSED') {
        $recommendedCategories[] = 'Stress Management';
        $reasons['Stress Management'] = "You've been feeling stressed lately. These resources can help you manage stress better.";
    }
    
    if ($moodType === 'CALM') {
        $recommendedCategories[] = 'Mindfulness';
        $reasons['Mindfulness'] = "Building on your calm moments, mindfulness can help you maintain inner peace.";
    }
}

//CBT Exercises as general recommendation
$recommendedCategories[] = 'CBT Exercises';
$reasons['CBT Exercises'] = "These cognitive behavioral techniques can help improve your overall mental wellbeing.";

$recommendedCategories = array_unique($recommendedCategories);

// Delete old recommendations 
$deleteQuery = "DELETE FROM RECOMMENDATIONS WHERE USER_ID = ? AND IS_READ = 'N'";
$deleteStmt = $conn->prepare($deleteQuery);
$deleteStmt->bind_param("i", $user_id);
$deleteStmt->execute();

// Get resources from recommended categories
$placeholders = str_repeat('?,', count($recommendedCategories) - 1) . '?';
$resourceQuery = "SELECT r.RESOURCE_ID, r.TITLE, r.DESCRIPTION, r.RESOURCE_TYPE, r.CATEGORY, 
                         r.EXTERNAL_LINK, r.FILE_URL,
                         CONCAT(u.FIRST_NAME, ' ', u.LAST_NAME) as therapist_name
                  FROM RESOURCES r
                  JOIN USERS u ON r.THERAPIST_ID = u.USER_ID
                  WHERE r.CATEGORY IN ($placeholders) 
                  AND r.IS_APPROVED = 'Y'
                  ORDER BY RAND()
                  LIMIT 10";

$resourceStmt = $conn->prepare($resourceQuery);
$types = str_repeat('s', count($recommendedCategories));
$resourceStmt->bind_param($types, ...$recommendedCategories);
$resourceStmt->execute();
$resourceResult = $resourceStmt->get_result();

$recommendationsCreated = 0;

while ($resource = $resourceResult->fetch_assoc()) {
    
    $idQuery = "SELECT COALESCE(MAX(RECOMMENDATION_ID), 0) + 1 as next_id FROM RECOMMENDATIONS";
    $idResult = $conn->query($idQuery);
    $rec_id = $idResult->fetch_assoc()['next_id'];
    
    $category = $resource['CATEGORY'];
    $reason = $reasons[$category] ?? "Based on your recent mood patterns, this resource might be helpful.";
    
    $insertQuery = "INSERT INTO RECOMMENDATIONS 
                    (RECOMMENDATION_ID, USER_ID, TYPE, RELATED_ID, REASON, IS_READ, CREATED_AT)
                    VALUES (?, ?, 'RESOURCE', ?, ?, 'N', NOW())";
    
    $insertStmt = $conn->prepare($insertQuery);
    $type = 'RESOURCE';
    $insertStmt->bind_param("iiss", $rec_id, $user_id, $resource['RESOURCE_ID'], $reason);
    
    if ($insertStmt->execute()) {
        $recommendationsCreated++;
    }
}

echo json_encode([
    'success' => true,
    'message' => 'Recommendations generated',
    'recommendations_created' => $recommendationsCreated,
    'categories' => $recommendedCategories
]);

$conn->close();
?>