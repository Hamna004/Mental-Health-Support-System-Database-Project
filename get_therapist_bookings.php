<?php
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
    
    $query = "SELECT specialization_id, specialization_name 
              FROM specializations 
              ORDER BY specialization_name ASC";
    
    $stmt = $conn->query($query);
    $specializations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'specializations' => $specializations,
        'count' => count($specializations)
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>