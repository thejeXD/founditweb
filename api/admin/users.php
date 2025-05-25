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
$result = $conn->query('SELECT id, first_name, last_name, email, student_number, password, status, role, last_login, created_at FROM accounts WHERE status != 3');
$users = [];
while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}
echo json_encode(['success' => true, 'users' => $users]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (($data['action'] ?? '') === 'message' && !empty($data['id']) && !empty($data['subject']) && !empty($data['message'])) {
        $id = intval($data['id']);
        $subject = $data['subject'];
        $message = $data['message'];
        $inbox = $conn->prepare('INSERT INTO inbox (user_id, subject, message, is_read, created_at) VALUES (?, ?, ?, 0, NOW())');
        $inbox->bind_param('iss', $id, $subject, $message);
        if ($inbox->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to send message.']);
        }
        $inbox->close();
        exit();
    }
} 