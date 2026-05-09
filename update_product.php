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

$id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
$name = trim($_POST['name'] ?? '');
$barcode = trim($_POST['barcode'] ?? '');
$price = floatval($_POST['price'] ?? 0);
$cost = intval($_POST['cost'] ?? 0);
$category_id = intval($_POST['category_id'] ?? 0);

// Validation
$errors = [];
if ($id <= 0) $errors[] = "Invalid product ID";
if (empty($name)) $errors[] = "Product name is required";
if (empty($barcode)) $errors[] = "Barcode is required";
if ($price <= 0) $errors[] = "Price must be greater than 0";
if ($cost < 0) $errors[] = "Stock cannot be negative";
if ($category_id <= 0) $errors[] = "Please select a valid category";

if (!empty($errors)) {
    echo json_encode(['status' => 'error', 'message' => implode(' • ', $errors)]);
    exit;
}

// Check if barcode exists for other products
$checkStmt = $db->prepare("SELECT id FROM products WHERE barcode = ? AND id != ?");
$checkStmt->bind_param("si", $barcode, $id);
$checkStmt->execute();
$checkResult = $checkStmt->get_result();

if ($checkResult->num_rows > 0) {
    echo json_encode(['status' => 'error', 'message' => "Barcode '{$barcode}' already used by another product"]);
    $checkStmt->close();
    exit;
}
$checkStmt->close();

// Verify category exists
$catCheck = $db->prepare("SELECT id FROM categories WHERE id = ?");
$catCheck->bind_param("i", $category_id);
$catCheck->execute();
$catResult = $catCheck->get_result();

if ($catResult->num_rows === 0) {
    echo json_encode(['status' => 'error', 'message' => "Selected category does not exist"]);
    $catCheck->close();
    exit;
}
$catCheck->close();

// Update product
$stmt = $db->prepare("UPDATE products SET name = ?, barcode = ?, cost = ?, price = ?, category_id = ? WHERE id = ?");
$stmt->bind_param("ssdiii", $name, $barcode, $cost, $price, $category_id, $id);

if ($stmt->execute()) {
    echo json_encode([
        'status' => 'success',
        'message' => "✅ Product '{$name}' updated successfully!",
        'data' => ['id' => $id]
    ]);
} else {
    echo json_encode(['status' => 'error', 'message' => "Update failed: " . $stmt->error]);
}
$stmt->close();
?>