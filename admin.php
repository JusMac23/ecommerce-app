<?php
session_start();

require 'functions/functions.php';

// --- 1. HANDLE AJAX REQUEST (Mark as Read) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_read_id'])) {
    if (!function_exists('getDB')) { 
        require_once __DIR__ . '/database/connection.php'; 
    }
    
    try {
        $conn = getDB();
        $stmt = $conn->prepare("DELETE FROM messages WHERE id = ?");
        $stmt->execute([$_POST['mark_read_id']]);
        echo "success"; 
    } catch (Exception $e) {
        echo "error";
    }
    exit; 
}

// --- AUTHENTICATION ---
$error = "";
$message = ""; 

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_admin'])) {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (checkAdminLogin($username, $password)) {
        $_SESSION['admin_logged_in'] = true;

        if (!function_exists('getDB')) { 
            require_once __DIR__ . '/database/connection.php'; 
        }
        $conn = getDB();
        
        $stmt = $conn->prepare("SELECT id, username FROM admins WHERE username = ? LIMIT 1");
        $stmt->execute([$username]);
        $adminData = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($adminData) {
            $_SESSION['admin_id'] = $adminData['id'];
            $_SESSION['admin_username'] = $adminData['username'];
        }

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

$isLoggedIn = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;

// --- HANDLE ADMIN ACTIONS ---
if ($isLoggedIn) {
    if (isset($_POST['add_product'])) {
        if(addProduct($_POST['name'], $_POST['description'], $_POST['price'], $_FILES['img'])) {
            $_SESSION['message'] = "Product added successfully!";
        } else {
            $_SESSION['message'] = "Failed to add product.";
        }
        header("Location: admin.php");
        exit;
    }

    if (isset($_POST['update_product'])) {
        $id = $_POST['product_id'];
        $name = $_POST['name'];
        $desc = $_POST['description'];
        $price = $_POST['price'];
        $file = $_FILES['img'];

        if(updateProduct($id, $name, $desc, $price, $file)) {
            $_SESSION['message'] = "Product updated successfully!";
        } else {
            $_SESSION['message'] = "Failed to update product.";
        }
        header("Location: admin.php");
        exit;
    }

    if (isset($_GET['delete_product'])) {
        deleteProduct($_GET['delete_product']);
        $_SESSION['message'] = "Product deleted successfully!";
        header("Location: admin.php");
        exit;
    }

    if (isset($_POST['update_status'])) {
        $orderId = $_POST['order_id'];
        $newStatus = $_POST['status'];
        updateOrderStatus($orderId, $newStatus); 
        $_SESSION['message'] = "Order status updated!";
        header("Location: admin.php");
        exit;
    }
}

if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']); 
}

// --- DATA FETCHING ---
$products = [];
$orders = [];
$messages = [];

