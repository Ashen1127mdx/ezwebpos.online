<?php
/**
 * API endpoint for inserting products
 * Handles AJAX requests from the frontend
 */

header('Content-Type: application/json');

// Enable error reporting for debugging (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors directly, we'll handle them

require_once 'db_connection.php';

// Only handle POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Only POST requests are allowed']);
    exit;
}

// Check database connection
if (!$db) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed. Please check your configuration.']);
    exit;
}

// Get and sanitize input data
$name = trim($_POST['name'] ?? '');
$barcode = trim($_POST['barcode'] ?? '');
$price = floatval($_POST['price'] ?? 0);
$cost = intval($_POST['cost'] ?? 0);
$category_id = intval($_POST['category_id'] ?? 0);

// Validation
$errors = [];

if (empty($name)) {
    $errors[] = "Product name is required";
}
if (empty($barcode)) {
    $errors[] = "Barcode is required";
}
if ($price <= 0) {
    $errors[] = "Price must be greater than 0";
}
if ($price > 999999.99) {
    $errors[] = "Price is too high";
}
if ($cost < 0) {
    $errors[] = "cost cannot be negative";
}
if ($category_id <= 0) {
    $errors[] = "Please select a valid category";
}

if (!empty($errors)) {
    echo json_encode(['status' => 'error', 'message' => implode(' • ', $errors)]);
    exit;
}

// Check if barcode already exists
$checkStmt = $db->prepare("SELECT id, name FROM products WHERE barcode = ?");
$checkStmt->bind_param("s", $barcode);
$checkStmt->execute();
$checkResult = $checkStmt->get_result();

if ($checkResult->num_rows > 0) {
    $existing = $checkResult->fetch_assoc();
    $checkStmt->close();
    echo json_encode([
        'status' => 'error', 
        'message' => "Barcode '{$barcode}' already exists in product: {$existing['name']}"
    ]);
    exit;
}
$checkStmt->close();

// Verify category exists (foreign key constraint)
$catCheck = $db->prepare("SELECT id, name FROM categories WHERE id = ?");
$catCheck->bind_param("i", $category_id);
$catCheck->execute();
$catResult = $catCheck->get_result();

if ($catResult->num_rows === 0) {
    $catCheck->close();
    echo json_encode(['status' => 'error', 'message' => "Selected category does not exist. Foreign key violation prevented."]);
    exit;
}
$category = $catResult->fetch_assoc();
$catCheck->close();

// Insert product
$stmt = $db->prepare("INSERT INTO products (name, barcode, cost, price, category_id) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("ssdii", $name, $barcode, $cost, $price, $category_id);

if ($stmt->execute()) {
    $insertId = $stmt->insert_id;
    $stmt->close();
    
    echo json_encode([
        'status' => 'success',
        'message' => "✅ Product '{$name}' added successfully! (ID: {$insertId})",
        'data' => [
            'id' => $insertId,
            'name' => $name,
            'barcode' => $barcode,
            'price' => $price,
            'cost' => $cost,
            'category' => $category['name']
        ]
    ]);
} else {
    $errorMsg = "Insert failed: " . $stmt->error;
    $stmt->close();
    echo json_encode(['status' => 'error', 'message' => $errorMsg]);
}
?>