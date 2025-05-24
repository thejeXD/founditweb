<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../database/connection.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid item ID']);
    exit();
}
$item_id = intval($_GET['id']);
$stmt = $conn->prepare('SELECT * FROM items WHERE id = ?');
$stmt->bind_param('i', $item_id);
$stmt->execute();
$result = $stmt->get_result();
$item = $result->fetch_assoc();
if (!$item) {
    echo json_encode(['success' => false, 'message' => 'Item not found']);
    exit();
}
// Get user info
$user_stmt = $conn->prepare('SELECT first_name, last_name, email FROM accounts WHERE id = ?');
$user_stmt->bind_param('i', $item['user_id']);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user = $user_result->fetch_assoc();
$user_stmt->close();
echo json_encode(['success' => true, 'item' => $item, 'user' => $user]); 