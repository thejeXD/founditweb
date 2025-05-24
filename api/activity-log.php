<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../database/connection.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare('SELECT action, details, created_at FROM activity_log WHERE user_id = ? ORDER BY created_at DESC LIMIT 10');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$activities = $result->fetch_all(MYSQLI_ASSOC);
echo json_encode(['success' => true, 'activities' => $activities]); 