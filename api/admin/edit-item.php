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
    echo json_encode(['success' => false, 'message' => 'Missing item id.']);
    exit();
}
$name = trim($data['name'] ?? '');
$category = trim($data['category'] ?? '');
$description = trim($data['description'] ?? '');
$date_time = trim($data['date_time'] ?? '');
$location = trim($data['location'] ?? '');
$specific_location = trim($data['specific_location'] ?? '');
$status = intval($data['status'] ?? 1);
$type = trim($data['type'] ?? '');
if (!$name || !$category || !$date_time || !$type) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields.']);
    exit();
}
$stmt = $conn->prepare('UPDATE items SET name=?, category=?, description=?, date_time=?, location=?, specific_location=?, status=?, type=? WHERE id=?');
$stmt->bind_param('ssssssisi', $name, $category, $description, $date_time, $location, $specific_location, $status, $type, $id);
if ($stmt->execute()) {
    // Log to activity_log_admin
    $action = 'edit_item';
    $details = "Edited item (ID: $id, Name: $name)";
    $log = $conn->prepare('INSERT INTO activity_log_admin (admin_id, action, target_type, target_id, details) VALUES (?, ?, ?, ?, ?)');
    $target_type = 'item';
    $log->bind_param('issis', $user_id, $action, $target_type, $id, $details);
    $log->execute();
    $log->close();
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update item.']);
}
$stmt->close(); 