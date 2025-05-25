<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../../database/connection.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in.']);
    exit();
}
$user_id = $_SESSION['user_id'];
$role = 1;
$stmt = $conn->prepare('SELECT role FROM accounts WHERE id = ?');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$stmt->bind_result($role);
$stmt->fetch();
$stmt->close();
if ($role < 2) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit();
}
$data = json_decode(file_get_contents('php://input'), true);
$id = intval($data['id'] ?? 0);
if (!$id) {
    echo json_encode(['success' => false, 'message' => 'Missing user id.']);
    exit();
}
$first_name = trim($data['first_name'] ?? '');
$last_name = trim($data['last_name'] ?? '');
$email = trim($data['email'] ?? '');
$student_number = trim($data['student_number'] ?? '');
$role_val = intval($data['role'] ?? 1);
$status = intval($data['status'] ?? 1);
$password = $data['password'] ?? '';

if (!$first_name || !$last_name || !$email) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields.']);
    exit();
}

// Update user
if ($password) {
    $hashed = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare('UPDATE accounts SET first_name=?, last_name=?, email=?, student_number=?, role=?, status=?, password=? WHERE id=?');
    $stmt->bind_param('ssssiiii', $first_name, $last_name, $email, $student_number, $role_val, $status, $hashed, $id);
} else {
    $stmt = $conn->prepare('UPDATE accounts SET first_name=?, last_name=?, email=?, student_number=?, role=?, status=? WHERE id=?');
    $stmt->bind_param('ssssiii', $first_name, $last_name, $email, $student_number, $role_val, $status, $id);
}
if ($stmt->execute()) {
    // Log to activity_log_admin
    $action = 'edit_user';
    $details = "Edited user (ID: $id, Name: $first_name $last_name)";
    $log = $conn->prepare('INSERT INTO activity_log_admin (admin_id, action, target_type, target_id, details) VALUES (?, ?, ?, ?, ?)');
    $target_type = 'user';
    $log->bind_param('issis', $user_id, $action, $target_type, $id, $details);
    $log->execute();
    $log->close();
    // Send inbox notification
    $subject = 'Account Updated';
    $message = 'Your account details have been updated by an administrator.';
    $inbox = $conn->prepare('INSERT INTO inbox (user_id, subject, message, is_read, created_at) VALUES (?, ?, ?, 0, NOW())');
    $inbox->bind_param('iss', $id, $subject, $message);
    $inbox->execute();
    $inbox->close();
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update user.']);
}
$stmt->close(); 