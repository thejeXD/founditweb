<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set proper headers for CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

// Log the request
error_log("Registration request received");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

require_once '../database/connection.php';

// Get JSON data from request
$json = file_get_contents('php://input');
$data = null;
if ($json) {
    $data = json_decode($json, true);
}
// If not JSON, try POST (form data)
if (!$data || !is_array($data)) {
    $data = $_POST;
}
if (!$data || !is_array($data) || count($data) === 0) {
    error_log("No registration data received");
    echo json_encode(['success' => false, 'message' => 'No data received']);
    exit();
}

// Log received data (excluding password)
$logData = $data;
if (isset($logData['password'])) {
    $logData['password'] = '********';
}
error_log("Decoded data: " . print_r($logData, true));

// Validate required fields
$required_fields = ['firstName', 'lastName', 'studentNumber', 'email', 'password'];
foreach ($required_fields as $field) {
    if (empty($data[$field])) {
        error_log("Missing required field: " . $field);
        echo json_encode(['success' => false, 'message' => 'All fields are required']);
        exit();
    }
}

// Sanitize input
$firstName = sanitize_input($data['firstName']);
$lastName = sanitize_input($data['lastName']);
$studentNumber = sanitize_input($data['studentNumber']);
$email = sanitize_input($data['email']);
$password = password_hash($data['password'], PASSWORD_DEFAULT);

try {
    error_log("Starting database operations");
    
    // Check if email already exists
    $stmt = $conn->prepare("SELECT id FROM accounts WHERE email = ?");
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        error_log("Email already registered: " . $email);
        echo json_encode(['success' => false, 'message' => 'Email already registered']);
        exit();
    }
    $stmt->close();

    // Check if student number already exists
    $stmt = $conn->prepare("SELECT id FROM accounts WHERE student_number = ?");
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param("s", $studentNumber);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        error_log("Student number already registered: " . $studentNumber);
        echo json_encode(['success' => false, 'message' => 'Student number already registered']);
        exit();
    }
    $stmt->close();

    // Insert new user
    $stmt = $conn->prepare("INSERT INTO accounts (first_name, last_name, student_number, email, password, status) VALUES (?, ?, ?, ?, ?, 1)");
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param("sssss", $firstName, $lastName, $studentNumber, $email, $password);
    
    if ($stmt->execute()) {
        $new_user_id = $stmt->insert_id;
        // Insert welcome inbox message
        $welcome_subject = 'Welcome to FoundIT!';
        $welcome_message = 'Hi <b>' . htmlspecialchars($firstName). 
                        '</b>, Welcome to FoundIT! You can now submit lost or found items, track your submissions, and receive updates. If you have any questions, feel free to contact us.'.
                        '<br><br>Best regards,<br>The FoundIT Team';
        // $welcome_message = 'Hi ' . $firstName . ',\n\nWelcome to FoundIT! You can now submit lost or found items, track your submissions, and receive updates. If you have any questions, feel free to contact us.\n\nBest regards,\nThe FoundIT Team';
        $stmt_inbox = $conn->prepare("INSERT INTO inbox (user_id, subject, message, is_read) VALUES (?, ?, ?, 0)");
        $stmt_inbox->bind_param("iss", $new_user_id, $welcome_subject, $welcome_message);
        $stmt_inbox->execute();
        error_log("User registered successfully: " . $email);
        echo json_encode(['success' => true, 'message' => 'Registration successful']);
    } else {
        throw new Exception("Error inserting user: " . $stmt->error);
    }
    
    $stmt->close();

} catch (Exception $e) {
    error_log("Registration Error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    echo json_encode(['success' => false, 'message' => 'Registration failed. Please try again.']);
}
?> 