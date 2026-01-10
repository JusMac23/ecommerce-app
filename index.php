<?php

require 'functions/functions.php';

$currentUser = isset($_SESSION['user']) ? $_SESSION['user'] : null;
$error = "";
$success = "";
$action_type = ""; // To track which form was submitted

// Handle Logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit;
}

// Handle Customer Login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    $action_type = 'login';
    $email = $_POST['email']; 
    $password = $_POST['password'];
    
    $user = loginUser($email, $password);
    
    if ($user) {
        $_SESSION['user'] = $user;
        header("Location: index.php");
        exit;
    } else {
        $error = "Invalid email or password.";
    }
}

// Handle Registration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'register') {
    $action_type = 'register'; // Value set here is 'register'
    $fname = $_POST['firstname'];
    $mname = $_POST['middlename'];
    $lname = $_POST['lastname'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $pass  = $_POST['password'];

    $regResult = registerUser($fname, $mname, $lname, $email, $phone, $pass);
    
    if ($regResult === true) {
        $success = "Account created successfully! Please login.";
        $action_type = 'register_success'; // Special flag to switch to login view
    } else {
        $error = $regResult; 
    }
}

// Handle Place Order
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'place_order') {
    if (!$currentUser) {
        $error = "You must be logged in to place an order.";
        $action_type = 'login'; // Trigger login modal if they try to order without auth
    } else {
        addOrder($_POST['product_id'], $_POST['name'], $_POST['phone'], $_POST['address'], $currentUser['id']);
        header("Location: index.php?success=order_placed#my-orders");
        exit;
    }
}

// Handle Cancel Order
if (isset($_GET['cancel_order']) && $currentUser) {
    updateOrderStatus($_GET['cancel_order'], 'Cancelled', $currentUser['id']);
    header("Location: index.php#my-orders");
    exit;
}

if (isset($_GET['success']) && $_GET['success'] === 'order_placed') {
    $success = "Order placed successfully!";
}

