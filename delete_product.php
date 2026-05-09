<?php
header('Content-Type: application/json');
require_once 'db_connection.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Only POST requests are allowed']);
    exit;
}

if (!$db) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed']);
    exit;
}

$id = isset($_POST['id']) ? intval($_POST['id']) : 0;

if ($id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid product ID']);
    exit;
}

// First, check if the product exists
$checkStmt = $db->prepare("SELECT name FROM products WHERE id = ?");
$checkStmt->bind_param("i", $id);
$checkStmt->execute();
$checkResult = $checkStmt->get_result();

if ($checkResult->num_rows === 0) {
    echo json_encode(['status' => 'error', 'message' => 'Product not found']);
    $checkStmt->close();
    exit;
}

$product = $checkResult->fetch_assoc();
$productName = $product['name'];
$checkStmt->close();

// Check if product exists in any GRN (optional - prevent deletion if in GRN)
$grnCheck = $db->prepare("SELECT COUNT(*) as count FROM grn_items WHERE product_id = ?");
$grnCheck->bind_param("i", $id);
$grnCheck->execute();
$grnResult = $grnCheck->get_result();
$grnCount = $grnResult->fetch_assoc()['count'];
$grnCheck->close();

if ($grnCount > 0) {
    echo json_encode([
        'status' => 'error', 
        'message' => "Cannot delete product. It appears in {$grnCount} GRN record(s)."
    ]);
    exit;
}

// Delete the product
$stmt = $db->prepare("DELETE FROM products WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    echo json_encode([
        'status' => 'success',
        'message' => "✅ Product '{$productName}' deleted successfully!"
    ]);
} else {
    echo json_encode([
        'status' => 'error', 
        'message' => "Delete failed: " . $stmt->error
    ]);
}

$stmt->close();
?>