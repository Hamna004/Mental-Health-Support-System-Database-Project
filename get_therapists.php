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

$query = "SELECT 
            rec.RECOMMENDATION_ID,
            rec.REASON,
            rec.IS_READ,
            rec.CREATED_AT,
            r.RESOURCE_ID,
            r.TITLE,
            r.DESCRIPTION,
            r.RESOURCE_TYPE,
            r.CATEGORY,
            r.EXTERNAL_LINK,
            r.FILE_URL,
            CONCAT(u.FIRST_NAME, ' ', u.LAST_NAME) as therapist_name
          FROM RECOMMENDATIONS rec
          JOIN RESOURCES r ON rec.RELATED_ID = r.RESOURCE_ID
          JOIN USERS u ON r.THERAPIST_ID = u.USER_ID
          WHERE rec.USER_ID = ? AND rec.TYPE = 'RESOURCE'
          ORDER BY rec.IS_READ ASC, rec.CREATED_AT DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$recommendations = [];
$unread_count = 0;

while ($row = $result->fetch_assoc()) {
    $recommendations[] = [
        'recommendation_id' => $row['RECOMMENDATION_ID'],
        'resource_id' => $row['RESOURCE_ID'],
        'title' => $row['TITLE'],
        'description' => $row['DESCRIPTION'],
        'type' => $row['RESOURCE_TYPE'],
        'category' => $row['CATEGORY'],
        'external_link' => $row['EXTERNAL_LINK'],
        'file_url' => $row['FILE_URL'],
        'therapist_name' => $row['therapist_name'],
        'reason' => $row['REASON'],
        'is_read' => $row['IS_READ'],
        'created_at' => $row['CREATED_AT']
    ];
    
    if ($row['IS_READ'] === 'N') {
        $unread_count++;
    }
}

echo json_encode([
    'success' => true,
    'recommendations' => $recommendations,
    'unread_count' => $unread_count,
    'total_count' => count($recommendations)
]);

$conn->close();
?>