<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>POS System - Add Product</title>
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
    <a href="add_product.php" class="nav-item active">➕ Add Product</a>
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
    <a href="categories.php" class="nav-item">🗂️ Categories</a>
</nav>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="view-header">
                <a href="index.php" class="btn-back">
                    ← Back to Products
                </a>
                <h1>Add New Product</h1>
            </div>

            <div class="form-container">
                <form id="productForm" method="POST" action="insert_product.php">
                    <div class="input-group">
                        <label>📛 Product Name <span class="required">*</span></label>
                        <input type="text" name="name" id="name" required>
                    </div>

                    <div class="input-group">
                        <label>🔖 Barcode <span class="required">*</span></label>
                        <input type="text" name="barcode" id="barcode" required>
                    </div>

                    <div class="row-2col">
                        <div class="input-group">
                            <label>💰 Price ($) <span class="required">*</span></label>
                            <input type="number" name="price" id="price" step="0.01" min="0" required>
                        </div>
                        <div class="input-group">
                            <label>📦 Cost <span class="required">*</span></label>
                            <input type="number" name="cost" id="cost" step="0.01" min="0" required>
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
                            💾 Save Product
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
    // Load categories when page loads
    document.addEventListener('DOMContentLoaded', function() {
        loadCategoriesForSelect();
        
        // Handle form submission
        const form = document.getElementById('productForm');
        if (form) {
            form.addEventListener('submit', async (e) => {
                e.preventDefault();
                
                const formData = new FormData(form);
                formData.append('action', 'insert_product');
                
                const submitBtn = document.getElementById('submitBtn');
                const originalText = submitBtn.textContent;
                submitBtn.textContent = 'Saving...';
                submitBtn.disabled = true;
                
                try {
                    const response = await fetch('insert_product.php', {
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
                    showMessage('Network error saving product', 'error');
                    submitBtn.textContent = originalText;
                    submitBtn.disabled = false;
                }
            });
        }
    });
</script>
</body>
</html>