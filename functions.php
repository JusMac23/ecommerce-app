<?php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

include 'database/connection.php';

// ==========================================
// 2. AUTHENTICATION & OTP
// ==========================================

function checkAdminLogin($username, $password) {
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        return true;
    }
    return false;
}

// Ensure Admin Exists
function ensureAdminTableExists() {
    $pdo = getDB();
    $pdo->exec("CREATE TABLE IF NOT EXISTS admins (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        contact_number VARCHAR(20) NULL,
        otp_code VARCHAR(6) NULL,
        otp_expires DATETIME NULL
    )");

    $stmt = $pdo->query("SELECT count(*) FROM admins");
    if ($stmt->fetchColumn() == 0) {
        $defaultPass = password_hash('admin123', PASSWORD_DEFAULT);
        // NOTE: Default phone is 09123456789. Change in DB if needed.
        $stmt = $pdo->prepare("INSERT INTO admins (username, password, contact_number) VALUES ('admin', ?, '09123456789')");
        $stmt->execute([$defaultPass]);
    }
}
ensureAdminTableExists();

// Request OTP via Phone
function requestOTP($phone) {
    $pdo = getDB();
    
    // Check if phone exists
    $stmt = $pdo->prepare("SELECT id FROM admins WHERE contact_number = ?");
    $stmt->execute([$phone]);
    $user = $stmt->fetch();

    if ($user) {
        $otp = rand(100000, 999999);
        $expiry = date('Y-m-d H:i:s', strtotime('+10 minutes'));

        $update = $pdo->prepare("UPDATE admins SET otp_code = ?, otp_expires = ? WHERE contact_number = ?");
        $update->execute([$otp, $expiry, $phone]);

        return $otp; 
    }
    return false;
}

// Verify OTP and Reset Password
function verifyResetOTP($phone, $otp, $newPassword) {
    $pdo = getDB();
    $now = date('Y-m-d H:i:s');

    $stmt = $pdo->prepare("SELECT id FROM admins WHERE contact_number = ? AND otp_code = ? AND otp_expires > ?");
    $stmt->execute([$phone, $otp, $now]);
    $user = $stmt->fetch();

    if ($user) {
        $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
        
        $update = $pdo->prepare("UPDATE admins SET password = ?, otp_code = NULL, otp_expires = NULL WHERE id = ?");
        $update->execute([$newHash, $user['id']]);
        
        return true;
    }
    return false;
}

// ==========================================
// 3. PRODUCT LOGIC
// ==========================================

function getProducts() {
    $pdo = getDB();
    $stmt = $pdo->query("SELECT * FROM products ORDER BY id DESC");
    return $stmt->fetchAll();
}

function addProduct($name, $price, $file) {
    $pdo = getDB();
    $imagePath = "https://via.placeholder.com/300"; 

    if (isset($file) && $file['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $file['tmp_name'];
        $fileName = $file['name'];
        $newFileName = time() . '_' . preg_replace("/[^a-zA-Z0-9.]/", "", basename($fileName));
        $dest_path = UPLOAD_DIR . $newFileName;
        
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'gif', 'png', 'jpeg', 'webp'])) {
            if(move_uploaded_file($fileTmpPath, $dest_path)) {
                $imagePath = $dest_path; 
            }
        }
    }

    $stmt = $pdo->prepare("INSERT INTO products (name, price, img) VALUES (?, ?, ?)");
    $stmt->execute([htmlspecialchars($name), floatval($price), $imagePath]);
}

function deleteProduct($id) {
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT img FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $product = $stmt->fetch();
    
    if($product && file_exists($product['img']) && strpos($product['img'], 'uploads/') !== false) {
        unlink($product['img']); 
    }
    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
    $stmt->execute([$id]);
}

// ==========================================
// 4. ORDER LOGIC
// ==========================================

function getOrders() {
    $pdo = getDB();
    $stmt = $pdo->query("SELECT * FROM orders ORDER BY id DESC");
    return $stmt->fetchAll();
}

function addOrder($productId, $customerName, $phone, $address) {
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT name FROM products WHERE id = ?");
    $stmt->execute([$productId]);
    $product = $stmt->fetch();
    $pName = $product ? $product['name'] : "Unknown Product";

    $stmtInsert = $pdo->prepare("INSERT INTO orders (product_name, customer_name, contact, address, status) VALUES (?, ?, ?, ?, 'Pending')");
    $stmtInsert->execute([$pName, htmlspecialchars($customerName), htmlspecialchars($phone), htmlspecialchars($address)]);

    return ['id' => $pdo->lastInsertId(), 'productName' => $pName, 'customerName' => htmlspecialchars($customerName), 'address' => htmlspecialchars($address)];
}

function updateOrderStatus($id, $status) {
    $pdo = getDB();
    $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->execute([$status, $id]);
}
?>