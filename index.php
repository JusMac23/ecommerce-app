<?php
require 'functions.php';

// Handle Order Submission
$waLink = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'place_order') {
    $order = addOrder($_POST['product_id'], $_POST['name'], $_POST['phone'], $_POST['address']);
    
    // Generate WhatsApp Link
    // Note: $order comes from addOrder() which manually returns 'productName', so this keys remains valid here.
    $msg = "Hello, I want to order:%0a*Product:* " . urlencode($order['productName']) . 
           "%0a*Name:* " . urlencode($order['customerName']) . 
           "%0a*Address:* " . urlencode($order['address']) . 
           "%0a*Order ID:* " . $order['id'];
    $waLink = "https://wa.me/" . ADMIN_PHONE . "?text=" . $msg;
    
    // Use JS to open WhatsApp automatically
    echo "<script>window.open('$waLink', '_blank'); window.location.href='index.php';</script>";
    exit;
}

// Handle Cancel Order (Simple implementation for user)
if (isset($_GET['cancel_order'])) {
    updateOrderStatus($_GET['cancel_order'], 'Cancelled');
    header("Location: index.php#my-orders");
    exit;
}

$products = getProducts();
$orders = getOrders();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Erwin Souvenir Shop</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <nav>
        <div class="logo">Erwin Souvenir Shop</div>
        <div class="nav-links">
            <a href="#shop">Shop</a>
            <a href="#my-orders">Orders</a>
            <a href="#contact">Contact</a>
            <?php if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true): ?>
                <a href="admin.php" class="admin-link">Manage Store</a>
            <?php else: ?>
                <a href="admin.php" class="admin-link">Login</a>
            <?php endif; ?>
        </div>
    </nav>

    <div class="main-content">
        
        <div class="hero-section">
            <h1>Welcome to Our Store</h1>
            <p>Browse our products below. Click "Order Now" to purchase via WhatsApp.</p>
        </div>

        <div id="shop" class="container">
            <h2>Our Products</h2>
            <div class="product-grid">
                <?php if (empty($products)): ?>
                    <p class="loading-text">No products available.</p>
                <?php else: ?>
                    <?php foreach ($products as $p): ?>
                    <div class="product-card">
                        <img src="<?= $p['img'] ?>" onerror="this.src='https://via.placeholder.com/300'">
                        <div class="card-details">
                            <h4><?= $p['name'] ?></h4>
                            <span class="price">₱<?= number_format($p['price'], 2) ?></span>
                            <button class="btn-buy" onclick="openOrderModal(<?= $p['id'] ?>, '<?= addslashes($p['name']) ?>')">Order Now</button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div id="my-orders" class="container">
            <h2>Order Status</h2>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Product</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($orders)): ?>
                            <tr><td colspan="4">No recent orders.</td></tr>
                        <?php else: ?>
                            <?php foreach ($orders as $o): ?>
                            <tr>
                                <td>#<?= $o['id'] ?></td>
                                <td><?= $o['product_name'] ?></td>
                                <td><span class="status-badge status-<?= $o['status'] ?>"><?= $o['status'] ?></span></td>
                                <td>
                                    <?php if ($o['status'] === 'Pending'): ?>
                                        <a href="?cancel_order=<?= $o['id'] ?>" class="btn-danger" style="text-decoration:none;">Cancel</a>
                                    <?php else: ?>
                                        <span>-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div id="contact" class="container contact-section">
            <h2>Contact Us</h2>
            <p>Need help? Reach out to the admin directly.</p>
            <div class="contact-card">
                <p><strong>📞 Admin Phone:</strong> +<?= ADMIN_PHONE ?></p>
                <p><strong>📧 Email:</strong> support@mystore.com</p>
                <p><strong>📍 Location:</strong> Main Street, City Center</p>
            </div>
        </div>
    </div>

    <div id="order-modal" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="closeModal()">&times;</span>
            <h3>Place Order</h3>
            <p id="modal-product-name" class="highlight-text"></p>
            
            <form method="POST" action="index.php">
                <input type="hidden" name="action" value="place_order">
                <input type="hidden" id="modal-product-id" name="product_id">

                <label>Your Name</label>
                <input type="text" name="name" placeholder="Enter your name" required>
                
                <label>Phone Number</label>
                <input type="text" name="phone" placeholder="Enter your number" required>
                
                <label>Delivery Address</label>
                <input type="text" name="address" placeholder="Enter address" required>
                
                <button type="submit" class="btn-confirm">Confirm & WhatsApp Admin</button>
            </form>
        </div>
    </div>

    <script src="js/script.js"></script>
</body>
</html>