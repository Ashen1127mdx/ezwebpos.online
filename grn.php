<?php
session_start();
require_once 'db_connection.php';

// Generate GRN Number
function generateGRNNumber($db) {
    $year = date('Y');
    $month = date('m');
    
    $query = "SELECT grn_no FROM grn WHERE grn_no LIKE 'GRN-{$year}{$month}%' ORDER BY id DESC LIMIT 1";
    $result = $db->query($query);
    
    if ($result && $result->num_rows > 0) {
        $lastGRN = $result->fetch_assoc()['grn_no'];
        $lastNumber = intval(substr($lastGRN, -4));
        $newNumber = $lastNumber + 1;
    } else {
        $newNumber = 1;
    }
    
    return "GRN-{$year}{$month}-" . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
}

// Handle GRN creation (Step 1)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_grn'])) {
    $grn_no = generateGRNNumber($db);
    $supplier = trim($_POST['supplier']);
    
    $stmt = $db->prepare("INSERT INTO grn (grn_no, supplier) VALUES (?, ?)");
    $stmt->bind_param("ss", $grn_no, $supplier);
    
    if ($stmt->execute()) {
        $grn_id = $stmt->insert_id;
        $_SESSION['current_grn'] = [
            'id' => $grn_id,
            'grn_no' => $grn_no,
            'supplier' => $supplier,
            'created_at' => date('Y-m-d H:i:s'),
            'items' => []
        ];
        header('Location: grn.php?step=2');
        exit;
    } else {
        $error = "Failed to create GRN: " . $db->error;
    }
    $stmt->close();
}

// Handle adding items to GRN (AJAX)
if (isset($_POST['action']) && $_POST['action'] === 'add_item') {
    header('Content-Type: application/json');
    
    if (!isset($_SESSION['current_grn'])) {
        echo json_encode(['status' => 'error', 'message' => 'No active GRN session']);
        exit;
    }
    
    $search = trim($_POST['search']);
    $cost = floatval($_POST['cost']);
    $price = floatval($_POST['price']);
    $quantity = intval($_POST['quantity']);
    
    // Search product by barcode or id
    $stmt = $db->prepare("SELECT * FROM products WHERE barcode = ? OR id = ?");
    $stmt->bind_param("si", $search, $search);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['status' => 'error', 'message' => 'Product not found']);
        exit;
    }
    
    $product = $result->fetch_assoc();
    $stmt->close();
    
    $item = [
        'product_id' => $product['id'],
        'barcode' => $product['barcode'],
        'name' => $product['name'],
        'current_stock' => $product['stock'],
        'current_cost' => $product['cost'],
        'current_price' => $product['price'],
        'new_cost' => $cost,
        'new_price' => $price,
        'quantity' => $quantity
    ];
    
    $_SESSION['current_grn']['items'][] = $item;
    
    echo json_encode([
        'status' => 'success',
        'message' => 'Item added successfully',
        'item' => $item
    ]);
    exit;
}

