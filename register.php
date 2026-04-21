<?php
header('Content-Type: application/json');
error_reporting(0);

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

$method = $_SERVER['REQUEST_METHOD'];

//mood logs
if ($method === 'GET') {
    $user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
    
    if (!$user_id) {
        die(json_encode(['success' => false, 'message' => 'User ID required']));
    }
    
    $stmt = $conn->prepare("SELECT MOOD_LOG_ID, MOOD, INTENSITY, LOG_DATE, NOTES, CREATED_AT 
                            FROM MOOD_LOGS 
                            WHERE USER_ID = ? 
                            ORDER BY CREATED_AT DESC");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $mood_logs = [];
    while ($row = $result->fetch_assoc()) {
        $mood_logs[] = [
            'id' => $row['MOOD_LOG_ID'],
            'mood' => $row['MOOD'],
            'intensity' => $row['INTENSITY'],
            'log_date' => $row['LOG_DATE'],
            'notes' => $row['NOTES'],
            'created_at' => $row['CREATED_AT']
        ];
    }
    
    die(json_encode([
        'success' => true,
        'mood_logs' => $mood_logs
    ]));
}

//new mood log
if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['user_id']) || !isset($data['mood']) || !isset($data['intensity'])) {
        die(json_encode(['success' => false, 'message' => 'User ID, mood, and intensity required']));
    }
    
    $user_id = intval($data['user_id']);
    $mood = strtoupper(trim($data['mood']));
    $intensity = intval($data['intensity']);
    $notes = isset($data['notes']) ? trim($data['notes']) : null;
    
    $valid_moods = ['HAPPY', 'SAD', 'ANXIOUS', 'STRESSED', 'CALM', 'ANGRY', 'TIRED', 'EXCITED'];
    if (!in_array($mood, $valid_moods)) {
        die(json_encode(['success' => false, 'message' => 'Invalid mood']));
    }
    
    if ($intensity < 1 || $intensity > 10) {
        die(json_encode(['success' => false, 'message' => 'Intensity must be 1-10']));
    }
    
    // Insert mood log
    $stmt = $conn->prepare("INSERT INTO MOOD_LOGS (USER_ID, MOOD, INTENSITY, LOG_DATE, NOTES, CREATED_AT) 
                            VALUES (?, ?, ?, CURDATE(), ?, NOW())");
    $stmt->bind_param("isis", $user_id, $mood, $intensity, $notes);
    
    if ($stmt->execute()) {
        $log_id = $conn->insert_id;
        
        //  TRIGGER RECOMMENDATION GENERATION 
        // Check if user should get new recommendations
        $moodCount = "SELECT COUNT(*) as count FROM MOOD_LOGS WHERE USER_ID = ?";
        $countStmt = $conn->prepare($moodCount);
        $countStmt->bind_param("i", $user_id);
        $countStmt->execute();
        $countResult = $countStmt->get_result();
        $totalMoods = $countResult->fetch_assoc()['count'];
        
        // Generate recommendations after every 3 mood logs
        if ($totalMoods % 3 === 0) {
            // Call check_recommendations internally
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "http://localhost/DBProject/api/check_recommendations.php?user_id=" . $user_id);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_exec($ch);
            curl_close($ch);
        }
        
        die(json_encode([
            'success' => true,
            'message' => 'Mood logged successfully',
            'log_id' => $log_id,
            'check_recommendations' => true
        ]));
    } else {
        die(json_encode(['success' => false, 'message' => 'Failed to log mood']));
    }
}

// Delete mood log
if ($method === 'DELETE') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['log_id']) || !isset($data['user_id'])) {
        die(json_encode(['success' => false, 'message' => 'Log ID and User ID required']));
    }
    
    $log_id = intval($data['log_id']);
    $user_id = intval($data['user_id']);
    
    $stmt = $conn->prepare("DELETE FROM MOOD_LOGS WHERE MOOD_LOG_ID = ? AND USER_ID = ?");
    $stmt->bind_param("ii", $log_id, $user_id);
    
    if ($stmt->execute() && $stmt->affected_rows > 0) {
        die(json_encode(['success' => true, 'message' => 'Mood log deleted']));
    } else {
        die(json_encode(['success' => false, 'message' => 'Failed to delete']));
    }
}

die(json_encode(['success' => false, 'message' => 'Invalid request method']));
?>