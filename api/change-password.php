<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../database/connection.php';
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in.']);
    exit();
}
$user_id = $_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);
if (!isset($data['current_password'], $data['new_password'], $data['confirm_new_password'])) {
    echo json_encode(['success' => false, 'message' => 'All fields are required.']);
    exit();
}
if ($data['new_password'] !== $data['confirm_new_password']) {
    echo json_encode(['success' => false, 'message' => 'New passwords do not match.']);
    exit();
}
// Fetch current password hash
$stmt = $conn->prepare('SELECT password FROM accounts WHERE id=?');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$stmt->bind_result($hash);
if ($stmt->fetch()) {
    if (!password_verify($data['current_password'], $hash)) {
        echo json_encode(['success' => false, 'message' => 'Current password is incorrect.']);
        $stmt->close();
        exit();
    }
    $stmt->close();
    // Update to new password
    $new_hash = password_hash($data['new_password'], PASSWORD_DEFAULT);
    $update = $conn->prepare('UPDATE accounts SET password=? WHERE id=?');
    $update->bind_param('si', $new_hash, $user_id);
    if ($update->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update password.']);
    }
    $update->close();
} else {
    echo json_encode(['success' => false, 'message' => 'User not found.']);
} 