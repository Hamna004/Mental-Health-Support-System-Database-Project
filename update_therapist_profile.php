<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE');
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
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

//fetch all resources 
if ($method === 'GET') {
    try {
        if (isset($_GET['therapist_id'])) {
            $therapist_id = intval($_GET['therapist_id']);
            
            $query = "SELECT 
                        r.RESOURCE_ID, 
                        r.THERAPIST_ID, 
                        CONCAT(u.FIRST_NAME, ' ', u.LAST_NAME) AS therapist_name,
                        r.TITLE, 
                        r.DESCRIPTION, 
                        r.RESOURCE_TYPE, 
                        r.CATEGORY, 
                        r.FILE_URL,
                        r.EXTERNAL_LINK,
                        r.DATE_UPLOADED,
                        r.IS_APPROVED
                      FROM RESOURCES r
                      LEFT JOIN USERS u ON r.THERAPIST_ID = u.USER_ID
                      WHERE r.THERAPIST_ID = ?
                      ORDER BY r.DATE_UPLOADED DESC";
            
            $stmt = $conn->prepare($query);
            $stmt->execute([$therapist_id]);
            $resources = $stmt->fetchAll();
            
            echo json_encode(['success' => true, 'resources' => $resources]);
            exit;
        }
        
        $query = "SELECT 
                    r.RESOURCE_ID, 
                    r.THERAPIST_ID, 
                    CONCAT(u.FIRST_NAME, ' ', u.LAST_NAME) AS therapist_name,
                    r.TITLE, 
                    r.DESCRIPTION, 
                    r.RESOURCE_TYPE, 
                    r.CATEGORY, 
                    r.FILE_URL,
                    r.EXTERNAL_LINK,
                    r.DATE_UPLOADED,
                    r.IS_APPROVED
                  FROM RESOURCES r
                  LEFT JOIN USERS u ON r.THERAPIST_ID = u.USER_ID
                  WHERE r.IS_APPROVED = 'Y'
                  ORDER BY r.DATE_UPLOADED DESC";
        
        $stmt = $conn->query($query);
        $resources = $stmt->fetchAll();
        
        echo json_encode(['success' => true, 'resources' => $resources]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}
//upload new resource 
if ($method === 'POST') {
    try {
        $therapist_id = isset($_POST['therapist_id']) ? intval($_POST['therapist_id']) : 0;
        $title = isset($_POST['title']) ? trim($_POST['title']) : '';
        $description = isset($_POST['description']) ? trim($_POST['description']) : '';
        $resource_type = isset($_POST['resource_type']) ? strtoupper(trim($_POST['resource_type'])) : 'ARTICLE';
        $category = isset($_POST['category']) ? trim($_POST['category']) : '';
        $external_link = isset($_POST['external_link']) ? trim($_POST['external_link']) : null;

        if (!$therapist_id || !$title || !$category) {
            echo json_encode(['success' => false, 'message' => 'Therapist ID, title, and category are required']);
            exit;
        }

        // Validate resource 
        $valid_types = ['ARTICLE', 'VIDEO', 'GUIDE', 'WORKSHEET'];
        if (!in_array($resource_type, $valid_types)) {
            echo json_encode(['success' => false, 'message' => 'Invalid resource type']);
            exit;
        }

        $file_url = null;

        // file upload
        if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = '../uploads/resources/';
            
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $fileName = $_FILES['file']['name'];
            $fileTmpPath = $_FILES['file']['tmp_name'];
            $fileSize = $_FILES['file']['size'];

            $allowedExtensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'jpg', 'png', 'gif'];
            $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

            if (!in_array($fileExtension, $allowedExtensions)) {
                echo json_encode(['success' => false, 'message' => 'File type not allowed']);
                exit;
            }

            if ($fileSize > 50 * 1024 * 1024) {
                echo json_encode(['success' => false, 'message' => 'File exceeds 50MB limit']);
                exit;
            }

            $newFileName = 'resource_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $fileExtension;
            $uploadPath = $uploadDir . $newFileName;

            if (move_uploaded_file($fileTmpPath, $uploadPath)) {
                $file_url = str_replace('../', '', $uploadPath);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to upload file']);
                exit;
            }
        }

//get next resource id 
        $idQuery = "SELECT COALESCE(MAX(RESOURCE_ID), 0) + 1 as next_id FROM RESOURCES";
        $idResult = $conn->query($idQuery);
        $resource_id = $idResult->fetch()['next_id'];

        // Insert a  resource
        $query = "INSERT INTO RESOURCES 
                  (RESOURCE_ID, THERAPIST_ID, TITLE, DESCRIPTION, RESOURCE_TYPE, CATEGORY, FILE_URL, EXTERNAL_LINK, DATE_UPLOADED, IS_APPROVED)
                  VALUES 
                  (?, ?, ?, ?, ?, ?, ?, ?, NOW(), 'Y')";

        $stmt = $conn->prepare($query);
        $stmt->execute([
            $resource_id,
            $therapist_id,
            $title,
            $description,
            $resource_type,
            $category,
            $file_url,
            $external_link
        ]);

        echo json_encode([
            'success' => true,
            'message' => 'Resource uploaded successfully and pending approval',
            'resource_id' => $resource_id,
            'file_url' => $file_url
        ]);

    } catch (Exception $e) {
        if (isset($file_url) && $file_url && file_exists('../' . $file_url)) {
            unlink('../' . $file_url);
        }
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
    exit;
}

// remove resource 
if ($method === 'DELETE') {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['resource_id'])) {
            echo json_encode(['success' => false, 'message' => 'Resource ID required']);
            exit;
        }

        $resource_id = intval($data['resource_id']);

        $getFileQuery = "SELECT FILE_URL FROM RESOURCES WHERE RESOURCE_ID = ?";
        $getStmt = $conn->prepare($getFileQuery);
        $getStmt->execute([$resource_id]);
        $fileRow = $getStmt->fetch();

        if ($fileRow && $fileRow['FILE_URL']) {
            $filePath = '../' . $fileRow['FILE_URL'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }

        $deleteQuery = "DELETE FROM RESOURCES WHERE RESOURCE_ID = ?";
        $delStmt = $conn->prepare($deleteQuery);
        $delStmt->execute([$resource_id]);

        echo json_encode(['success' => true, 'message' => 'Resource deleted successfully']);

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid request method']);
?>