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
// Get user profile
$stmt = $conn->prepare("SELECT first_name, last_name, student_number, email FROM accounts WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$profile = $stmt->get_result()->fetch_assoc();

// Get account activity
$stmt = $conn->prepare("SELECT 
    SUM(type='lost') AS total_lost, 
    SUM(type='found') AS total_found, 
    COUNT(*) AS total_items 
    FROM items WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$activity = $stmt->get_result()->fetch_assoc();
if (!$activity['total_lost']) $activity['total_lost'] = 0;
if (!$activity['total_found']) $activity['total_found'] = 0;
if (!$activity['total_items']) $activity['total_items'] = 0;

echo json_encode([
    'success' => true,
    'profile' => $profile,
    'activity' => $activity
]); 