// Handle removing item from GRN (AJAX)
if (isset($_POST['action']) && $_POST['action'] === 'remove_item') {
    header('Content-Type: application/json');
    
    if (!isset($_SESSION['current_grn'])) {
        echo json_encode(['status' => 'error', 'message' => 'No active GRN session']);
        exit;
    }
    
    $index = intval($_POST['index']);
    
    if (isset($_SESSION['current_grn']['items'][$index])) {
        array_splice($_SESSION['current_grn']['items'], $index, 1);
        echo json_encode(['status' => 'success', 'message' => 'Item removed']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Item not found']);
    }
    exit;
}

// Handle final processing (Update stock)
if (isset($_POST['action']) && $_POST['action'] === 'process_grn') {
    header('Content-Type: application/json');
    
    if (!isset($_SESSION['current_grn']) || empty($_SESSION['current_grn']['items'])) {
        echo json_encode(['status' => 'error', 'message' => 'No items to process']);
        exit;
    }
    
    $grn_id = $_SESSION['current_grn']['id'];
    $items = $_SESSION['current_grn']['items'];
    
    // Start transaction
    $db->begin_transaction();
    
    try {
        // Create grn_items table if not exists
        $db->query("CREATE TABLE IF NOT EXISTS grn_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            grn_id INT NOT NULL,
            product_id INT NOT NULL,
            quantity INT NOT NULL,
            cost DECIMAL(10,2) NOT NULL,
            price DECIMAL(10,2) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (grn_id) REFERENCES grn(id) ON DELETE CASCADE,
            FOREIGN KEY (product_id) REFERENCES products(id)
        )");
        
        foreach ($items as $item) {
            // Insert into grn_items
            $stmt = $db->prepare("INSERT INTO grn_items (grn_id, product_id, quantity, cost, price) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("iiidd", $grn_id, $item['product_id'], $item['quantity'], $item['new_cost'], $item['new_price']);
            $stmt->execute();
            $stmt->close();
            
            // Update product stock and cost/price
            $new_stock = $item['current_stock'] + $item['quantity'];
            $updateStmt = $db->prepare("UPDATE products SET stock = ?, cost = ?, price = ? WHERE id = ?");
            $updateStmt->bind_param("iddi", $new_stock, $item['new_cost'], $item['new_price'], $item['product_id']);
            $updateStmt->execute();
            $updateStmt->close();
        }
        
        $db->commit();
        
        // Clear session
        unset($_SESSION['current_grn']);
        
        echo json_encode([
            'status' => 'success',
            'message' => 'GRN processed successfully! Stock updated.',
            'redirect' => 'grn.php'
        ]);
        
    } catch (Exception $e) {
        $db->rollback();
        echo json_encode(['status' => 'error', 'message' => 'Failed to process GRN: ' . $e->getMessage()]);
    }
    exit;
}

// Get current GRN details
$current_grn = isset($_SESSION['current_grn']) ? $_SESSION['current_grn'] : null;
$step = isset($_GET['step']) ? intval($_GET['step']) : (isset($_SESSION['current_grn']) ? 2 : 1);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>POS System - Goods Receipt Note (GRN)</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="grn.css">
</head>
<body>
    <div class="dashboard-container">
        <div class="sidebar">
            <div class="logo">
                <h2>📦 POS System</h2>
            </div>
            <nav class="nav-menu">
    <a href="index.php" class="nav-item">📋 Products</a>
    <a href="add_product.php" class="nav-item">➕ Add Product</a>
    <a href="grn.php" class="nav-item active">📥 GRN</a>
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

        <div class="main-content">
            <div class="grn-container">
                <!-- Step Indicator -->
                <div class="step-indicator">
                    <div class="step <?php echo $step >= 1 ? ($step > 1 ? 'completed' : 'active') : ''; ?>">
                        Step 1: Create GRN
                    </div>
                    <div class="step <?php echo $step >= 2 ? 'active' : ''; ?>">
                        Step 2: Add Items
                    </div>
                    <div class="step <?php echo $step >= 3 ? 'active' : ''; ?>">
                        Step 3: Process
                    </div>
                </div>

                <?php if ($step == 1 || !$current_grn): ?>
                <!-- Step 1: Create GRN Form -->
                <div class="form-container">
                    <h2>Create New GRN</h2>
                    <?php if (isset($error)): ?>
                        <div class="message-area show message-error"><?php echo $error; ?></div>
                    <?php endif; ?>
                    <form method="POST" action="">
                        <div class="input-group">
                            <label>GRN Number</label>
                            <input type="text" value="<?php echo generateGRNNumber($db); ?>" disabled class="grn-number">
                        </div>
                        <div class="input-group">
                            <label>Date & Time</label>
                            <input type="text" value="<?php echo date('Y-m-d H:i:s'); ?>" disabled>
                        </div>
                        <div class="input-group">
                            <label>Supplier Name *</label>
                            <input type="text" name="supplier" required placeholder="Enter supplier name">
                        </div>
                        <button type="submit" name="create_grn" class="btn btn-primary">Create GRN & Continue →</button>
                    </form>
                </div>
                <?php else: ?>
                <!-- Step 2: Add Items -->
                <div class="grn-header">
                    <h2>GRN: <?php echo htmlspecialchars($current_grn['grn_no']); ?></h2>
                    <div class="grn-info">
                        <div><strong>Supplier:</strong> <?php echo htmlspecialchars($current_grn['supplier']); ?></div>
                        <div><strong>Date:</strong> <?php echo $current_grn['created_at']; ?></div>
                        <div><strong>Status:</strong> <span class="status-badge">In Progress</span></div>
                    </div>
                </div>

                <!-- Add Item Form -->
                <div class="add-item-form">
                    <h3>Add Product to GRN</h3>
					<p>Barcode or Product ID *</p>
                    <form id="addItemForm">
                        <div class="form-row">
                            <div class="form-group">
                                
                                <input type="text" id="search-product" placeholder="Scan barcode or enter product ID..." required>
                                <button type="button" class="btn-search" onclick="searchProduct()">Search</button>
                            </div>
                        </div>
                        
                        <div id="product-details" style="display:none;">
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Product Name</label>
                                    <input type="text" id="product-name" readonly>
                                </div>
                                <div class="form-group">
                                    <label>Barcode</label>
                                    <input type="text" id="product-barcode" readonly>
                                </div>
                                <div class="form-group">
                                    <label>Current Stock</label>
                                    <input type="text" id="current-stock" readonly>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Current Cost</label>
                                    <input type="text" id="current-cost" readonly>
                                </div>
                                <div class="form-group">
                                    <label>Current Price</label>
                                    <input type="text" id="current-price" readonly>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>New Cost *</label>
                                    <input type="number" id="new-cost" step="0.01" required>
                                </div>
                                <div class="form-group">
                                    <label>New Price *</label>
                                    <input type="number" id="new-price" step="0.01" required>
                                </div>
                                <div class="form-group">
                                    <label>Quantity *</label>
                                    <input type="number" id="quantity" step="1" min="1" required>
                                </div>
                            </div>
                            <button type="button" class="btn-add" onclick="addItemToGRN()">+ Add Item</button>
                        </div>
                    </form>
                </div>

               <!-- Items Table -->
<div class="items-table">
    <h3>GRN Items</h3>
    <table>
        <thead>
            <tr>
                <th>Product ID</th>
                <th>Barcode</th>
                <th>Product Name</th>
                <th>Current Stock</th>
                <th>New Cost</th>
                <th>New Price</th>
                <th>Quantity</th>
                <th>Total Cost</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody id="grn-items-body">
            <?php if (!empty($current_grn['items'])): ?>
                <?php 
                $grand_total = 0;
                foreach ($current_grn['items'] as $index => $item): 
                    $item_total = $item['new_cost'] * $item['quantity'];
                    $grand_total += $item_total;
                ?>
                <tr data-index="<?php echo $index; ?>" data-cost="<?php echo $item['new_cost']; ?>" data-quantity="<?php echo $item['quantity']; ?>">
                    <td><?php echo $item['product_id']; ?></td>
                    <td><?php echo htmlspecialchars($item['barcode']); ?></td>
                    <td><?php echo htmlspecialchars($item['name']); ?></td>
                    <td><?php echo $item['current_stock']; ?></td>
                    <td class="item-cost"><?php echo number_format($item['new_cost'], 2); ?></td>
                    <td><?php echo number_format($item['new_price'], 2); ?></td>
                    <td class="item-quantity"><?php echo $item['quantity']; ?></td>
                    <td class="item-total"><?php echo number_format($item_total, 2); ?></td>
                    <td>
                        <button class="btn-remove" onclick="removeItem(<?php echo $index; ?>)">Remove</button>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr id="no-items-row">
                    <td colspan="9" class="text-center">No items added yet</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Summary Section - Add this after the table -->
<div class="summary-section" id="summary-section" style="display: <?php echo empty($current_grn['items']) ? 'none' : 'block'; ?>;">
    <div class="summary-card">
        <h3>GRN Summary</h3>
        <div class="summary-details">
            <div class="summary-row">
                <span class="summary-label">Total Items:</span>
                <span class="summary-value" id="total-items"><?php echo count($current_grn['items']); ?></span>
            </div>
            <div class="summary-row">
                <span class="summary-label">Total Quantity:</span>
                <span class="summary-value" id="total-quantity">
                    <?php 
                    $total_qty = array_sum(array_column($current_grn['items'], 'quantity'));
                    echo $total_qty;
                    ?>
                </span>
            </div>
            <div class="summary-row total-amount">
                <span class="summary-label">Grand Total (Cost):</span>
                <span class="summary-value" id="grand-total">
                    <?php 
                    $grand_total = array_sum(array_map(function($item) {
                        return $item['new_cost'] * $item['quantity'];
                    }, $current_grn['items']));
                    echo number_format($grand_total, 2);
                    ?>
                </span>
            </div>
        </div>
    </div>
</div>
                <!-- Process Button -->
                <div class="process-section">
                    <button class="btn-process" onclick="processGRN()" id="process-btn" <?php echo empty($current_grn['items']) ? 'disabled' : ''; ?>>
                        ✅ Process GRN & Update Stock
                    </button>
					<button class="btn-process" onclick="cancelGRN()" id="cancel-btn">
                        ✅ Cencal GRN
                    </button>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div id="messageBox" class="message-area" style="position: fixed; bottom: 20px; right: 20px; z-index: 1000;"></div>

    <script src="grn.js"></script>
</body>
</html>