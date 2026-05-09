<?php
header('Content-Type: application/json');
require_once 'db_connection.php';

if (!$db) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed']);
    exit;
}

$result = $db->query("SELECT id, name FROM categories ORDER BY name ASC");

if (!$result) {
    echo json_encode(['status' => 'error', 'message' => 'Failed to fetch categories']);
    exit;
}

$categories = [];
while ($row = $result->fetch_assoc()) {
    $categories[] = $row;
}

echo json_encode([
    'status' => 'success',
    'data' => $categories,
    'count' => count($categories)
]);
?>