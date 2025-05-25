<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../database/connection.php';

$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = isset($_GET['per_page']) ? max(1, intval($_GET['per_page'])) : 0;
$offset = ($page - 1) * $per_page;

$sql = "SELECT id, location, specific_location, name, description, date_time, status, type FROM items WHERE status >= 0 ORDER BY date_time DESC";
if ($per_page > 0) {
  $sql .= " LIMIT $per_page OFFSET $offset";
}
$result = $conn->query($sql);
$items = [];
while ($row = $result->fetch_assoc()) {
  $items[] = $row;
}
echo json_encode(['success' => true, 'items' => $items]); 