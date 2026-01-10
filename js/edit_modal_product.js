function openEditModal(id, name, desc, price) {
    document.getElementById('edit-modal').style.display = "block";
    
    // Populate the form fields
    document.getElementById('edit-id').value = id;
    document.getElementById('edit-name').value = name;
    document.getElementById('edit-desc').value = desc;
    document.getElementById('edit-price').value = price;
}

function closeEditModal() {
    document.getElementById('edit-modal').style.display = "none";
}

// Close modal if user clicks outside of it
window.onclick = function(event) {
    var modal = document.getElementById('edit-modal');
    if (event.target == modal) {
        modal.style.display = "none";
    }
}