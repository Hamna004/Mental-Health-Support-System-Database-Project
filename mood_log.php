<?php
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Only POST requests allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['email']) || !isset($data['password']) || !isset($data['role'])) {
    echo json_encode(['success' => false, 'message' => 'Email, password, and role are required']);
    exit;
}

$email = trim($data['email']);
$password = $data['password'];
$role = strtoupper($data['role']);

if ($role === 'USER') {
    $role = 'PATIENT';
}

if (!in_array($role, ['PATIENT', 'THERAPIST', 'ADMIN'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid role']);
    exit;
}

$conn = getDBConnection();

$stmt = $conn->prepare("SELECT USER_ID, USERNAME, EMAIL, PASSWORD_HASH, FIRST_NAME, LAST_NAME, ROLE, IS_ACTIVE FROM USERS WHERE EMAIL = ? AND ROLE = ?");
$stmt->bind_param("ss", $email, $role);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid email, password, or role']);
    $stmt->close();
    $conn->close();
    exit;
}

$user = $result->fetch_assoc();
$stmt->close();

if ($user['IS_ACTIVE'] !== 'Y') {
    echo json_encode(['success' => false, 'message' => 'Account is inactive']);
    $conn->close();
    exit;
}

if (!password_verify($password, $user['PASSWORD_HASH'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid email, password, or role']);
    $conn->close();
    exit;
}

$specialty = null;
if ($user['ROLE'] === 'THERAPIST') {
    $stmt2 = $conn->prepare("SELECT SPECIALISATION FROM THERAPIST_PROFILES WHERE THERAPIST_ID = ?");
    $stmt2->bind_param("i", $user['USER_ID']);
    $stmt2->execute();
    $result2 = $stmt2->get_result();
    if ($result2->num_rows > 0) {
        $therapist = $result2->fetch_assoc();
        $specialty = $therapist['SPECIALISATION'];
    }
    $stmt2->close();
}

$frontend_role = ($user['ROLE'] === 'PATIENT') ? 'user' : strtolower($user['ROLE']);

$response = [
    'success' => true,
    'message' => 'Login successful',
    'user' => [
        'id' => $user['USER_ID'],
        'name' => $user['FIRST_NAME'] . ' ' . $user['LAST_NAME'],
        'email' => $user['EMAIL'],
        'role' => $frontend_role,
        'status' => 'active'
    ]
];

if ($specialty) {
    $response['user']['specialty'] = $specialty;
}

echo json_encode($response);

$conn->close();
?>