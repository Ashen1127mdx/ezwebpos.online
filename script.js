// Global variables
let currentDeleteId = null;
let currentDeleteName = null;

// Load products
async function loadProducts() {
    const search = document.getElementById('searchInput')?.value || '';
    const category = document.getElementById('categoryFilter')?.value || '';
    
    console.log('=== LOADING PRODUCTS ==='); // Debug
    console.log('Search term:', search);
    console.log('Category ID:', category);
    
    const url = `get_products.php?search=${encodeURIComponent(search)}&category_id=${category}`;
    console.log('Request URL:', url);
    
    try {
        const response = await fetch(url);
        console.log('Response status:', response.status);
        
        const result = await response.json();
        console.log('API Response:', result);
        
        if (result.status === 'success') {
            console.log('Products loaded:', result.data.length);
            renderProductsTable(result.data);
        } else {
            console.error('API Error:', result.message);
            showMessage('Failed to load products: ' + result.message, 'error');
        }
    } catch (error) {
        console.error('Error loading products:', error);
        showMessage('Network error loading products', 'error');
    }
}

// Render products table
function renderProductsTable(products) {
    const tbody = document.getElementById('productsTableBody');
    if (!tbody) return;
    
    if (products.length === 0) {
        tbody.innerHTML = '<tr><td colspan="9" class="loading">No products found</td></tr>';
        return;
    }
    
    tbody.innerHTML = products.map(product => {
        // Escape the product name for JavaScript
        const escapedName = escapeHtml(product.name).replace(/'/g, "\\'").replace(/"/g, '&quot;');
        
        return `
        <tr>
            <td>${product.id}</td>
            <td><strong>${escapeHtml(product.name)}</strong></td>
            <td>${escapeHtml(product.barcode)}</td>
            <td>Rs: ${parseFloat(product.price).toFixed(2)}</td>
            <td>Rs: ${parseFloat(product.cost).toFixed(2)}</td>
            <td class="${product.stock < 10 ? 'low-stock' : ''}">${product.stock}</td>
            <td>${escapeHtml(product.category_name || 'Uncategorized')}</td>
            <td>${formatDate(product.created_at)}</td>
            <td>
                <div class="action-buttons-table">
                    <a href="edit_product.php?id=${product.id}" class="btn-edit">✏️ Edit</a>
                    <button class="btn-delete" onclick="deleteProductPrompt(${product.id}, '${escapedName}')">🗑️ Delete</button>
                </div>
            </td>
        </tr>
    `}).join('');
    
    console.log('Products loaded:', products.length); // Debug log
}



// Load categories for the add product form dropdown
async function loadCategoriesForSelect() {
    try {
        const response = await fetch('get_categories.php');
        const result = await response.json();
        
        if (result.status === 'success') {
            const categories = result.data;
            
            const categorySelect = document.getElementById('category_id');
            if (categorySelect) {
                if (categories.length > 0) {
                    categorySelect.innerHTML = '<option value="">-- Select Category --</option>' +
                        categories.map(cat => `<option value="${cat.id}">${escapeHtml(cat.name)}</option>`).join('');
                } else {
                    categorySelect.innerHTML = '<option value="">-- No categories available --</option>';
                    showMessage('Please add categories first', 'warning');
                }
            }
        } else {
            console.error('Failed to load categories:', result.message);
            const categorySelect = document.getElementById('category_id');
            if (categorySelect) {
                categorySelect.innerHTML = '<option value="">-- Error loading categories --</option>';
            }
        }
    } catch (error) {
        console.error('Error loading categories:', error);
        showMessage('Failed to load categories', 'error');
        const categorySelect = document.getElementById('category_id');
        if (categorySelect) {
            categorySelect.innerHTML = '<option value="">-- Error loading categories --</option>';
        }
    }
}
// Delete product prompt
function deleteProductPrompt(id, name) {
    console.log('Delete prompt called for ID:', id, 'Name:', name); // Debug log
    
    currentDeleteId = id;
    currentDeleteName = name;
    
    const modal = document.getElementById('deleteModal');
    const productNameSpan = document.getElementById('deleteProductName');
    
    if (modal && productNameSpan) {
        productNameSpan.textContent = name;
        modal.style.display = 'flex'; // Use style.display instead of classList
        console.log('Modal should be visible now'); // Debug log
    } else {
        console.error('Modal or product name span not found');
    }
}

// Confirm delete
function confirmDelete() {
    console.log('Confirm delete called. Current ID:', currentDeleteId); // Debug log
    
    if (currentDeleteId) {
        deleteProduct(currentDeleteId);
    } else {
        console.error('No product ID to delete');
        showMessage('No product selected for deletion', 'error');
    }
}

// Delete product
async function deleteProduct(id) {
    console.log('Delete function called for ID:', id); // Debug log
    
    try {
        showMessage('Deleting product...', 'info');
        
        const response = await fetch('delete_product.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `id=${id}`
        });
        
        console.log('Delete response status:', response.status); // Debug log
        
        const result = await response.json();
        console.log('Delete response data:', result); // Debug log
        
        if (result.status === 'success') {
            showMessage(result.message, 'success');
            closeModal();
            loadProducts(); // Refresh the table
        } else {
            showMessage(result.message, 'error');
        }
    } catch (error) {
        console.error('Error deleting product:', error);
        showMessage('Network error deleting product: ' + error.message, 'error');
    }
}

// Close modal
function closeModal() {
    console.log('Closing modal'); // Debug log
    
    const modal = document.getElementById('deleteModal');
    if (modal) {
        modal.style.display = 'none';
    }
    currentDeleteId = null;
    currentDeleteName = null;
}

// Helper functions
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function formatDate(dateString) {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleDateString() + ' ' + date.toLocaleTimeString();
}

function showMessage(message, type = 'info') {
    const messageBox = document.getElementById('messageBox');
    if (!messageBox) return;
    
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

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('deleteModal');
    if (event.target === modal) {
        closeModal();
    }
};

// Click to expand dropdown
document.addEventListener('DOMContentLoaded', function() {
    const dropdown = document.querySelector('.dropdown');
    const dropdownToggle = document.querySelector('.dropdown-toggle');
    const dropdownMenu = document.querySelector('.dropdown-menu');
    
    if (dropdownToggle && dropdownMenu) {
        dropdownToggle.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            dropdownMenu.classList.toggle('show');
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!dropdown.contains(e.target)) {
                dropdownMenu.classList.remove('show');
            }
        });
        
        // Close dropdown when clicking on a dropdown item
        const dropdownItems = document.querySelectorAll('.dropdown-item');
        dropdownItems.forEach(item => {
            item.addEventListener('click', function() {
                dropdownMenu.classList.remove('show');
            });
        });
    }
});

// Make ALL functions global
window.deleteProductPrompt = deleteProductPrompt;
window.confirmDelete = confirmDelete;
window.closeModal = closeModal;
window.deleteProduct = deleteProduct;
window.loadProducts = loadProducts;
window.loadCategories = loadCategories;

console.log('Script loaded, functions are global'); // Debug log