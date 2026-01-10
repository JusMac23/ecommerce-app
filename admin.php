<?php
session_start(); // Ensure session is started at the very top

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

    // Check against admins table
    if (checkAdminLogin($username, $password)) {
        $_SESSION['admin_logged_in'] = true;

        // --- FIXED: FETCH AND SAVE ADMIN ID ---
        // We must get the ID so admin_account.php knows who is logged in
        if (!function_exists('getDB')) { 
            require_once __DIR__ . '/database/connection.php'; 
        }
        $conn = getDB();
        
        // Fetch ID based on the username
        $stmt = $conn->prepare("SELECT id, username FROM admins WHERE username = ? LIMIT 1");
        $stmt->execute([$username]);
        $adminData = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($adminData) {
            $_SESSION['admin_id'] = $adminData['id']; // <--- CRITICAL FIX
            $_SESSION['admin_username'] = $adminData['username'];
        }
        // --------------------------------------

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

    // 2. Update Product
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

    // 3. Delete Product
    if (isset($_GET['delete_product'])) {
        deleteProduct($_GET['delete_product']);
        $_SESSION['message'] = "Product deleted successfully!";
        header("Location: admin.php");
        exit;
    }

    // 4. Update Order Status
    if (isset($_POST['update_status'])) {
        $orderId = $_POST['order_id'];
        $newStatus = $_POST['status'];
        updateOrderStatus($orderId, $newStatus); 
        $_SESSION['message'] = "Order status updated!";
        header("Location: admin.php");
        exit;
    }
}

