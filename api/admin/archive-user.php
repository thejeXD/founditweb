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
$update = $conn->prepare('UPDATE accounts SET status=2 WHERE id=?');
$update->bind_param('i', $id);
if ($update->execute()) {
    // Log to activity_log_admin
    $action = 'archive_user';
    $details = "Archived user (ID: $id)";
    $log = $conn->prepare('INSERT INTO activity_log_admin (admin_id, action, target_type, target_id, details) VALUES (?, ?, ?, ?, ?)');
    $target_type = 'user';
    $log->bind_param('issis', $user_id, $action, $target_type, $id, $details);
    $log->execute();
    $log->close();
    // Send inbox message
    $subject = 'Account Archived';
    $message = 'Your account has been archived by an administrator. If you believe this is a mistake, please contact support.';
    $inbox = $conn->prepare('INSERT INTO inbox (user_id, subject, message, is_read, created_at) VALUES (?, ?, ?, 0, NOW())');
    $inbox->bind_param('iss', $id, $subject, $message);
    $inbox->execute();
    $inbox->close();
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to archive user.']);
}
$update->close(); 