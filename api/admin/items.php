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
$sql = 'SELECT i.id, i.user_id, i.name, i.category, i.description, i.date_time, i.location, i.specific_location, i.status, i.type, i.created_at, a.first_name, a.last_name, a.email FROM items i LEFT JOIN accounts a ON i.user_id = a.id WHERE i.status != 2 ORDER BY i.created_at DESC';
$result = $conn->query($sql);
$items = [];
while ($row = $result->fetch_assoc()) {
    $items[] = $row;
}
echo json_encode(['success' => true, 'items' => $items]); 