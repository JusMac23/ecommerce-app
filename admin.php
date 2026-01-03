<?php
session_start();
require 'functions.php';

// Handle Login
$error = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (checkAdminLogin($username, $password)) {
        $_SESSION['admin_logged_in'] = true;
        header("Location: admin.php");
        exit;
    } else {
        $error = "Incorrect Username or Password!";
    }
}

// Handle Logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: admin.php");
    exit;
}

// Check Access
$isLoggedIn = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;

// Handle Add Product
if ($isLoggedIn && isset($_POST['add_product'])) {
    addProduct($_POST['name'], $_POST['price'], $_FILES['img']);
    header("Location: admin.php");
    exit;
}

// Handle Delete Product
if ($isLoggedIn && isset($_GET['delete_product'])) {
    deleteProduct($_GET['delete_product']);
    header("Location: admin.php");
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
    <title>Store Admin Panel</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="admin-body">

    <?php if (!$isLoggedIn): ?>
    <div id="login-view" class="login-container">
        <div class="login-box">
            <h2>Admin Login</h2>
            <p>Enter credentials to manage store</p>
            
            <form method="POST">
                <input type="text" name="username" placeholder="Username" required>
                <input type="password" name="password" placeholder="Password" required>
                <button type="submit" name="login" class="btn-primary w-100">Login</button>
            </form>
            
            <?php if ($error): ?>
                <p class="error-msg" style="display:block;"><?= $error ?></p>
            <?php endif; ?>
            
            <br>
            <a href="forgot_password.php" style="font-size:0.9rem; color: #64748b;">Forgot Password?</a>
            <br><br>
            <a href="index.php" class="back-link-muted">← Back to Shop</a>
        </div>
    </div>
    <?php else: ?>

    <div id="admin-view">
        <nav class="admin-nav">
            <div class="logo">Admin Panel</div>
            <div class="nav-links">
                <a href="index.php" target="_blank">View Shop</a>
                <a href="?logout=true" class="btn-danger">Logout</a>
            </div>
        </nav>

        <div class="main-content">
            
            <div class="container admin-card">
                <h3>Add New Product</h3>
                <form method="POST" class="form-group" enctype="multipart/form-data">
                    <input type="hidden" name="add_product" value="1">
                    <input type="text" name="name" placeholder="Product Name" required>
                    <input type="number" name="price" step="0.01" placeholder="Price" required>
                    <input type="file" name="img" accept="image/*" required>
                    <button type="submit" class="btn-primary">Add Product</button>
                </form>
            </div>

            <div class="container admin-card">
                <h3>Current Products</h3>
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
                                <td><img src="<?= $p['img'] ?>" width="60" height="60" style="object-fit:cover; border-radius:4px; border:1px solid #ddd;"></td>
                                <td><?= $p['name'] ?></td>
                                <td>₱<?= number_format($p['price'], 2) ?></td>
                                <td><a href="?delete_product=<?= $p['id'] ?>" class="btn-danger" onclick="return confirm('Delete this product?')">Delete</a></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="container admin-card">
                <h3>Customer Orders</h3>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Customer Info</th>
                                <th>Product</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $o): ?>
                            <tr>
                                <td>#<?= $o['id'] ?></td>
                                <td>
                                    <strong><?= $o['customerName'] ?></strong><br>
                                    <small><?= $o['contact'] ?></small><br>
                                    <small><?= $o['address'] ?></small>
                                </td>
                                <td><?= $o['productName'] ?></td>
                                <td><span class="status-badge status-<?= $o['status'] ?>"><?= $o['status'] ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
    <?php endif; ?>

</body>
</html>