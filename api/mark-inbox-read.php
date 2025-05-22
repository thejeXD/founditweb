<?php
session_start();
$timeout_duration = 1200;
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY']) > $timeout_duration) {
    session_unset();
    session_destroy();
    echo json_encode(['success' => false, 'message' => 'Session expired']);
    exit();
}
$_SESSION['LAST_ACTIVITY'] = time();

require_once '../database/connection.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
if (!isset($data['id'])) {
    echo json_encode(['success' => false, 'message' => 'No message id provided']);
    exit();
}

$user_id = $_SESSION['user_id'];
$message_id = intval($data['id']);
$stmt = $conn->prepare("UPDATE inbox SET is_read = 1 WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $message_id, $user_id);
$stmt->execute();
if ($stmt->affected_rows > 0) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Message not found or already read']);
} 