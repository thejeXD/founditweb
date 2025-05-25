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
$name = trim($data['name'] ?? '');
$description = trim($data['description'] ?? '');
$location = trim($data['location'] ?? '');
$specific_location = trim($data['specific_location'] ?? '');
$status = isset($data['status']) ? intval($data['status']) : 1;
if (!$id || !$name || !$location) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields.']);
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
// Update item
$update = $conn->prepare('UPDATE items SET name=?, description=?, location=?, specific_location=?, status=? WHERE id=?');
$update->bind_param('ssssii', $name, $description, $location, $specific_location, $status, $id);
if ($update->execute()) {
    // Log to activity_log
    $action = 'edit';
    $desc = "Edited item: $name (ID: $id)";
    $log = $conn->prepare('INSERT INTO activity_log (user_id, action, details, created_at) VALUES (?, ?, ?, NOW())');
    $log->bind_param('iss', $user_id, $action, $desc);
    $log->execute();
    $log->close();
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update item.']);
}
$update->close(); 