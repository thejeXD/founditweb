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

$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT id, from_user_id, subject, message, is_read, created_at, item_id FROM inbox WHERE user_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$messages = $result->fetch_all(MYSQLI_ASSOC);
echo json_encode(['success' => true, 'messages' => $messages]); 