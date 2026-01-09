<?php

require 'functions/functions.php';

// --- AUTHENTICATION ---
$error = "";
$message = ""; 

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_admin'])) {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    // Check against admins table
    if (checkAdminLogin($username, $password)) {
        $_SESSION['admin_logged_in'] = true;
        header("Location: admin.php");
        exit;
    } else {
        $error = "Incorrect Username or Password!";
    }
}

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: admin.php");
    exit;
}

// Check if logged in
$isLoggedIn = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;

// --- HANDLE ADMIN ACTIONS ---
if ($isLoggedIn) {
    
    // 1. Add Product
    if (isset($_POST['add_product'])) {
        if(addProduct($_POST['name'], $_POST['description'], $_POST['price'], $_FILES['img'])) {
            $_SESSION['message'] = "Product added successfully!";
        } else {
            $_SESSION['message'] = "Failed to add product.";
        }
        header("Location: admin.php");
        exit;
    }

    // 2. Delete Product
    if (isset($_GET['delete_product'])) {
        deleteProduct($_GET['delete_product']);
        $_SESSION['message'] = "Product deleted successfully!";
        header("Location: admin.php");
        exit;
    }

    // 3. Update Order Status
    if (isset($_POST['update_status'])) {
        $orderId = $_POST['order_id'];
        $newStatus = $_POST['status'];
        updateOrderStatus($orderId, $newStatus);
        $_SESSION['message'] = "Order status updated!";
        header("Location: admin.php");
        exit;
    }
}

// Check for session messages (Feedback for actions)
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']); // Clear message after displaying
}

$products = getProducts();
$orders = getOrders();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="admin-body">

    <?php if (!$isLoggedIn): ?>
    <div id="login-view" class="login-container">
        <div class="login-box">
            <h2>Admin Login</h2>
            <form method="POST">
                <input type="hidden" name="login_admin" value="1">
                <input type="text" name="username" placeholder="Username" required>
                <input type="password" name="password" placeholder="Password" required>
                <button type="submit" class="btn-confirm" style="width:100%;">Login</button>
            </form>
            <?php if ($error): ?>
                <p class="error-msg"><?= $error ?></p>
            <?php endif; ?>
            <br>
            <a href="index.php" style="color:#666; font-size:0.9em;">← Back to Shop</a>
        </div>
    </div>
    <?php else: ?>

    <div id="admin-view">
        <nav class="admin-nav">
            <div class="logo">Admin Panel</div>
            
            <div class="hamburger">
                <span></span>
                <span></span>
                <span></span>
            </div>

            <div class="nav-links">
                <a href="index.php" target="_blank">View Shop</a>
                <a href="?logout=true" class="logout-btn-ghost">Logout</a>
            </div>
        </nav>

        <div class="main-content" style="padding: 20px;">
            
            <div class="container admin-card">
                <h3><i class="fa fa-plus-circle" style="margin-right: 10px; color: var(--primary);"></i>Add New Product</h3>
                
                <?php if(!empty($message)): ?>
                    <div style="padding: 10px; margin-bottom: 10px; background: #e0f7fa; color: #006064; border-radius: 4px;">
                        <?= htmlspecialchars($message) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" class="form-group" enctype="multipart/form-data" style="display:flex; flex-direction: column; gap:10px;">
                    <input type="hidden" name="add_product" value="1">
                    <input type="text" name="name" placeholder="Product Name" required style="padding:10px; border: 1px solid #ccc; border-radius: 4px;">
                    <textarea name="description" placeholder="Product Description" required style="padding:10px; border: 1px solid #ccc; border-radius: 4px; min-height: 80px;"></textarea>
                    <input type="number" name="price" step="0.01" placeholder="Price" required style="padding:10px; border: 1px solid #ccc; border-radius: 4px;">
                    <div style="padding: 5px; border: 1px dashed #ccc;">
                        <label style="display:block; margin-bottom:5px; font-size: 0.9em; color:#666;">Product Image:</label>
                        <input type="file" name="img" accept="image/*" required>
                    </div>
                    <button type="submit" class="btn-confirm" style="padding: 10px; background-color: var(--primary, #007bff); color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: bold;">
                        Add Product
                    </button>
                </form>
            </div>

            <div class="container admin-card">
                <h3><i class="fa fa-tags" style="margin-right: 10px; color: var(--primary);"></i>Current Products</h3>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Image</th>
                                <th>Name</th>
                                <th>Price</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $p): ?>
                            <tr>
                                <td><img src="<?= $p['img'] ?>" width="60" height="60" style="object-fit:cover; border-radius:4px;"></td>
                                <td><?= htmlspecialchars($p['name']) ?></td>
                                <td>₱<?= number_format($p['price'], 2) ?></td>
                                <td><a href="?delete_product=<?= $p['id'] ?>" class="btn-danger" style="text-decoration:none; padding:5px 10px;" onclick="return confirm('Delete this product?')">Delete</a></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="container admin-card">
                <h3><i class="fa fa-shopping-cart" style="margin-right: 10px; color: var(--primary);"></i>Customer Orders</h3>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Customer Info</th>
                                <th>Product</th>
                                <th>Current Status</th>
                                <th>Update Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $o): ?>
                            <tr>
                                <td>#<?= $o['id'] ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($o['customer_name']) ?></strong><br>
                                    <small><?= htmlspecialchars($o['contact']) ?></small><br>
                                    <small><?= htmlspecialchars($o['address']) ?></small>
                                </td>
                                <td><?= htmlspecialchars($o['product_name']) ?></td>
                                <td>
                                    <span class="status-badge status-<?= str_replace(' ', '-', $o['status']) ?>"><?= $o['status'] ?></span>
                                </td>
                                <td>
                                    <form method="POST" class="status-form">
                                        <input type="hidden" name="update_status" value="1">
                                        <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                                        <select name="status" class="status-select">
                                            <option value="Pending" <?= $o['status'] == 'Pending' ? 'selected' : '' ?>>Pending</option>
                                            <option value="Confirmed" <?= $o['status'] == 'Confirmed' ? 'selected' : '' ?>>Confirmed</option>
                                            <option value="For Delivery" <?= $o['status'] == 'For Delivery' ? 'selected' : '' ?>>For Delivery</option>
                                            <option value="Completed" <?= $o['status'] == 'Completed' ? 'selected' : '' ?>>Completed</option>
                                            <option value="Cancelled" <?= $o['status'] == 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                        </select>
                                        <button type="submit" class="btn-update" style="width: 100%;">Save</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>

    <script src="js/sidebar.js"></script>

    <?php endif; ?>
</body>
</html>