$products = getProducts();
$orders = [];
if ($currentUser) {
    $orders = getUserOrders($currentUser['id']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Souvenir Shop</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/modal_product.css">
    <link rel="stylesheet" href="css/contact_us.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>

    <nav class="index-nav">
        <div class="logo">Souvenir Shop</div>
        
        <div class="index-hamburger">
            <span></span>
            <span></span>
            <span></span>
        </div>

        <div class="nav-links">
            <a href="#shop">Shop</a>
            <a href="#my-orders">My Orders</a>
            <a href="#contact">Contact</a>
            <?php if ($currentUser): ?>
                <span style="font-weight: bold; font-size:0.9em; margin-right:10px;">
                    Hi, <?= htmlspecialchars($currentUser['first_name']) ?>
                </span>
                <a href="?logout=true" class="admin-link">Logout</a>
            <?php else: ?>
                <a href="#" onclick="openAuthModal('login')" class="admin-link">Login</a>
            <?php endif; ?>
        </div>
    </nav>

    <div class="main-content">
        <div class="hero-section">
            <h1>Welcome to Our Store</h1>
            <p>Quality Souvenirs & Gifts.</p>
            
            <?php if ($error && $action_type !== 'login' && $action_type !== 'register'): ?>
                <div class="alert alert-error"><?= $error ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?= $success ?></div>
            <?php endif; ?>
        </div>

        <div id="shop" class="container">
        <h2><i class="fa fa-tags" style="margin-right: 10px; color: var(--primary);"></i> Our Products</h2>
            <div class="product-grid">
                <?php if (empty($products)): ?>
                    <p class="loading-text">No products available.</p>
                <?php else: ?>
                    <?php foreach ($products as $p): ?>
                    <div class="product-card">
                        <div class="image-wrapper" 
                            onclick="viewProduct(this)" 
                            style="cursor: pointer;"
                            data-id="<?= $p['id'] ?>"
                            data-name="<?= htmlspecialchars($p['name']) ?>"
                            data-price="<?= $p['price'] ?>"
                            data-img="<?= $p['img'] ?>"
                            data-desc="<?= isset($p['description']) ? htmlspecialchars($p['description']) : 'No description available.' ?>">
                            
                            <img src="<?= $p['img'] ?>" onerror="this.src='https://via.placeholder.com/300'" alt="<?= htmlspecialchars($p['name']) ?>">
                        </div>

                        <div class="card-details">
                            <h4 onclick="viewProduct(this.parentElement.previousElementSibling)" style="cursor: pointer;"><?= htmlspecialchars($p['name']) ?></h4>
                            
                            <span class="price">₱<?= number_format($p['price'], 2) ?></span>
                            
                            <?php if ($currentUser): ?>
                                <button class="btn-buy" onclick="openOrderModal(<?= $p['id'] ?>, '<?= addslashes($p['name']) ?>')">Order Now</button>
                            <?php else: ?>
                                <button class="btn-buy" onclick="openAuthModal('login')">Order Now</button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div id="product-view-modal" class="modal-overlay" style="display: none;">
                <div class="modal-content product-expand">
                    <span class="close-modal" onclick="closeProductView()">×</span>
                    
                    <div class="modal-body">
                        <div class="modal-img-col">
                            <img id="view-img" src="" alt="Product Image">
                        </div>

                        <div class="modal-info-col">
                            <h2 id="view-name">Product Name</h2>
                            <h3 class="price">₱<span id="view-price">0.00</span></h3>
                            <p id="view-desc">Product description goes here.</p>
                            
                            <div class="modal-actions">
                                <?php if ($currentUser): ?>
                                    <button id="view-order-btn" class="btn-buy">Order Now</button>
                                <?php else: ?>
                                    <button class="btn-buy" onclick="openAuthModal('login')">Login to Order</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div id="my-orders" class="container">
            <h2><i class="fa fa-shopping-cart" style="margin-right: 10px; color: var(--primary);"></i> My Orders</h2>
            <?php if (!$currentUser): ?>
                <div style="text-align: center; padding: 40px; background: #f9f9f9; border-radius: 8px;">
                    <p>Please <a href="#" onclick="openAuthModal('login')" class="auth-link">Login</a> to view your orders.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Product</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($orders)): ?>
                                <tr><td colspan="4" style="text-align:center;">No orders yet.</td></tr>
                            <?php else: ?>
                                <?php foreach ($orders as $o): ?>
                                <?php 
                                    $orderTime = strtotime($o['created_at']);
                                    $isPast24Hours = (time() - $orderTime) > 86400;
                                    $isPending = $o['status'] === 'Pending';
                                    $isCancelled = $o['status'] === 'Cancelled';
                                    $isCompleted = $o['status'] === 'Completed';
                                ?>
                                <tr>
                                    <td>#<?= $o['id'] ?></td>
                                    <td>
                                        <?= htmlspecialchars($o['product_name']) ?><br>
                                        <small style="color:#888"><?= date('M d, Y h:i A', $orderTime) ?></small>
                                    </td>
                                    <td><span class="status-badge status-<?= str_replace(' ', '-', $o['status']) ?>"><?= $o['status'] ?></span></td>
                                    <td>
                                        <?php if ($isCancelled): ?>
                                            <span style="color:#999;">-</span>

                                        <?php elseif ($isCompleted): ?>
                                            <button class="btn-disabled" disabled>Cancel</button>
                                            <span class="note-text">Thank you for ordering!</span>

                                        <?php elseif ($isPast24Hours): ?>
                                            <button class="btn-disabled" disabled>Cancel</button>
                                            <span class="note-text">Cannot cancel orders after 24hrs</span>

                                        <?php elseif (!$isPending): ?>
                                            <button class="btn-disabled" disabled>Cancel</button>
                                            <span class="note-text">Order processing</span>

                                        <?php else: ?>
                                            <a href="?cancel_order=<?= $o['id'] ?>"
                                            class="btn-danger"
                                            style="text-decoration:none; padding:5px 10px; font-size:0.8em;"
                                            onclick="return confirm('Cancel this order?')">
                                            Cancel
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <div id="contact" class="container contact-section">
            <h2>Contact Us</h2>
            
            <div class="contact-info-header">
                <span><strong><i class="fa fa-phone" style="color:#0d9488;"></i> Phone:</strong> <?= defined('ADMIN_PHONE') ? ADMIN_PHONE : 'N/A' ?></span> | 
                <span><strong><i class="fa fa-envelope" style="color:#0d9488;"></i> Email:</strong> support@souvenirshop.com</span> | 
                <span><strong><i class="fa fa-map-marker" style="color:#0d9488;"></i> Location:</strong> Gracepark, Caloocan City</span>
            </div>

            <div class="contact-container-row">
                
                <div class="map-left">
                    <iframe 
                        src="https://maps.google.com/maps?q=Gracepark%20Caloocan&t=&z=13&ie=UTF8&iwloc=&output=embed" 
                        allowfullscreen 
                        loading="lazy">
                    </iframe>
                </div>

                <div class="form-right">
                    <h3>Send a Message</h3>
                    <form action="send_message.php" method="POST">
                        <div class="form-group">
                            <label>Full Name</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Message</label>
                            <textarea name="message" rows="5" class="form-control" required></textarea>
                        </div>
                        <button type="submit" class="btn-submit">Submit</button>
                    </form>
                </div>

            </div>
        </div>
    </div>

    <div id="order-modal" class="modal" style="display: none;">
        <div class="modal-content">
            <span class="close-btn" onclick="closeModal('order-modal')">×</span>
            <h3 style="margin-bottom:10px; text-align:center;">Place Order</h3>
            <span style="margin-bottom:15px; display:block;">
                Item:
                <span id="modal-product-name" class="highlight-text"></span>
            </span>
            <form method="POST" action="index.php">
                <input type="hidden" name="action" value="place_order">
                <input type="hidden" id="modal-product-id" name="product_id">
                
                <label>Your Name</label>
                <input type="text" name="name" value="<?= $currentUser ? htmlspecialchars($currentUser['first_name'] . ' ' . $currentUser['last_name']) : '' ?>" readonly style="background:#f0f0f0;">
                
                <label>Phone Number</label>
                <input type="text" name="phone" value="<?= $currentUser ? htmlspecialchars($currentUser['phone']) : '' ?>" readonly style="background:#f0f0f0;">
                
                <label>Delivery Address</label>
                <input type="text" name="address" placeholder="Enter complete address" required>
                
                <button type="submit" class="btn-confirm" style="width:100%;">Confirm Order</button>
            </form>
        </div>
    </div>

    <div id="auth-modal" class="modal" style="display: none;">
        <div class="modal-content" style="max-width: 400px;">
            <span class="close-btn" onclick="closeModal('auth-modal')">×</span>
            
            <?php if ($error && ($action_type === 'login' || $action_type === 'register')): ?>
                <div class="alert alert-error" style="margin-bottom:15px;"><?= $error ?></div>
            <?php endif; ?>

            <div id="login-form">
                <h3 style="text-align:center; margin-bottom:5px;">Customer Sign In</h3>
                <form method="POST" action="index.php">
                    <input type="hidden" name="action" value="login">
                    
                    <label>Email Address</label>
                    <input type="email" name="email" required>
                    
                    <label>Password</label>
                    <input type="password" name="password" required>
                    
                    <button type="submit" class="btn-confirm" style="width:100%; margin-bottom:10px;"></i>Login</button>
                </form>

                <a href="admin.php" class="btn-block btn-admin-link" style="display: block; text-align: center; color: var(--primary); font-weight: 600;">Go to Admin Login</a>
                <p style="margin-top:15px; text-align:center;">No account? <span class="auth-link" onclick="toggleAuth('register')">Register here</span></p>
            </div>

            <div id="register-form" style="display: none;">
                <h3 style="text-align:center; margin-bottom:5px;">Create Customer Account</h3>
                <form method="POST" action="index.php">
                    <input type="hidden" name="action" value="register">
                    
                    <label>First Name</label>
                    <input type="text" name="firstname" placeholder="John" style="font-family: Google Sans, sans-serif;" required>
                    
                    <label>Middle Initial (Optional)</label>
                    <input type="text" name="middlename" placeholder="J" maxlength="1" style="font-family: Google Sans, sans-serif;">
                    
                    <label>Last Name</label>
                    <input type="text" name="lastname" placeholder="Doe" style="font-family: Google Sans, sans-serif;" required>
                    
                    <label>Email Address</label>
                    <input type="email" name="email" placeholder="johndoe@gmail.com" style="font-family: Google Sans, sans-serif;" required>
                    
                    <label>Phone Number</label>
                    <input type="text" name="phone" placeholder="09123456789" style="font-family: Google Sans, sans-serif;" required>
                    
                    <label>Password</label>
                    <input type="password" name="password" required>
                    
                    <button type="submit" class="btn-confirm" style="width:100%; font-family: Google Sans, sans-serif;">Sign Up</button>
                </form>
                <p style="margin-top:15px; text-align:center;">Have account? <span class="auth-link" onclick="toggleAuth('login')">Login here</span></p>
            </div>
        </div>
    </div>

    <script>
        // --- PHP INTERACTION: Auto-open modal on error ---
        <?php if ($action_type === 'login'): ?>
            // Login failed, reopen login modal
            openAuthModal('login');
        <?php elseif ($action_type === 'register'): ?>
            // Registration failed (e.g., email taken), reopen register modal
            openAuthModal('register');
        <?php elseif ($action_type === 'register_success'): ?>
            // Registration success, open login modal
            openAuthModal('login');
        <?php endif; ?>
    </script>
    
    <script src="js/sidebar.js"></script>
    <script src="js/modal.js"></script>
    <script src="js/modal_product.js"></script>
</body>
</html>