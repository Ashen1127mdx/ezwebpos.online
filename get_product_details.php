<?php
header('Content-Type: application/json');
require_once 'db_connection.php';

if (!$db) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed']);
    exit;
}

$search = isset($_GET['search']) ? trim($_GET['search']) : '';

if (empty($search)) {
    echo json_encode(['status' => 'error', 'message' => 'Please enter barcode or product ID']);
    exit;
}

// Search by barcode or ID
$stmt = $db->prepare("SELECT * FROM products WHERE barcode = ? OR id = ?");
$stmt->bind_param("si", $search, $search);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['status' => 'error', 'message' => 'Product not found']);
    exit;
}

$product = $result->fetch_assoc();

echo json_encode([
    'status' => 'success',
    'data' => $product
]);
?>