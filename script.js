// ============================================
// 1. CONFIGURATION
// ============================================
// Enter your phone number (Country Code + Number). No + sign.
// Example: 15550000000 (USA), 639123456789 (Philippines)
const ADMIN_PHONE = "1234567890"; 

// Password to access admin.html
const ADMIN_PASSWORD = "admin123"; 

// ============================================
// 2. STATE & DATA LOADING
// ============================================
// Default products if store is empty
const defaultProducts = [
    { id: 1, name: "White Sneakers", price: 49.99, img: "https://images.unsplash.com/photo-1549298916-b41d501d3772?w=300" },
    { id: 2, name: "Leather Bag", price: 89.50, img: "https://images.unsplash.com/photo-1553062407-98eeb64c6a62?w=300" }
];

// Load from LocalStorage
let products = JSON.parse(localStorage.getItem('products')) || defaultProducts;
let orders = JSON.parse(localStorage.getItem('orders')) || [];
let currentProductId = null;

// ============================================
// 3. PAGE INITIALIZATION
// ============================================
window.onload = function() {
    
    // --- CUSTOMER PAGE (index.html) ---
    if (document.getElementById('product-grid')) {
        renderCustomerProducts();
        renderCustomerOrders();
        // Show contact number on homepage
        document.getElementById('display-phone').innerText = "+" + ADMIN_PHONE;
    } 
    
    // --- ADMIN PAGE (admin.html) ---
    else if (document.getElementById('admin-view')) {
        // Check if Admin is already logged in for this session
        if (sessionStorage.getItem('isAdminLoggedIn') === 'true') {
            showAdminDashboard();
        }
    }
};

// ============================================
// 4. ADMIN LOGIN LOGIC
// ============================================
function checkLogin() {
    const input = document.getElementById('admin-password').value;
    const errorMsg = document.getElementById('login-error');

    if (input === ADMIN_PASSWORD) {
        sessionStorage.setItem('isAdminLoggedIn', 'true'); // Save session
        showAdminDashboard();
    } else {
        errorMsg.style.display = 'block';
    }
}

function showAdminDashboard() {
    document.getElementById('login-view').style.display = 'none';
    document.getElementById('admin-view').style.display = 'block';
    renderAdminProducts();
    renderAllOrders();
}

function logout() {
    sessionStorage.removeItem('isAdminLoggedIn');
    location.reload(); // Refresh to show login screen
}

// ============================================
// 5. CUSTOMER FUNCTIONALITY
// ============================================

// Display Products
function renderCustomerProducts() {
    const grid = document.getElementById('product-grid');
    grid.innerHTML = "";
    
    if(products.length === 0) {
        grid.innerHTML = "<p>No products available at the moment.</p>";
        return;
    }

    products.forEach(p => {
        grid.innerHTML += `
            <div class="product-card">
                <img src="${p.img}" onerror="this.src='https://via.placeholder.com/300'">
                <div class="card-details">
                    <h4>${p.name}</h4>
                    <span class="price">$${p.price}</span>
                    <button class="btn-buy" onclick="openOrderModal(${p.id})">Order Now</button>
                </div>
            </div>
        `;
    });
}

// Open Popup
function openOrderModal(id) {
    currentProductId = id;
    const p = products.find(x => x.id === id);
    if(p) {
        document.getElementById('modal-product-name').innerText = p.name;
        document.getElementById('order-modal').style.display = "block";
    }
}

// Close Popup
function closeModal() {
    document.getElementById('order-modal').style.display = "none";
}

