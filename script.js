// Open Popup with Product Data
function openOrderModal(id, name) {
    document.getElementById('modal-product-id').value = id;
    document.getElementById('modal-product-name').innerText = name;
    document.getElementById('order-modal').style.display = "block";
}

// Close Popup
function closeModal() {
    document.getElementById('order-modal').style.display = "none";
}

// Close modal if clicking outside content
window.onclick = function(event) {
    const modal = document.getElementById('order-modal');
    if (event.target == modal) {
        modal.style.display = "none";
    }
}