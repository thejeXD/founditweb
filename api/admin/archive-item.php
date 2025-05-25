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
$status = isset($data['status']) ? intval($data['status']) : null;
if (!$id || ($status !== 0 && $status !== 1)) {
    echo json_encode(['success' => false, 'message' => 'Missing item id or status.']);
    exit();
}
$update = $conn->prepare('UPDATE items SET status=? WHERE id=?');
$update->bind_param('ii', $status, $id);
if ($update->execute()) {
    // Log to activity_log_admin
    $action = $status == 1 ? 'unarchive_item' : 'archive_item';
    $details = ($status == 1 ? 'Unarchived' : 'Archived') . " item (ID: $id)";
    $log = $conn->prepare('INSERT INTO activity_log_admin (admin_id, action, target_type, target_id, details) VALUES (?, ?, ?, ?, ?)');
    $target_type = 'item';
    $log->bind_param('issis', $user_id, $action, $target_type, $id, $details);
    $log->execute();
    $log->close();
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update item status.']);
}
$update->close(); 