<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../database/connection.php';
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in.']);
    exit();
}
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare('UPDATE accounts SET status=2 WHERE id=?');
$stmt->bind_param('i', $user_id);
if ($stmt->execute()) {
    // Optionally destroy session
    session_destroy();
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to archive account.']);
}
$stmt->close(); 