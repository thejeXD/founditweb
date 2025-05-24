<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../database/connection.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'You must be logged in to claim an item.']);
    exit();
}
$user_id = $_SESSION['user_id'];

$data = json_decode(file_get_contents('php://input'), true);
$item_id = isset($data['item_id']) ? intval($data['item_id']) : 0;
if (!$item_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid item.']);
    exit();
}
// Get item and author
$stmt = $conn->prepare('SELECT id, user_id, name FROM items WHERE id = ?');
$stmt->bind_param('i', $item_id);
$stmt->execute();
$result = $stmt->get_result();
$item = $result->fetch_assoc();
if (!$item) {
    echo json_encode(['success' => false, 'message' => 'Item not found.']);
    exit();
}
if ($item['user_id'] == $user_id) {
    echo json_encode(['success' => false, 'message' => 'You cannot claim your own item.']);
    exit();
}
// Prevent spam: check if already claimed (inbox message exists)
$check_stmt = $conn->prepare('SELECT id FROM inbox WHERE user_id = ? AND subject = ? AND message LIKE ?');
$subject = 'Someone is claiming your item!';
$like = '%id=' . $item_id . '%';
$check_stmt->bind_param('iss', $item['user_id'], $subject, $like);
$check_stmt->execute();
$check_stmt->store_result();
if ($check_stmt->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'You have already claimed this item. Please wait for the owner to respond.']);
    exit();
}
$check_stmt->close();
// Get claimer info
$user_stmt = $conn->prepare('SELECT first_name, last_name, email FROM accounts WHERE id = ?');
$user_stmt->bind_param('i', $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$claimer = $user_result->fetch_assoc();
$user_stmt->close();
// Notify the author
$link = '/FoundIT/pages/view-item.html?id=' . $item_id;
$subject = 'Someone is claiming your item!';
$msg = 'A user is claiming your item: <b>' . htmlspecialchars($item['name']) . '</b>.<br>' .
       'Claimer: <b>' . htmlspecialchars($claimer['first_name'] . ' ' . $claimer['last_name']) . '</b> (' . htmlspecialchars($claimer['email']) . ')<br>' .
       '<a href="' . $link . '">View Item</a>';
$inbox_stmt = $conn->prepare('INSERT INTO inbox (user_id, subject, message, is_read, created_at) VALUES (?, ?, ?, 0, NOW())');
$inbox_stmt->bind_param('iss', $item['user_id'], $subject, $msg);
$inbox_stmt->execute();
$inbox_stmt->close();
echo json_encode(['success' => true, 'message' => 'The author has been notified.']); 