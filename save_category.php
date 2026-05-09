<?php
session_start();
require_once 'db_connection.php';

$id = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;
$name = trim($_POST['name'] ?? '');

if (empty($name)) {
    $_SESSION['error'] = "Category name is required";
    header('Location: categories.php');
    exit;
}

if ($id > 0) {
    // Update existing category
    $stmt = $db->prepare("UPDATE categories SET name = ? WHERE id = ?");
    $stmt->bind_param("si", $name, $id);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Category updated successfully!";
    } else {
        $_SESSION['error'] = "Failed to update category: " . $db->error;
    }
    $stmt->close();
} else {
    // Add new category
    $stmt = $db->prepare("INSERT INTO categories (name) VALUES (?)");
    $stmt->bind_param("s", $name);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Category added successfully!";
    } else {
        $_SESSION['error'] = "Failed to add category: " . $db->error;
    }
    $stmt->close();
}

header('Location: categories.php');
exit;
?>