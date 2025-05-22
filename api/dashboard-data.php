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

// Get user info
$stmt = $conn->prepare("SELECT first_name, last_name FROM accounts WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Get lost/found counts
$stmt = $conn->prepare("SELECT 
    SUM(type='lost') AS total_lost, 
    SUM(type='found') AS total_found 
    FROM items WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$counts = $stmt->get_result()->fetch_assoc();
if (!$counts['total_lost']) $counts['total_lost'] = 0;
if (!$counts['total_found']) $counts['total_found'] = 0;

// Get recent items
$stmt = $conn->prepare("SELECT name, category, status, date_time, location FROM items WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$recent = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

echo json_encode([
    'success' => true,
    'user' => $user,
    'counts' => $counts,
    'recent' => $recent
]); 