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
    document.getElementById('view-price').innerText = parseFloat(price).toLocaleString('en-US', {minimumFractionDigits: 2});
    document.getElementById('view-desc').innerText = desc;

    // 3. Handle the "Order Now" button inside the modal
    const orderBtn = document.getElementById('view-order-btn');
    if (orderBtn) {
        // Remove old event listeners to prevent duplicates
        const newBtn = orderBtn.cloneNode(true);
        orderBtn.parentNode.replaceChild(newBtn, orderBtn);
        
        // Add new click event that calls your existing order function
        newBtn.onclick = function() {
            // Close the view modal first
            closeProductView();
            // Open your existing order modal
            openOrderModal(id, name);
        };
    }

    // 4. Show the Modal
    document.getElementById('product-view-modal').style.display = 'flex';
}

function closeProductView() {
    document.getElementById('product-view-modal').style.display = 'none';
}

// Close modal when clicking outside the white box
window.onclick = function(event) {
    const modal = document.getElementById('product-view-modal');
    if (event.target == modal) {
        closeProductView();
    }
}