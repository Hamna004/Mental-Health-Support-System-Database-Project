<?php
header('Content-Type: application/json');
error_reporting(0);

// Database connection
$host = 'localhost';
$dbname = 'dbproject'; // YOUR DATABASE NAME
$username = 'root';
$password = '';

try {
    $conn = new mysqli($host, $username, $password, $dbname);
    if ($conn->connect_error) {
        die(json_encode(['success' => false, 'message' => 'Database connection failed']));
    }
} catch (Exception $e) {
    die(json_encode(['success' => false, 'message' => 'Database error']));
}

// Get user_id from request
$user_id = null;

if (isset($_GET['user_id'])) {
    $user_id = intval($_GET['user_id']);
} else {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    if (isset($data['user_id'])) {
        $user_id = intval($data['user_id']);
    }
}

if (!$user_id) {
    die(json_encode(['success' => false, 'message' => 'User ID required']));
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            handleGet($conn, $user_id);
            break;
        case 'POST':
            handlePost($conn, $user_id);
            break;
        case 'PUT':
            handlePut($conn, $user_id);
            break;
        case 'DELETE':
            handleDelete($conn, $user_id);
            break;
        default:
            die(json_encode(['success' => false, 'message' => 'Invalid method']));
    }
} catch (Exception $e) {
    die(json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]));
}

function handleGet($conn, $user_id) {
    if (isset($_GET['id'])) {
        // Get single journal
        $journal_id = intval($_GET['id']);
        $stmt = $conn->prepare("SELECT JOURNAL_ID as id, TITLE as title, CONTENT as content, ENTRY_DATE, CREATED_AT as created_at FROM JOURNALS WHERE JOURNAL_ID = ? AND USER_ID = ?");
        $stmt->bind_param("ii", $journal_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            die(json_encode(['success' => true, 'journal' => $row]));
        } else {
            die(json_encode(['success' => false, 'message' => 'Journal not found']));
        }
    } else {
        // Get all journals for user
        $stmt = $conn->prepare("SELECT JOURNAL_ID as id, TITLE as title, CONTENT as content, LEFT(CONTENT, 150) as excerpt, ENTRY_DATE, CREATED_AT as created_at FROM JOURNALS WHERE USER_ID = ? ORDER BY CREATED_AT DESC");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $journals = [];
        while ($row = $result->fetch_assoc()) {
            $journals[] = $row;
        }
        
        die(json_encode([
            'success' => true,
            'journals' => $journals,
            'total' => count($journals)
        ]));
    }
}

function handlePost($conn, $user_id) {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    if (!isset($data['title']) || !isset($data['content'])) {
        die(json_encode(['success' => false, 'message' => 'Title and content required']));
    }
    
    $title = trim($data['title']);
    $content = trim($data['content']);
    
    if (empty($title) || empty($content)) {
        die(json_encode(['success' => false, 'message' => 'Title and content cannot be empty']));
    }
    
    // Get next journal ID
    $idQuery = "SELECT COALESCE(MAX(JOURNAL_ID), 0) + 1 as next_id FROM JOURNALS";
    $idResult = $conn->query($idQuery);
    $journal_id = $idResult->fetch_assoc()['next_id'];
    
    $stmt = $conn->prepare("INSERT INTO JOURNALS (JOURNAL_ID, USER_ID, TITLE, CONTENT, ENTRY_DATE, IS_PRIVATE, CREATED_AT) VALUES (?, ?, ?, ?, CURDATE(), 'Y', NOW())");
    $stmt->bind_param("iiss", $journal_id, $user_id, $title, $content);
    
    if ($stmt->execute()) {
        die(json_encode([
            'success' => true,
            'message' => 'Journal saved successfully! ✅',
            'id' => $journal_id
        ]));
    } else {
        die(json_encode(['success' => false, 'message' => 'Failed to save journal']));
    }
}

function handlePut($conn, $user_id) {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    if (!isset($data['id'])) {
        die(json_encode(['success' => false, 'message' => 'Journal ID required']));
    }
    
    $journal_id = intval($data['id']);
    
    // Check ownership
    $check = $conn->prepare("SELECT JOURNAL_ID FROM JOURNALS WHERE JOURNAL_ID = ? AND USER_ID = ?");
    $check->bind_param("ii", $journal_id, $user_id);
    $check->execute();
    
    if ($check->get_result()->num_rows === 0) {
        die(json_encode(['success' => false, 'message' => 'Unauthorized or journal not found']));
    }
    
    $title = trim($data['title']);
    $content = trim($data['content']);
    
    $stmt = $conn->prepare("UPDATE JOURNALS SET TITLE = ?, CONTENT = ? WHERE JOURNAL_ID = ? AND USER_ID = ?");
    $stmt->bind_param("ssii", $title, $content, $journal_id, $user_id);
    
    if ($stmt->execute()) {
        die(json_encode(['success' => true, 'message' => 'Journal updated successfully! ✅']));
    } else {
        die(json_encode(['success' => false, 'message' => 'Failed to update journal']));
    }
}

function handleDelete($conn, $user_id) {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    if (!isset($data['id'])) {
        die(json_encode(['success' => false, 'message' => 'Journal ID required']));
    }
    
    $journal_id = intval($data['id']);
    
    $stmt = $conn->prepare("DELETE FROM JOURNALS WHERE JOURNAL_ID = ? AND USER_ID = ?");
    $stmt->bind_param("ii", $journal_id, $user_id);
    
    if ($stmt->execute() && $stmt->affected_rows > 0) {
        die(json_encode(['success' => true, 'message' => 'Journal deleted successfully! 🗑️']));
    } else {
        die(json_encode(['success' => false, 'message' => 'Journal not found']));
    }
}

$conn->close();
?>