// Confirm Order & WhatsApp
function confirmOrder() {
    const name = document.getElementById('customer-name').value;
    const phone = document.getElementById('customer-phone').value;
    const address = document.getElementById('customer-address').value;
    const product = products.find(p => p.id === currentProductId);

    if (name && phone && address) {
        // 1. Create Order
        const newOrder = {
            id: Date.now(),
            productName: product.name,
            customerName: name,
            contact: phone,
            address: address,
            status: "Pending"
        };
        
        // 2. Save
        orders.push(newOrder);
        localStorage.setItem('orders', JSON.stringify(orders));

        // 3. WhatsApp Redirect
        const waMsg = `Hello, I want to order:%0a*Product:* ${product.name}%0a*Name:* ${name}%0a*Address:* ${address}%0a*Order ID:* ${newOrder.id}`;
        window.open(`https://wa.me/${ADMIN_PHONE}?text=${waMsg}`, '_blank');
        
        // 4. Cleanup
        closeModal();
        renderCustomerOrders();
        document.getElementById('customer-name').value = "";
        document.getElementById('customer-phone').value = "";
        document.getElementById('customer-address').value = "";
    } else {
        alert("Please fill in all fields.");
    }
}

// Show Orders
function renderCustomerOrders() {
    const tbody = document.getElementById('customer-order-list');
    tbody.innerHTML = "";
    const myOrders = orders.slice().reverse(); // Newest first

    if (myOrders.length === 0) {
        tbody.innerHTML = "<tr><td colspan='4'>No orders found.</td></tr>";
        return;
    }

    myOrders.forEach(o => {
        // Allow cancel only if Pending
        const action = o.status === 'Pending' 
            ? `<button class="btn-danger" onclick="cancelOrder(${o.id})">Cancel</button>` 
            : '<span style="color:#aaa">-</span>';

        tbody.innerHTML += `
            <tr>
                <td>#${o.id}</td>
                <td>${o.productName}</td>
                <td><span class="status-badge status-${o.status}">${o.status}</span></td>
                <td>${action}</td>
            </tr>
        `;
    });
}

// Cancel Order
function cancelOrder(id) {
    if(confirm("Are you sure you want to cancel this order?")) {
        const index = orders.findIndex(o => o.id === id);
        if (index > -1) {
            orders[index].status = "Cancelled";
            localStorage.setItem('orders', JSON.stringify(orders));
            renderCustomerOrders();
        }
    }
}

// ============================================
// 6. ADMIN FUNCTIONALITY
// ============================================

// Add Product
function addProduct() {
    const name = document.getElementById('p-name').value;
    const price = document.getElementById('p-price').value;
    const img = document.getElementById('p-img').value;

    if (name && price) {
        products.push({ 
            id: Date.now(), 
            name, 
            price, 
            img: img || "https://via.placeholder.com/300" 
        });
        localStorage.setItem('products', JSON.stringify(products));
        alert("Product Added Successfully!");
        renderAdminProducts();
        // Clear inputs
        document.getElementById('p-name').value = "";
        document.getElementById('p-price').value = "";
    } else {
        alert("Please enter Name and Price.");
    }
}

// Show Products in Admin
function renderAdminProducts() {
    const tbody = document.getElementById('admin-product-list');
    tbody.innerHTML = "";
    products.forEach(p => {
        tbody.innerHTML += `
            <tr>
                <td><img src="${p.img}" width="50" style="border-radius:4px;"></td>
                <td>${p.name}</td>
                <td>$${p.price}</td>
                <td><button class="btn-danger" onclick="deleteProduct(${p.id})">Delete</button></td>
            </tr>
        `;
    });
}

// Delete Product
function deleteProduct(id) {
    if(confirm("Delete this product permanently?")) {
        products = products.filter(p => p.id !== id);
        localStorage.setItem('products', JSON.stringify(products));
        renderAdminProducts();
    }
}

// Show All Orders
function renderAllOrders() {
    const tbody = document.getElementById('admin-order-list');
    tbody.innerHTML = "";
    const allOrders = orders.slice().reverse();

    if(allOrders.length === 0) {
        tbody.innerHTML = "<tr><td colspan='4'>No orders received yet.</td></tr>";
        return;
    }

    allOrders.forEach(o => {
        tbody.innerHTML += `
            <tr>
                <td>#${o.id}</td>
                <td>
                    <strong>${o.customerName}</strong><br>
                    <small>${o.contact}</small><br>
                    <small>${o.address}</small>
                </td>
                <td>${o.productName}</td>
                <td><span class="status-badge status-${o.status}">${o.status}</span></td>
            </tr>
        `;
    });
}