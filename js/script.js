/* =========================================
   1. SIDEBAR / HAMBURGER MENU LOGIC
   ========================================= */
document.addEventListener("DOMContentLoaded", () => {
    const adminHamburger = document.querySelector(".admin-hamburger");
    const indexHamburger = document.querySelector(".index-hamburger");
    const navLinks = document.querySelector(".nav-links");

    // Admin Sidebar Toggle
    if (adminHamburger && navLinks) {
        adminHamburger.addEventListener("click", () => {
            adminHamburger.classList.toggle("active");
            navLinks.classList.toggle("active");
        });

        document.querySelectorAll(".nav-links a").forEach(n => n.addEventListener("click", () => {
            adminHamburger.classList.remove("active");
            navLinks.classList.remove("active");
        }));
    }
    // Index Sidebar Toggle
    else if (indexHamburger && navLinks) {
        indexHamburger.addEventListener("click", () => {
            indexHamburger.classList.toggle("active");
            navLinks.classList.toggle("active");
        });

        document.querySelectorAll(".nav-links a").forEach(n => n.addEventListener("click", () => {
            indexHamburger.classList.remove("active");
            navLinks.classList.remove("active");
        }));
    }
});

/* =========================================
   2. GENERAL MODAL UTILITIES
   ========================================= */
function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'none';
    }
}

/* =========================================
   3. AUTHENTICATION MODAL (Login/Register)
   ========================================= */
function openAuthModal(type) {
    document.getElementById('auth-modal').style.display = 'flex';
    toggleAuth(type);
}

function toggleAuth(type) {
    const loginForm = document.getElementById('login-form');
    const registerForm = document.getElementById('register-form');

    if (type === 'login') {
        if (loginForm) loginForm.style.display = 'block';
        if (registerForm) registerForm.style.display = 'none';
    } else {
        if (loginForm) loginForm.style.display = 'none';
        if (registerForm) registerForm.style.display = 'block';
    }
}

/* =========================================
   4. ORDER MODAL
   ========================================= */
function openOrderModal(id, name) {
    const idInput = document.getElementById('modal-product-id');
    const nameDisplay = document.getElementById('modal-product-name');
    const orderModal = document.getElementById('order-modal');

    if (idInput) idInput.value = id;
    if (nameDisplay) nameDisplay.innerText = name;
    if (orderModal) orderModal.style.display = 'flex';
}

/* =========================================
   5. PRODUCT VIEW EXPAND MODAL
   ========================================= */
function viewProduct(element) {
    // 1. Get data from the clicked element's data attributes
    const id = element.getAttribute('data-id');
    const name = element.getAttribute('data-name');
    const price = element.getAttribute('data-price');
    const img = element.getAttribute('data-img');
    const desc = element.getAttribute('data-desc');

    // 2. Populate the Modal Elements
    document.getElementById('view-img').src = img;
    document.getElementById('view-name').innerText = name;
    document.getElementById('view-desc').innerText = desc;
    
    // Format price safely
    const parsedPrice = parseFloat(price);
    if (!isNaN(parsedPrice)) {
        document.getElementById('view-price').innerText = parsedPrice.toLocaleString('en-US', {minimumFractionDigits: 2});
    }

    // 3. Handle the "Order Now" button inside the modal
    const orderBtn = document.getElementById('view-order-btn');
    if (orderBtn) {
        // Remove old event listeners to prevent duplicate clicks
        const newBtn = orderBtn.cloneNode(true);
        orderBtn.parentNode.replaceChild(newBtn, orderBtn);
        
        // Add new click event that triggers the order modal
        newBtn.onclick = function() {
            closeProductView();
            openOrderModal(id, name);
        };
    }

    // 4. Show the Modal
    const viewModal = document.getElementById('product-view-modal');
    if (viewModal) viewModal.style.display = 'flex';
}

function closeProductView() {
    const viewModal = document.getElementById('product-view-modal');
    if (viewModal) viewModal.style.display = 'none';
}

/* =========================================
   6. ADMIN: EDIT PRODUCT MODAL
   ========================================= */
function openEditModal(id, name, desc, price) {
    const editModal = document.getElementById('edit-modal');
    if (editModal) editModal.style.display = "flex";
    
    // Populate the form fields
    const idInput = document.getElementById('edit-id');
    const nameInput = document.getElementById('edit-name');
    const descInput = document.getElementById('edit-desc');
    const priceInput = document.getElementById('edit-price');

    if (idInput) idInput.value = id;
    if (nameInput) nameInput.value = name;
    if (descInput) descInput.value = desc;
    if (priceInput) priceInput.value = price;
}

function closeEditModal() {
    const editModal = document.getElementById('edit-modal');
    if (editModal) editModal.style.display = "none";
}

/* =========================================
   7. UNIFIED "CLICK OUTSIDE TO CLOSE" EVENT
   ========================================= */
// This single event listener handles clicking the background overlay for ALL modals
window.onclick = function(event) {
    // Standard classes
    if (event.target.classList.contains('modal') || event.target.classList.contains('modal-overlay')) {
        event.target.style.display = "none";
    }
    
    // Specific IDs (fail-safe for modals lacking the shared class)
    const modals = [
        document.getElementById('order-modal'),
        document.getElementById('auth-modal'),
        document.getElementById('product-view-modal'),
        document.getElementById('edit-modal'),
        document.getElementById('messages-modal')
    ];

    modals.forEach(modal => {
        if (modal && event.target === modal) {
            modal.style.display = "none";
        }
    });
};