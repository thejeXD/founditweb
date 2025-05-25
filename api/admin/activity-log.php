<?php
require_once __DIR__ . '/../../database/connection.php';

header('Content-Type: application/json');

$response = [
    'success' => false,
    'activities' => []
];

try {
    $sql = "SELECT a.id, a.admin_id, a.action, a.details, a.created_at, CONCAT(ac.first_name, ' ', ac.last_name) AS admin_name FROM activity_log_admin a LEFT JOIN accounts ac ON a.admin_id = ac.id ORDER BY a.created_at DESC LIMIT 10";
    $result = $conn->query($sql);
    while ($row = $result->fetch_assoc()) {
        $response['activities'][] = $row;
    }
    $response['success'] = true;
} catch (Exception $e) {
    $response['error'] = $e->getMessage();
}

echo json_encode($response); 