if ($isLoggedIn) {
    $products = getProducts();
    $orders = getOrders();

    if (!function_exists('getDB')) {
        require_once __DIR__ . '/database/connection.php'; 
    }
    
    try {
        $conn = getDB(); 
        $stmt = $conn->prepare("SELECT * FROM messages ORDER BY created_at DESC");
        $stmt->execute();
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $messages = []; 
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel</title>
    <link rel="stylesheet" href="css/admin_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="admin-body">

    <?php if (!$isLoggedIn): ?>
    <div id="login-view" class="login-container">
        <div class="login-box">
            <?php if ($error): ?>
                <div class="alert alert-error"><?= $error ?></div>
            <?php endif; ?>
            
            <h2>Admin Login</h2>
            <form method="POST">
                <input type="hidden" name="login_admin" value="1">
                <div class="form-group">
                    <input type="text" name="username" placeholder="Username" required>
                </div>
                <div class="form-group">
                    <input type="password" name="password" placeholder="Password" required>
                </div>
                <button type="submit" class="btn-confirm w-100" style="margin-bottom:10px;">Login</button>
                <a href="forgot_password.php" class="auth-link">Forgot Password?</a>
            </form>
            <br>
            <a href="index.php" style="color:#666; font-size:0.9em; text-decoration: none;">← Back to Shop</a>
        </div>
    </div>
    <?php else: ?>

    <div id="admin-view">
        <nav class="admin-nav">
            <div class="logo">Admin Panel</div>
            
            <div class="index-hamburger">
                <span></span>
                <span></span>
                <span></span>
            </div>

            <div class="nav-links">
                <a href="admin_account.php" class="account-link">My Account</a>
                <a href="#" onclick="openMessagesModal(); return false;" class="notification-link">
                    Message 
                    <?php if(count($messages) > 0): ?>
                        <span id="msg-badge-count" class="notification-badge"><?= count($messages) ?></span>
                    <?php endif; ?>
                </a>
                <a href="index.php" target="_blank">View Shop</a>
                <a href="?logout=true" class="logout-btn-ghost">Logout</a>
            </div>
        </nav>

        <div class="main-content" style="padding: 20px;">
            
            <div class="container admin-card">
                <h3><i class="fa fa-plus-circle" style="margin-right: 10px; color: var(--primary);"></i>Add New Product</h3>
                
                <?php if(!empty($message)): ?>
                    <div class="alert alert-success">
                        <?= htmlspecialchars($message) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" class="form-group" enctype="multipart/form-data" style="display:flex; flex-direction: column;">
                    <input type="hidden" name="add_product" value="1">
                    <input type="text" name="name" placeholder="Product Name" required>
                    <textarea name="description" placeholder="Product Description" rows="3" required></textarea>
                    <input type="number" name="price" step="0.01" placeholder="Price" required>
                    <div style="padding: 10px; border: 1px solid #ccc; border-radius: 4px; background:#fff; margin-bottom: 15px;">
                        <label style="display:block; margin-bottom:5px; font-size: 0.9em; color:#666;">Product Image:</label>
                        <input type="file" name="img" accept="image/*" style="margin-bottom: 0; border:none; padding:0;" required>
                    </div>
                    <button type="submit" class="btn-confirm w-100">Add Product</button>
                </form>
            </div>

            <div class="container admin-card">
                <h3><i class="fa fa-tags" style="margin-right: 10px; color: var(--primary);"></i>Current Products</h3>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th style="width: 80px;">Image</th>
                                <th style="min-width: 150px;">Name</th>
                                <th style="min-width: 200px;">Description</th>
                                <th>Price</th>
                                <th style="min-width: 120px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $p): ?>
                            <tr>
                                <td><img src="<?= $p['img'] ?>" width="60" height="60" style="object-fit:cover; border-radius:4px;"></td>
                                <td><strong><?= htmlspecialchars($p['name']) ?></strong></td>
                                <td style="font-size: 0.9em; color: #555;">
                                    <?= strlen($p['description']) > 50 ? htmlspecialchars(substr($p['description'], 0, 50)) . '...' : htmlspecialchars($p['description']) ?>
                                </td>
                                <td style="font-weight: bold; color: var(--primary);">₱<?= number_format($p['price'], 2) ?></td>
                                <td>
                                    <div class="action-btn-group">
                                        <button class="btn-edit" 
                                            onclick="openEditModal(
                                                '<?= $p['id'] ?>', 
                                                '<?= addslashes($p['name']) ?>', 
                                                '<?= addslashes(str_replace(array("\r", "\n"), '', $p['description'])) ?>', 
                                                '<?= $p['price'] ?>'
                                            )">
                                            <i class="fa fa-edit"></i>
                                        </button>
                                        <a href="?delete_product=<?= $p['id'] ?>" class="btn-delete" onclick="return confirm('Delete this product?')">
                                            <i class="fa fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
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
                                <th style="min-width: 150px;">Customer Info</th>
                                <th style="min-width: 150px;">Product</th>
                                <th>Current Status</th>
                                <th style="min-width: 180px;">Update Status</th>
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
                                    <form method="POST" class="status-form" style="display:flex; gap:5px;">
                                        <input type="hidden" name="update_status" value="1">
                                        <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                                        <select name="status" class="status-select" style="margin-bottom:0;">
                                            <option value="Pending" <?= $o['status'] == 'Pending' ? 'selected' : '' ?>>Pending</option>
                                            <option value="Confirmed" <?= $o['status'] == 'Confirmed' ? 'selected' : '' ?>>Confirmed</option>
                                            <option value="For Delivery" <?= $o['status'] == 'For Delivery' ? 'selected' : '' ?>>For Delivery</option>
                                            <option value="Completed" <?= $o['status'] == 'Completed' ? 'selected' : '' ?>>Completed</option>
                                            <option value="Cancelled" <?= $o['status'] == 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                        </select>
                                        <button type="submit" class="btn-update">Save</button>
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

    <div id="edit-modal" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="closeEditModal()">×</span>
            <h3 style="text-align:center; border-bottom:1px solid #eee; padding-bottom:10px; margin-bottom:20px;">Edit Product</h3>
            
            <form method="POST" enctype="multipart/form-data" class="modal-form">
                <input type="hidden" name="update_product" value="1">
                <input type="hidden" id="edit-id" name="product_id">
                
                <label>Product Name</label>
                <input type="text" id="edit-name" name="name" required>
                
                <label>Description</label>
                <textarea id="edit-desc" name="description" rows="4" required></textarea>
                
                <label>Price</label>
                <input type="number" id="edit-price" name="price" step="0.01" required>
                
                <label>Change Image (Optional)</label>
                <input type="file" name="img" accept="image/*" style="border:none; padding:0;">
                <small style="color:#666; display:block; margin-top:-10px;">Leave blank to keep current image.</small>
                
                <div style="margin-top:20px; display:flex; gap:10px; justify-content: flex-end;">
                    <button type="button" onclick="closeEditModal()" style="padding:10px 15px; border:none; background:#ccc; cursor:pointer; border-radius:4px;">Cancel</button>
                    <button type="submit" class="btn-confirm">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <div id="messages-modal" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="closeMessagesModal()">×</span>
            <h3 style="text-align:center; border-bottom:1px solid #eee; padding-bottom:10px; margin-bottom:20px;">
                <i class="fa fa-envelope" style="color: var(--primary);"></i> Inbound Messages
            </h3>
            
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th width="5%">ID</th>
                            <th width="15%">Date</th>
                            <th width="15%">Name</th>
                            <th width="20%">Email</th>
                            <th width="35%">Message</th>
                            <th width="10%">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($messages) > 0): ?>
                            <?php foreach ($messages as $row): ?>
                                <tr id="msg-row-<?php echo $row['id']; ?>">
                                    <td><?php echo htmlspecialchars($row['id']); ?></td>
                                    <td><?php echo htmlspecialchars($row['created_at']); ?></td>
                                    <td><?php echo htmlspecialchars($row['name']); ?></td>
                                    <td><a href="mailto:<?php echo htmlspecialchars($row['email']); ?>" style="color:var(--primary);"><?php echo htmlspecialchars($row['email']); ?></a></td>
                                    <td><?php echo nl2br(htmlspecialchars($row['message'])); ?></td>
                                    <td>
                                        <button 
                                            onclick="markAsRead(<?php echo $row['id']; ?>)" 
                                            style="padding:5px 15px; border:1px solid var(--primary); background:transparent; color:gray; cursor:pointer; border-radius:25px; white-space:nowrap; transition: all 0.2s;"
                                            onmouseover="this.style.background='var(--primary)'; this.style.color='white';"
                                            onmouseout="this.style.background='transparent'; this.style.color='gray';"
                                        >
                                            <i class="fa fa-check"></i> Read
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="no-msg">No messages found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <footer class="footer">
        <div class="footer-content">
            <p>&copy; <?= date('Y') ?> Souvenir Shop. All rights reserved.</p>
        </div>
    </footer>

    <script src="js/script.js"></script>

    <script>
        const msgModal = document.getElementById('messages-modal');
        const editModal = document.getElementById('edit-modal');
        
        function openMessagesModal() {
            msgModal.style.display = "flex"; // Changed from block to flex for better centering
        }
        
        function closeMessagesModal() {
            msgModal.style.display = "none";
        }

        function closeEditModal() {
            if(editModal) editModal.style.display = "none";
        }
        
        window.onclick = function(event) {
            if (event.target == editModal) {
                closeEditModal();
            }
            if (event.target == msgModal) {
                closeMessagesModal();
            }
        }

        function markAsRead(id) {
            if(!confirm('Mark this message as read? (This will delete it from view)')) return;

            let formData = new FormData();
            formData.append('mark_read_id', id);

            fetch('admin.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(data => {
                if(data.trim() === 'success') {
                    const row = document.getElementById('msg-row-' + id);
                    if(row) row.remove();

                    const badge = document.getElementById('msg-badge-count');
                    if(badge) {
                        let count = parseInt(badge.innerText);
                        count = count - 1;
                        
                        if(count <= 0) {
                            badge.style.display = 'none';
                        } else {
                            badge.innerText = count;
                        }
                    }
                } else {
                    alert('Error updating status. Please try again.');
                }
            })
            .catch(error => console.error('Error:', error));
        }
    </script>

    <?php endif; ?>
</body>
</html>