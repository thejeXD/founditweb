<?php
require_once __DIR__ . '/../../database/connection.php';

header('Content-Type: application/json');

$response = [
    'success' => false,
    'activities' => []
];

$type = isset($_GET['type']) ? $_GET['type'] : 'admin';
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 20;

try {
    if ($type === 'user') {
        $sql = "SELECT l.id, l.user_id, l.action, l.details, l.created_at, CONCAT(a.first_name, ' ', a.last_name) AS user_name FROM activity_log l LEFT JOIN accounts a ON l.user_id = a.id ORDER BY l.created_at DESC LIMIT ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $response['activities'][] = $row;
        }
        $stmt->close();
    } else {
        $sql = "SELECT a.id, a.admin_id, a.action, a.details, a.created_at, CONCAT(ac.first_name, ' ', ac.last_name) AS admin_name, ac.role AS admin_role FROM activity_log_admin a LEFT JOIN accounts ac ON a.admin_id = ac.id ORDER BY a.created_at DESC LIMIT ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            if (isset($row['admin_role'])) $row['admin_role'] = (int)$row['admin_role'];
            $response['activities'][] = $row;
        }
        $stmt->close();
    }
    $response['success'] = true;
} catch (Exception $e) {
    $response['error'] = $e->getMessage();
}

echo json_encode($response); 