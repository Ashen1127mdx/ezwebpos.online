<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'db_connection.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0);

if (!$db) {
    echo json_encode([
        'status' => 'error', 
        'message' => 'Database connection failed'
    ]);
    exit;
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    echo json_encode([
        'status' => 'error', 
        'message' => 'Invalid product ID. ID must be a positive number.'
    ]);
    exit;
}

// Prepare and execute query
$stmt = $db->prepare("SELECT p.*, c.name as category_name 
                       FROM products p 
                       LEFT JOIN categories c ON p.category_id = c.id 
                       WHERE p.id = ?");
                       
if (!$stmt) {
    echo json_encode([
        'status' => 'error', 
        'message' => 'Database prepare failed: ' . $db->error
    ]);
    exit;
}

$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode([
        'status' => 'error', 
        'message' => "Product with ID {$id} not found"
    ]);
    $stmt->close();
    exit;
}

$product = $result->fetch_assoc();

// Ensure numeric values are proper types
$product['id'] = intval($product['id']);
$product['price'] = floatval($product['price']);
$product['stock'] = intval($product['stock']);
$product['category_id'] = intval($product['category_id']);

echo json_encode([
    'status' => 'success',
    'data' => $product,
    'message' => 'Product loaded successfully'
]);

$stmt->close();
?>