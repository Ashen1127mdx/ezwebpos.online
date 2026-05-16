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
    <a href="categories.php" class="nav-item">🗂️ Categories</a>
</nav>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="view-header">
                <h1>Salse Reports</h1>
                
            </div>
            
     


            <!-- Products Table -->
            <div class="table-container">
                <table class="products-table">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Date</th>
                            <th>Total Sales</th>
                            <th>Action/Needs</th>
							
                            
                        </tr>
                    </thead>
                    <tbody id="grnTableBody">
                        <tr>
                            <td colspan="4" class="loading">Loading products...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    
    

    
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
