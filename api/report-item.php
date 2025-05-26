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
$item_id = isset($data['item_id']) ? intval($data['item_id']) : 0;
$type = isset($data['type']) ? trim($data['type']) : '';
$details = isset($data['details']) ? trim($data['details']) : '';
if (!$item_id || !$type) {
  echo json_encode(['success' => false, 'message' => 'Missing required fields.']);
  exit();
}
// Get author_id from items table
$item_stmt = $conn->prepare('SELECT user_id FROM items WHERE id = ?');
$item_stmt->bind_param('i', $item_id);
$item_stmt->execute();
$item_stmt->bind_result($author_id);
if (!$item_stmt->fetch()) {
  $item_stmt->close();
  echo json_encode(['success' => false, 'message' => 'Item not found.']);
  exit();
}
$item_stmt->close();
// Fetch reporter details
$reporter_stmt = $conn->prepare('SELECT id, first_name, last_name, email FROM accounts WHERE id = ?');
$reporter_stmt->bind_param('i', $user_id);
$reporter_stmt->execute();
$reporter = $reporter_stmt->get_result()->fetch_assoc();
$reporter_stmt->close();
// Fetch author details
$author_stmt = $conn->prepare('SELECT id, first_name, last_name, email FROM accounts WHERE id = ?');
$author_stmt->bind_param('i', $author_id);
$author_stmt->execute();
$author = $author_stmt->get_result()->fetch_assoc();
$author_stmt->close();
// Fetch item name
$item_name = '';
$item_name_stmt = $conn->prepare('SELECT name FROM items WHERE id = ?');
$item_name_stmt->bind_param('i', $item_id);
$item_name_stmt->execute();
$item_name_stmt->bind_result($item_name);
$item_name_stmt->fetch();
$item_name_stmt->close();
// Insert report
$stmt = $conn->prepare('INSERT INTO item_reports (item_id, user_id, author_id, type, details) VALUES (?, ?, ?, ?, ?)');
$stmt->bind_param('iiiss', $item_id, $user_id, $author_id, $type, $details);
if (!$stmt->execute()) {
  echo json_encode(['success' => false, 'message' => 'Failed to submit report.']);
  exit();
}
$stmt->close();
// Notify all admins and staff
$admin_stmt = $conn->query("SELECT id FROM accounts WHERE role IN (2,3)");
while ($admin = $admin_stmt->fetch_assoc()) {
  $admin_id = $admin['id'];
  $subject = 'Item Reported(admin/staff must check)';
  $msg = 'An item has been reported.<br>' .
    '<b>Report Type:</b> ' . htmlspecialchars($type) . '<br>' .
    '<b>Details:</b> ' . nl2br(htmlspecialchars($details)) . '<br>' .
    '<b>Item:</b> ' . htmlspecialchars($item_name) . ' (ID: ' . $item_id . ')<br>' .
    '<b>Reported User:</b> ' .
      htmlspecialchars($author['first_name'] . ' ' . $author['last_name']) .
      ' (ID: ' . $author['id'] . ', Email: ' . htmlspecialchars($author['email']) . ')<br>' .
    '<b>Reporter:</b> ' .
      htmlspecialchars($reporter['first_name'] . ' ' . $reporter['last_name']) .
      ' (ID: ' . $reporter['id'] . ', Email: ' . htmlspecialchars($reporter['email']) . ')<br>' .
    '<a href=\'/FoundIT/pages/view-item.html?id=' . $item_id . '\'>View Item</a>';
  $inbox = $conn->prepare('INSERT INTO inbox (user_id, subject, message, is_read, created_at) VALUES (?, ?, ?, 0, NOW())');
  $inbox->bind_param('iss', $admin_id, $subject, $msg);
  $inbox->execute();
  $inbox->close();
}
// Notify the item owner
if ($author_id != $user_id) {
  $subject = 'Your item was reported';
  $msg = 'Your item <b>' . htmlspecialchars($item_name) . '</b> (ID: ' . $item_id . ') was reported.<br>' .
         '<b>Report Type:</b> ' . htmlspecialchars($type) . '<br>' .
         '<b>Details:</b> ' . nl2br(htmlspecialchars($details)) . '<br>' .
         '<b>Reporter:</b> ' .
           htmlspecialchars($reporter['first_name'] . ' ' . $reporter['last_name']) .
           ' (ID: ' . $reporter['id'] . ', Email: ' . htmlspecialchars($reporter['email']) . ')<br>' .
         '<a href=\'/FoundIT/pages/view-item.html?id=' . $item_id . '\'>View Item</a>';
  $inbox = $conn->prepare('INSERT INTO inbox (user_id, subject, message, is_read, created_at) VALUES (?, ?, ?, 0, NOW())');
  $inbox->bind_param('iss', $author_id, $subject, $msg);
  $inbox->execute();
  $inbox->close();
}
echo json_encode(['success' => true, 'message' => 'Report submitted.']); 