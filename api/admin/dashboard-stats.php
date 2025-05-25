<?php
require_once __DIR__ . '/../../database/connection.php';

header('Content-Type: application/json');

$response = [
    'success' => false,
    'total_users' => 0,
    'total_items' => 0,
    'active_cases' => 0,
    'resolved_cases' => 0,
    'active_users' => 0,
    'archived_users' => 0
];

try {
    // Total users
    $users = $conn->query("SELECT COUNT(*) FROM accounts");
    $row = $users->fetch_row();
    $response['total_users'] = (int)$row[0];

    // Active users
    $activeUsers = $conn->query("SELECT COUNT(*) FROM accounts WHERE status = 1");
    $row = $activeUsers->fetch_row();
    $response['active_users'] = (int)$row[0];

    // Archived users
    $archivedUsers = $conn->query("SELECT COUNT(*) FROM accounts WHERE status = 2");
    $row = $archivedUsers->fetch_row();
    $response['archived_users'] = (int)$row[0];

    // Total items
    $items = $conn->query("SELECT COUNT(*) FROM items");
    $row = $items->fetch_row();
    $response['total_items'] = (int)$row[0];

    // Active cases (not archived)
    $active = $conn->query("SELECT COUNT(*) FROM items WHERE status = 1");
    $row = $active->fetch_row();
    $response['active_cases'] = (int)$row[0];

    // Resolved cases (archived)
    $archived = $conn->query("SELECT COUNT(*) FROM items WHERE status = 0");
    $row = $archived->fetch_row();
    $response['resolved_cases'] = (int)$row[0];

    $response['success'] = true;
} catch (Exception $e) {
    $response['error'] = $e->getMessage();
}

echo json_encode($response); 