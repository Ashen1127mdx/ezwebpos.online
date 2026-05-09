<?php
session_start();
require_once 'db_connection.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    header('Location: index.php');
    exit;
}

// Fetch product data
$stmt = $db->prepare("SELECT * FROM products WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: index.php');
    exit;
}

$product = $result->fetch_assoc();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>POS System - Edit Product</title>
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
                <a href="index.php" class="nav-item">
                    <span>📋</span> Products
                </a>
                <a href="add_product.php" class="nav-item">
                    <span>➕</span> Add Product
                </a>
                <a href="categories.php" class="nav-item">
                    <span>🗂️</span> Categories
                </a>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="view-header">
                <a href="index.php" class="btn-back">
                    ← Back to Products
                </a>
                <h1>Edit Product</h1>
            </div>

            <div class="form-container">
                <form id="productForm" method="POST" action="update_product.php">
                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                    
                    <div class="input-group">
                        <label>📛 Product Name <span class="required">*</span></label>
                        <input type="text" name="name" id="name" value="<?php echo htmlspecialchars($product['name']); ?>" required>
                    </div>

                    <div class="input-group">
                        <label>🔖 Barcode <span class="required">*</span></label>
                        <input type="text" name="barcode" id="barcode" value="<?php echo htmlspecialchars($product['barcode']); ?>" required>
                    </div>

                    <div class="row-2col">
                        <div class="input-group">
                            <label>💰 Price ($) <span class="required">*</span></label>
                            <input type="number" name="price" id="price" step="0.01" min="0" value="<?php echo $product['price']; ?>" required>
                        </div>
                        <div class="input-group">
                            <label>📦 cost <span class="required">*</span></label>
                            <input type="number" name="cost" id="cost" step="0.01" min="0" value="<?php echo $product['cost']; ?>" required>
                        </div>
                    </div>

                    <div class="input-group">
                        <label>🗂️ Category <span class="required">*</span></label>
                        <select name="category_id" id="category_id" required>
                            <option value="">-- Select Category --</option>
                        </select>
                    </div>

                    <div class="action-buttons">
                        <button type="submit" class="btn btn-primary" id="submitBtn">
                            💾 Update Product
                        </button>
                        <a href="index.php" class="btn btn-secondary">
                            ❌ Cancel
                        </a>
                    </div>
                </form>

                <div id="messageBox"></div>
            </div>
        </div>
    </div>

    <script src="script.js"></script>
    <script>
        // Load categories and set selected value
        document.addEventListener('DOMContentLoaded', async () => {
            await loadCategoriesForSelect();
            
            // Set selected category
            const categorySelect = document.getElementById('category_id');
            if (categorySelect) {
                categorySelect.value = '<?php echo $product['category_id']; ?>';
            }
            
            // Handle form submission
            const form = document.getElementById('productForm');
            if (form) {
                form.addEventListener('submit', handleEditFormSubmit);
            }
        });
        
        async function handleEditFormSubmit(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            const submitBtn = document.getElementById('submitBtn');
            const originalText = submitBtn.textContent;
            submitBtn.textContent = 'Updating...';
            submitBtn.disabled = true;
            
            try {
                const response = await fetch('update_product.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.status === 'success') {
                    showMessage(result.message, 'success');
                    setTimeout(() => {
                        window.location.href = 'index.php';
                    }, 1500);
                } else {
                    showMessage(result.message, 'error');
                    submitBtn.textContent = originalText;
                    submitBtn.disabled = false;
                }
            } catch (error) {
                console.error('Error:', error);
                showMessage('Network error updating product', 'error');
                submitBtn.textContent = originalText;
                submitBtn.disabled = false;
            }
        }
    </script>
</body>
</html>