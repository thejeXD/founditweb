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
$id = isset($data['id']) ? intval($data['id']) : 0;
if (!$id) {
    echo json_encode(['success' => false, 'message' => 'Missing item id.']);
    exit();
}
// Check ownership
$stmt = $conn->prepare('SELECT id FROM items WHERE id = ? AND user_id = ?');
$stmt->bind_param('ii', $id, $user_id);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit();
}
$stmt->close();
// Archive item
$update = $conn->prepare('UPDATE items SET status=0 WHERE id=?');
$update->bind_param('i', $id);
if ($update->execute()) {
    // Log to activity_log (match submit-item.php structure)
    $action = 'archive_item';
    $details = "Archived item (ID: $id)";
    $log = $conn->prepare('INSERT INTO activity_log (user_id, action, item_id, details, created_at) VALUES (?, ?, ?, ?, NOW())');
    $log->bind_param('isis', $user_id, $action, $id, $details);
    $log->execute();
    $log->close();
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to archive item.']);
}
$update->close(); 