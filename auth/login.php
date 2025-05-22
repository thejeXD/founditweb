<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

require_once '../database/connection.php';

// Get JSON data from request
$json = file_get_contents('php://input');
if (!$json) {
    echo json_encode(['success' => false, 'message' => 'No data received']);
    exit();
}

$data = json_decode($json, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(['success' => false, 'message' => 'Invalid JSON data']);
    exit();
}

$email = isset($data['email']) ? trim($data['email']) : '';
$password = isset($data['password']) ? $data['password'] : '';

if (empty($email) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Email and password are required.']);
    exit();
}

// Sanitize email
$email = filter_var($email, FILTER_SANITIZE_EMAIL);

session_start();

try {
    $stmt = $conn->prepare("SELECT id, password, status, first_name, last_name FROM accounts WHERE email = ? LIMIT 1");
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid email or password.']);
        exit();
    }
    $user = $result->fetch_assoc();
    if ($user['status'] != 1) {
        echo json_encode(['success' => false, 'message' => 'Account is not active.']);
        exit();
    }
    if (!password_verify($password, $user['password'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid email or password.']);
        exit();
    }
    // Success: set session and update last_login
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['first_name'] = $user['first_name'];
    $_SESSION['last_name'] = $user['last_name'];
    $_SESSION['email'] = $email;
    $stmt = $conn->prepare("UPDATE accounts SET last_login = NOW() WHERE id = ?");
    $stmt->bind_param("i", $user['id']);
    $stmt->execute();
    echo json_encode(['success' => true, 'message' => 'Login successful.']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Login failed. Please try again.']);
} 