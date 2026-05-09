let currentProduct = null;

// Add this function to calculate and update totals
function updateTotals() {
    const rows = document.querySelectorAll('#grn-items-body tr');
    let totalItems = 0;
    let totalQuantity = 0;
    let grandTotal = 0;
    
    rows.forEach(row => {
        if (row.id !== 'no-items-row') {
            totalItems++;
            
            // Get quantity
            const quantityCell = row.cells[6];
            const quantity = parseFloat(quantityCell.textContent);
            totalQuantity += quantity;
            
            // Get total cost
            const totalCell = row.cells[7];
            const total = parseFloat(totalCell.textContent);
            grandTotal += total;
        }
    });
    
 // Update summary display
    document.getElementById('total-items').textContent = totalItems;
    document.getElementById('total-quantity').textContent = totalQuantity;
    document.getElementById('grand-total').textContent = grandTotal.toFixed(2);
    
    // Show/hide summary section
    const summarySection = document.getElementById('summary-section');
    if (summarySection) {
        if (totalItems > 0) {
            summarySection.style.display = 'block';
        } else {
            summarySection.style.display = 'none';
        }
    }
}


// Search product by barcode or ID
async function searchProduct() {
    const searchValue = document.getElementById('search-product').value.trim();
    
    if (!searchValue) {
        showMessage('Please enter barcode or product ID', 'error');
        return;
    }
    
    try {
        const response = await fetch(`get_product_details.php?search=${encodeURIComponent(searchValue)}`);
        const result = await response.json();
        
        if (result.status === 'success') {
            currentProduct = result.data;
            displayProductDetails(currentProduct);
            showMessage('Product found!', 'success');
        } else {
            showMessage(result.message, 'error');
            document.getElementById('product-details').style.display = 'none';
            currentProduct = null;
        }
    } catch (error) {
        console.error('Error:', error);
        showMessage('Network error searching product', 'error');
    }
}

// Display product details in form
function displayProductDetails(product) {
    document.getElementById('product-name').value = product.name;
    document.getElementById('product-barcode').value = product.barcode;
    document.getElementById('current-stock').value = product.stock;
    document.getElementById('current-cost').value = parseFloat(product.cost).toFixed(2);
    document.getElementById('current-price').value = parseFloat(product.price).toFixed(2);
    document.getElementById('new-cost').value = product.cost;
    document.getElementById('new-price').value = product.price;
    document.getElementById('quantity').value = 1;
    
    document.getElementById('product-details').style.display = 'block';
}

