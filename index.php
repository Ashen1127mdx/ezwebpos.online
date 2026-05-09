<?php
// Start session if needed
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>POS System - Product Management</title>
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
    <a href="index.php" class="nav-item active">📋 Products</a>
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
    <a href="categories.php" class="nav-item">🗂️ Categories</a>
</nav>
        </div>

        <!-- Main Content -------->
        <div class="main-content">
            <div class="view-header">
                <h1>Product Management</h1>
                <a href="add_product.php" class="btn-add">
                    <span>➕</span> Add New Product
                </a>
            </div>
            
            <!-- Search & Filter -->
<div class="filters">
    <input type="text" id="searchInput" placeholder="🔍 Search by name or barcode..." 
           class="search-input" oninput="loadProducts()">
    <select id="categoryFilter" class="category-filter" onchange="loadProducts()">
        <option value="">All Categories</option>
    </select>
</div>

            <!-- Products Table -->
            <div class="table-container">
                <table class="products-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Product Name</th>
                            <th>Barcode</th>
                            <th>Price</th>
							<th>Cost</th>
                            <th>Stock</th>
                            <th>Category</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="productsTableBody">
                        <tr>
                            <td colspan="9" class="loading">Loading products...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <h3>Confirm Delete</h3>
            <p>Are you sure you want to delete <strong id="deleteProductName"></strong>?</p>
            <p class="warning">⚠️ This action cannot be undone!</p>
            <div class="modal-buttons">
                <button onclick="confirmDelete()" class="btn-danger">Delete</button>
                <button onclick="closeModal()" class="btn-secondary">Cancel</button>
            </div>
        </div>
    </div>

    <div id="messageBox" class="message-area" style="position: fixed; bottom: 20px; right: 20px; z-index: 1000;"></div>
    <script src="script.js"></script>
<script>
    // Load products on page load
    document.addEventListener('DOMContentLoaded', () => {
        console.log('Page loaded - initializing search and filters');
        
        // Load initial data
        loadProducts();
        loadCategories();
        
        // Search input - real-time search as user types
        const searchInput = document.getElementById('searchInput');
        if (searchInput) {
            console.log('Search input found, adding event listener');
            searchInput.addEventListener('input', function(e) {
                const searchTerm = e.target.value;
                console.log('🔍 Searching for:', searchTerm);
                loadProducts(); // Reload products with new search term
            });
        } else {
            console.error('Search input element not found!');
        }
        
        // Category filter - load products when category changes
        const categoryFilter = document.getElementById('categoryFilter');
        if (categoryFilter) {
            console.log('Category filter found, adding event listener');
            categoryFilter.addEventListener('change', function(e) {
                const categoryId = e.target.value;
                console.log('📁 Category changed to:', categoryId);
                loadProducts(); // Reload products with new category
            });
        } else {
            console.error('Category filter element not found!');
        }
    });
</script>
</body>
</html>