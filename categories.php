<?php
session_start();
require_once 'db_connection.php';

// Handle category deletion
if (isset($_POST['delete_category']) && isset($_POST['category_id'])) {
    $cat_id = intval($_POST['category_id']);
    
    // Check if category has products
    $checkStmt = $db->prepare("SELECT COUNT(*) as count FROM products WHERE category_id = ?");
    $checkStmt->bind_param("i", $cat_id);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    $count = $checkResult->fetch_assoc()['count'];
    $checkStmt->close();
    
    if ($count > 0) {
        $error = "Cannot delete category with {$count} products. Please reassign or delete products first.";
    } else {
        $deleteStmt = $db->prepare("DELETE FROM categories WHERE id = ?");
        $deleteStmt->bind_param("i", $cat_id);
        if ($deleteStmt->execute()) {
            $success = "Category deleted successfully!";
        } else {
            $error = "Failed to delete category: " . $db->error;
        }
        $deleteStmt->close();
    }
}

// Fetch all categories
$result = $db->query("SELECT * FROM categories ORDER BY name ASC");
$categories = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        // Get product count for each category
        $countStmt = $db->prepare("SELECT COUNT(*) as count FROM products WHERE category_id = ?");
        $countStmt->bind_param("i", $row['id']);
        $countStmt->execute();
        $countResult = $countStmt->get_result();
        $row['product_count'] = $countResult->fetch_assoc()['count'];
        $countStmt->close();
        
        $categories[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>POS System - Categories</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar Navigation -->
        <div class="sidebar">
            <div class="logo">
                <h2>📦 POS System</h2>
            </div>
<nav class="nav-menu">
    <a href="index.php" class="nav-item">📋 Products</a>
    <a href="add_product.php" class="nav-item">➕ Add Product</a>
    <a href="grn.php" class="nav-item">📥 GRN</a>
    <!-- Dropdown Menu - Expands Down -->
    <div class="dropdown">
        <a href="#" class="nav-item dropdown-toggle">📊 Reports</a>
        <div class="dropdown-menu">
            <a href="grn_reports.php" class="dropdown-item">📋 GRN Reports</a>
            <a href="sales_reports.php" class="dropdown-item">💰 Sales Reports</a>
            <a href="stock_reports.php" class="dropdown-item">📦 Stock Reports</a>
            <a href="category_reports.php" class="dropdown-item">🗂️ Category Reports</a>
        </div>
    </div>
    <a href="categories.php" class="nav-item active">🗂️ Categories</a>
</nav>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="view-header">
                <h1>Categories Management</h1>
                <button class="btn-add" onclick="showAddCategoryForm()">
                    <span>➕</span> Add Category
                </button>
            </div>

            <?php if (isset($success)): ?>
                <div class="message-area show message-success">✅ <?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="message-area show message-error">❌ <?php echo $error; ?></div>
            <?php endif; ?>

            <div class="table-container">
                <table class="products-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Category Name</th>
                            <th>Products Count</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($categories) > 0): ?>
                            <?php foreach ($categories as $category): ?>
                            <tr>
                                <td><?php echo $category['id']; ?></td>
                                <td><strong><?php echo htmlspecialchars($category['name']); ?></strong></td>
                                <td><?php echo $category['product_count']; ?></td>
                                <td><?php echo date('Y-m-d', strtotime($category['created_at'])); ?></td>
                                <td>
                                    <div class="action-buttons-table">
                                        <button class="btn-edit" onclick="editCategory(<?php echo $category['id']; ?>, '<?php echo htmlspecialchars($category['name']); ?>')">✏️ Edit</button>
                                        <?php if ($category['product_count'] == 0): ?>
                                            <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this category?');">
                                                <input type="hidden" name="category_id" value="<?php echo $category['id']; ?>">
                                                <button type="submit" name="delete_category" class="btn-delete">🗑️ Delete</button>
                                            </form>
                                        <?php else: ?>
                                            <button class="btn-delete" disabled style="opacity: 0.5; cursor: not-allowed;" title="Cannot delete: Category has <?php echo $category['product_count']; ?> products">🗑️ Delete</button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="loading">No categories found. Click "Add Category" to create one.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Add/Edit Category Modal -->
    <div id="categoryModal" class="modal">
        <div class="modal-content">
            <h3 id="modalTitle">Add New Category</h3>
            <form id="categoryForm" method="POST" action="save_category.php">
                <input type="hidden" name="category_id" id="categoryId">
                <div class="input-group">
                    <label>Category Name <span class="required">*</span></label>
                    <input type="text" name="name" id="categoryName" required>
                </div>
                <div class="modal-buttons">
                    <button type="submit" class="btn-primary">Save</button>
                    <button type="button" class="btn-secondary" onclick="closeCategoryModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    <script src="script.js"></script>                        
    <script>
        function showAddCategoryForm() {
            document.getElementById('modalTitle').textContent = 'Add New Category';
            document.getElementById('categoryId').value = '';
            document.getElementById('categoryName').value = '';
            document.getElementById('categoryModal').classList.add('show');
        }
        
        function editCategory(id, name) {
            document.getElementById('modalTitle').textContent = 'Edit Category';
            document.getElementById('categoryId').value = id;
            document.getElementById('categoryName').value = name;
            document.getElementById('categoryModal').classList.add('show');
        }
        
        function closeCategoryModal() {
            document.getElementById('categoryModal').classList.remove('show');
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('categoryModal');
            if (event.target === modal) {
                closeCategoryModal();
            }
        }
    </script>
</body>
</html>