// Add item to GRN
async function addItemToGRN() {
    if (!currentProduct) {
        showMessage('Please search for a product first', 'error');
        return;
    }
    
    const cost = parseFloat(document.getElementById('new-cost').value);
    const price = parseFloat(document.getElementById('new-price').value);
    const quantity = parseInt(document.getElementById('quantity').value);
    
    if (!quantity || quantity <= 0) {
        showMessage('Please enter valid quantity', 'error');
        return;
    }
    
    if (!cost || cost <= 0) {
        showMessage('Please enter valid cost', 'error');
        return;
    }
    
    if (!price || price <= 0) {
        showMessage('Please enter valid price', 'error');
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'add_item');
    formData.append('search', document.getElementById('search-product').value.trim());
    formData.append('cost', cost);
    formData.append('price', price);
    formData.append('quantity', quantity);
    
    try {
        const response = await fetch('grn.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.status === 'success') {
            showMessage(result.message, 'success');
            addItemToTable(result.item);
            resetProductForm();
        } else {
            showMessage(result.message, 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showMessage('Network error adding item', 'error');
    }
}

// Update the addItemToTable function
function addItemToTable(item) {
    const tbody = document.getElementById('grn-items-body');
    const noItemsRow = document.getElementById('no-items-row');
    
    if (noItemsRow) {
        noItemsRow.remove();
    }
    
    const row = tbody.insertRow();
    const index = tbody.children.length - 1;
    const itemTotal = item.new_cost * item.quantity;
    
    row.setAttribute('data-index', index);
    row.setAttribute('data-cost', item.new_cost);
    row.setAttribute('data-quantity', item.quantity);
    row.innerHTML = `
        <td>${item.product_id}</td>
        <td>${escapeHtml(item.barcode)}</td>
        <td>${escapeHtml(item.name)}</td>
        <td>${item.current_stock}</td>
        <td class="item-cost">${parseFloat(item.new_cost).toFixed(2)}</td>
        <td>${parseFloat(item.new_price).toFixed(2)}</td>
        <td class="item-quantity">${item.quantity}</td>
        <td class="item-total">${itemTotal.toFixed(2)}</td>
        <td>
            <button class="btn-remove" onclick="removeItem(${index})">Remove</button>
        </td>
    `;
    
    document.getElementById('process-btn').disabled = false;
    
    // Update totals after adding item
    updateTotals();
}


// Update the removeItem function
async function removeItem(index) {
    if (!confirm('Are you sure you want to remove this item?')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'remove_item');
    formData.append('index', index);
    
    try {
        const response = await fetch('grn.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.status === 'success') {
            showMessage('Item removed', 'success');
            
            // Remove the row from table
            const row = document.querySelector(`tr[data-index="${index}"]`);
            if (row) {
                row.remove();
            }
            
            // Check if table is empty
            const tbody = document.getElementById('grn-items-body');
            if (tbody.children.length === 0) {
                tbody.innerHTML = '<tr id="no-items-row"><td colspan="9" class="text-center">No items added yet</td></tr>';
                document.getElementById('process-btn').disabled = true;
            }
            
            // Update totals after removal
            updateTotals();
        } else {
            showMessage(result.message, 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showMessage('Network error removing item', 'error');
    }
}


// Add this to initialize totals when page loads
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('search-product');
    if (searchInput) {
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                searchProduct();
            }
        });
    }
    
    // Initialize totals if there are existing items
    updateTotals();
});

// Process GRN and update stock
async function processGRN() {
    if (!confirm('Are you sure you want to process this GRN?\n\nThis will:\n- Update product stock levels\n- Update product costs and prices\n- Cannot be undone!\n\nProceed?')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'process_grn');
    
    try {
        const response = await fetch('grn.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.status === 'success') {
            showMessage(result.message, 'success');
            setTimeout(() => {
                window.location.href = 'grn.php';
            }, 2000);
        } else {
            showMessage(result.message, 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showMessage('Network error processing GRN', 'error');
    }
}

function cancelGRN() {
    if (confirm('Are you sure you want to cancel this GRN? All unsaved items will be lost.')) {
        // Clear the GRN session
        fetch('clear_grn_session.php', {
            method: 'POST'
        }).then(() => {
            window.location.href = 'grn.php';
        }).catch(() => {
            window.location.href = 'grn.php';
        });
    }
}
// Reset product form
function resetProductForm() {
    document.getElementById('search-product').value = '';
    document.getElementById('product-details').style.display = 'none';
    document.getElementById('search-product').focus();
    currentProduct = null;
}

// Allow Enter key to search
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('search-product');
    if (searchInput) {
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                searchProduct();
            }
        });
    }
});

// Helper function to show messages
function showMessage(message, type) {
    const messageBox = document.getElementById('messageBox');
    const messageDiv = document.createElement('div');
    messageDiv.className = `message-area show message-${type}`;
    messageDiv.textContent = message;
    
    messageBox.innerHTML = '';
    messageBox.appendChild(messageDiv);
    
    setTimeout(() => {
        if (messageDiv.parentNode === messageBox) {
            messageDiv.classList.remove('show');
            setTimeout(() => {
                if (messageDiv.parentNode === messageBox) {
                    messageBox.innerHTML = '';
                }
            }, 300);
        }
    }, 5000);
}

// Helper function to escape HTML
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}