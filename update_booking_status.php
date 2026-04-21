<?php
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Only POST requests allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

$required_fields = ['name', 'email', 'password', 'role'];
foreach ($required_fields as $field) {
    if (!isset($data[$field]) || empty($data[$field])) {
        echo json_encode(['success' => false, 'message' => "Field '$field' is required"]);
        exit;
    }
}

$name_parts = explode(' ', trim($data['name']), 2);
$first_name = $name_parts[0];
$last_name = isset($name_parts[1]) ? $name_parts[1] : '';

$email = trim($data['email']);
$password = $data['password'];
$role = strtoupper($data['role']); 
$username = explode('@', $email)[0]; 

$valid_roles = ['USER', 'THERAPIST', 'ADMIN'];
if ($role === 'USER') {
    $role = 'PATIENT'; 
}

if (!in_array($role, ['PATIENT', 'THERAPIST', 'ADMIN'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid role']);
    exit;
}

$password_hash = password_hash($password, PASSWORD_DEFAULT);

$conn = getDBConnection();

$stmt = $conn->prepare("SELECT USER_ID FROM USERS WHERE EMAIL = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'Email already registered']);
    $stmt->close();
    $conn->close();
    exit;
}
$stmt->close();

$stmt = $conn->prepare("INSERT INTO USERS (USERNAME, EMAIL, PASSWORD_HASH, FIRST_NAME, LAST_NAME, ROLE, DATE_CREATED, IS_ACTIVE) VALUES (?, ?, ?, ?, ?, ?, NOW(), 'Y')");
$stmt->bind_param("ssssss", $username, $email, $password_hash, $first_name, $last_name, $role);

if ($stmt->execute()) {
    $user_id = $conn->insert_id;
    
    if ($role === 'THERAPIST') {
        $specialisation = isset($data['specialty']) ? $data['specialty'] : 'General';
        $stmt2 = $conn->prepare("INSERT INTO THERAPIST_PROFILES (THERAPIST_ID, SPECIALISATION, IS_ACCEPTING_NEW_CLIENTS) VALUES (?, ?, 'Y')");
        $stmt2->bind_param("is", $user_id, $specialisation);
        $stmt2->execute();
        $stmt2->close();
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Registration successful',
        'user' => [
            'id' => $user_id,
            'name' => $first_name . ' ' . $last_name,
            'email' => $email,
            'role' => strtolower($role),
            'status' => 'active'
        ]
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Registration failed: ' . $conn->error]);
}

$stmt->close();
$conn->close();
?>