// Check for session messages
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

    // --- MESSAGE FETCHING ---
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
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/edit_modal_product.css">
    <link rel="stylesheet" href="css/messages.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="admin-body">

    <?php if (!$isLoggedIn): ?>
    <div id="login-view" class="login-container">
        <div class="login-box">
            <h2>Admin Login</h2>
            <form method="POST">
                <input type="hidden" name="login_admin" value="1">
                <div class="form-group">
                    <input type="text" name="username" placeholder="Username" style="font-family: 'Google Sans', sans-serif; width: 100%; padding: 10px; box-sizing: border-box;" required>
                </div>
                <div class="form-group">
                    <input type="password" name="password" placeholder="Password" style="font-family: 'Google Sans', sans-serif; width: 100%; padding: 10px; box-sizing: border-box;" required>
                </div>
                <button type="submit" class="btn-confirm" style="width:100%; font-family: 'Google Sans', sans-serif; padding: 10px; cursor: pointer; margin-bottom:10px;">Login</button>
                <a href="forgot_password.php" class="forgot-password-link">Forgot Password?</a>
            </form>

            <?php if ($error): ?>
                <p class="error-msg" style="color: red; margin-top: 10px;"><?= $error ?></p>
            <?php endif; ?>
            
            <br>
            <a href="index.php" style="color:#666; font-size:0.9em; text-decoration: none; font-family: 'Google Sans', sans-serif;">← Back to Shop</a>
        </div>
    </div>
    <?php else: ?>

    <div id="admin-view">
        <nav class="admin-nav">
            <div class="logo">Admin Panel</div>
            
            <div class="admin-hamburger">
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
                    <div style="padding: 10px; margin-bottom: 10px; background: #e0f7fa; color: #006064; border-radius: 4px;">
                        <?= htmlspecialchars($message) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" class="form-group" enctype="multipart/form-data" style="display:flex; flex-direction: column;">
                    <input type="hidden" name="add_product" value="1">
                    <input type="text" name="name" placeholder="Product Name" required style="padding:10px; border: 1px solid #ccc; border-radius: 4px; margin-bottom: 10px; font-family: Google sans;">
                    <textarea name="description" placeholder="Product Description" required style="padding:10px; border: 1px solid #ccc; border-radius: 4px; min-height: 80px; margin-bottom: 10px; font-family: Google sans;"></textarea>
                    <input type="number" name="price" step="0.01" placeholder="Price" required style="padding:10px; border: 1px solid #ccc; border-radius: 4px; margin-bottom: 10px; font-family: Google sans;">
                    <div style="padding: 10px; border: 1px solid #ccc; border-radius: 4px;">
                        <label style="display:block; margin-bottom:5px; font-size: 0.9em; color:#666;">Product Image:</label>
                        <input type="file" name="img" accept="image/*" required>
                    </div>
                    <button type="submit" class="btn-confirm" style="padding: 10px; margin-top: 15px; background-color: var(--primary, #007bff); color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: bold; font-family: Google sans;">
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
                                <th style="width: 80px;">Image</th>
                                <th style="width: 20%;">Name</th>
                                <th style="width: 35%;">Description</th>
                                <th style="width: 15%;">Price</th>
                                <th style="width: 15%;">Actions</th>
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
                                        <select name="status" class="status-select" style="font-family: Google sans;">
                                            <option value="Pending" <?= $o['status'] == 'Pending' ? 'selected' : '' ?>>Pending</option>
                                            <option value="Confirmed" <?= $o['status'] == 'Confirmed' ? 'selected' : '' ?>>Confirmed</option>
                                            <option value="For Delivery" <?= $o['status'] == 'For Delivery' ? 'selected' : '' ?>>For Delivery</option>
                                            <option value="Completed" <?= $o['status'] == 'Completed' ? 'selected' : '' ?>>Completed</option>
                                            <option value="Cancelled" <?= $o['status'] == 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                        </select>
                                        <button type="submit" class="btn-update" style="width: 100%; font-family: Google sans;">Save</button>
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
                <input type="file" name="img" accept="image/*">
                <small style="color:#666;">Leave blank to keep current image.</small>
                
                <div style="margin-top:20px; text-align:right;">
                    <button type="button" onclick="closeEditModal()" style="padding:10px 15px; border:none; background:#ccc; cursor:pointer; border-radius:4px; margin-right:5px; font-family: Google sans">Cancel</button>
                    <button type="submit" style="padding:10px 20px; border:none; background:var(--primary, #007bff); color:white; font-weight:bold; cursor:pointer; border-radius:4px; font-family: Google sans">Save Changes</button>
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
                                    <td><a href="mailto:<?php echo htmlspecialchars($row['email']); ?>"><?php echo htmlspecialchars($row['email']); ?></a></td>
                                    <td><?php echo nl2br(htmlspecialchars($row['message'])); ?></td>
                                    <td>
                                        <button 
                                            onclick="markAsRead(<?php echo $row['id']; ?>)" 
                                            style="padding:5px 15px; border:1px solid #007bff; background:transparent; color:gray; cursor:pointer; border-radius:25px; white-space:nowrap; font-family: 'Google Sans', sans-serif; transition: all 0.2s;"
                                            onmouseover="this.style.background='#007bff'; this.style.color='white';"
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

    <div class="footer">
        <p>&copy; <?= date('Y') ?> Souvenir Shop. All rights reserved.</p>
    </div>

    <script src="js/sidebar.js"></script>
    <script src="js/edit_modal_product.js"></script>
    
    <script>
        const msgModal = document.getElementById('messages-modal');
        
        function openMessagesModal() {
            msgModal.style.display = "block";
        }
        
        function closeMessagesModal() {
            msgModal.style.display = "none";
        }
        
        // Handle closing when clicking outside the modal
        window.onclick = function(event) {
            const editModal = document.getElementById('edit-modal');
            if (event.target == editModal) {
                editModal.style.display = "none";
            }
            if (event.target == msgModal) {
                msgModal.style.display = "none";
            }
        }

        // --- NEW FUNCTION: Mark as Read & Update Badge ---
        function markAsRead(id) {
            if(!confirm('Mark this message as read? (This will delete it from view)')) return;

            // Prepare form data for AJAX
            let formData = new FormData();
            formData.append('mark_read_id', id);

            // Send request to same file (admin.php)
            fetch('admin.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(data => {
                // If the PHP block at the top returns "success"
                if(data.trim() === 'success') {
                    
                    // 1. Remove the row from the table
                    const row = document.getElementById('msg-row-' + id);
                    if(row) {
                        row.remove(); // Removes the element completely
                    }

                    // 2. Decrease the badge count
                    const badge = document.getElementById('msg-badge-count');
                    if(badge) {
                        let count = parseInt(badge.innerText);
                        count = count - 1;
                        
                        if(count <= 0) {
                            badge.style.display = 'none'; // Hide badge if 0
                        } else {
                            badge.innerText = count; // Update text
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