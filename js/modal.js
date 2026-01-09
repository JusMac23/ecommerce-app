function openOrderModal(id, name) {
    document.getElementById('modal-product-id').value = id;
    document.getElementById('modal-product-name').innerText = name;
    document.getElementById('order-modal').style.display = 'flex';
}

function openAuthModal(type) {
    document.getElementById('auth-modal').style.display = 'flex';
    toggleAuth(type);
}

function toggleAuth(type) {
    const loginForm = document.getElementById('login-form');
    const registerForm = document.getElementById('register-form');

    if(type === 'login') {
        loginForm.style.display = 'block';
        registerForm.style.display = 'none';
    } else {
        loginForm.style.display = 'none';
        registerForm.style.display = 'block';
    }
}

function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

// Close modal if clicking outside the content area
window.onclick = function(event) {
    if (event.target.className === 'modal') {
        event.target.style.display = "none";
    }
}