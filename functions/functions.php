<?php

// Prevent header errors
ob_start();

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// ==========================================
// 1. DATABASE CONNECTION
// ==========================================

// Ensure this file exists, or create a simple connection here
if (file_exists(__DIR__ . '/../database/connection.php')) {
    include __DIR__ . '/../database/connection.php';
} else {
    // Fallback connection
    if (!function_exists('getDB')) {
        function getDB() {
            $host = 'localhost';
            $db   = 'souvenir_shop';
            $user = 'root';
            $pass = 'CD@it1432'; // Make sure this matches your local setup
            $dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";
            try {
                $pdo = new PDO($dsn, $user, $pass);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                return $pdo;
            } catch (PDOException $e) {
                die("DB Connection Failed: " . $e->getMessage());
            }
        }
    }
}

// Define Admin Phone Globally
function getAdminPhone() {
    $pdo = getDB();
    try {
        $stmt = $pdo->query("SELECT contact_number FROM admins LIMIT 1");
        return $stmt->fetchColumn() ?: '09123456789';
    } catch (Exception $e) {
        return '0000000000';
    }
}

if (!defined('ADMIN_PHONE')) {
    define('ADMIN_PHONE', getAdminPhone());
}

// ==========================================
// 2. DATABASE SETUP (AUTO-CREATE TABLES)
// ==========================================

function ensureTablesExist() {
    $pdo = getDB();

    // 1. Admins
    $pdo->exec("CREATE TABLE IF NOT EXISTS admins (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        contact_number VARCHAR(20) NULL
    )");

    // Create default admin if none exists
    $stmt = $pdo->query("SELECT count(*) FROM admins");
    if ($stmt->fetchColumn() == 0) {
        $defaultPass = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO admins (username, password, contact_number) VALUES ('admin', ?, '09123456789')");
        $stmt->execute([$defaultPass]);
    }

    // 2. Customers
    $pdo->exec("CREATE TABLE IF NOT EXISTS customers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        first_name VARCHAR(50) NOT NULL,
        middle_name VARCHAR(1) NULL,
        last_name VARCHAR(50) NOT NULL,
        email VARCHAR(100) NOT NULL UNIQUE,
        phone VARCHAR(20) NOT NULL,
        password VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // 3. Products
    $pdo->exec("CREATE TABLE IF NOT EXISTS products (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        description TEXT NULL,
        price DECIMAL(10,2) NOT NULL,
        img VARCHAR(255) NOT NULL
    )");

    // 4. Orders
    $pdo->exec("CREATE TABLE IF NOT EXISTS orders (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NULL,
        product_name VARCHAR(100) NOT NULL,
        customer_name VARCHAR(100) NOT NULL,
        contact VARCHAR(20) NOT NULL,
        address TEXT NOT NULL,
        status VARCHAR(100) DEFAULT 'Pending', 
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
}
// Run setup once
ensureTablesExist();

// ==========================================
// 3. AUTHENTICATION
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

function registerUser($fname, $mname, $lname, $email, $phone, $password) {
    $pdo = getDB();
    
    $stmt = $pdo->prepare("SELECT id FROM customers WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) return "Email already registered.";

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO customers (first_name, middle_name, last_name, email, phone, password) VALUES (?, ?, ?, ?, ?, ?)");
    
    if ($stmt->execute([$fname, $mname, $lname, $email, $phone, $hash])) {
        return true;
    }
    return "Registration failed.";
}

function loginUser($email, $password) {
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT * FROM customers WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        return $user; 
    }
    return false;
}

// ==========================================
// 4. PRODUCT LOGIC
// ==========================================

function getProducts() {
    $pdo = getDB();
    try {
        $stmt = $pdo->query("SELECT * FROM products ORDER BY id DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return []; 
    }
}

// This function expects exactly 4 arguments: Name, Description, Price, File
function addProduct($name, $description, $price, $file) {
    $pdo = getDB();
    
    $imagePath = "https://via.placeholder.com/300"; 
    $uploadDir = 'uploads/'; // Ensure this folder exists in your root

    if (!is_dir($uploadDir)) { 
        mkdir($uploadDir, 0755, true); 
    }               

    if (isset($file) && $file['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $file['tmp_name'];
        $fileName    = $file['name'];
        
        // Sanitize filename
        $cleanFileName = preg_replace("/[^a-zA-Z0-9.]/", "", basename($fileName));
        $newFileName   = time() . '_' . $cleanFileName;
        $dest_path     = $uploadDir . $newFileName; 
        
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowedExts = ['jpg', 'gif', 'png', 'jpeg', 'webp'];

        if (in_array($ext, $allowedExts)) {
            if(move_uploaded_file($fileTmpPath, $dest_path)) {
                $imagePath = $dest_path; 
            }
        }
    }

    $stmt = $pdo->prepare("INSERT INTO products (name, description, price, img) VALUES (?, ?, ?, ?)");
    return $stmt->execute([$name, $description, floatval($price), $imagePath]);
}

function deleteProduct($id) {
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT img FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $product = $stmt->fetch();
    
    // Only delete if it's a local file in uploads folder
    if($product && file_exists($product['img']) && strpos($product['img'], 'uploads/') !== false) {
        unlink($product['img']); 
    }
    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
    $stmt->execute([$id]);
}

// ==========================================
// 5. ORDER LOGIC
// ==========================================

function getOrders() {
    $pdo = getDB();
    $stmt = $pdo->query("SELECT * FROM orders ORDER BY id DESC");
    return $stmt->fetchAll();
}

function getUserOrders($userId) {
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY id DESC");
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

function addOrder($productId, $customerName, $phone, $address, $userId) {
    $pdo = getDB();
    
    $stmt = $pdo->prepare("SELECT name FROM products WHERE id = ?");
    $stmt->execute([$productId]);
    $product = $stmt->fetch();
    $pName = $product ? $product['name'] : "Unknown Product";

    $stmtInsert = $pdo->prepare("INSERT INTO orders (user_id, product_name, customer_name, contact, address, status) VALUES (?, ?, ?, ?, ?, 'Pending')");
    $stmtInsert->execute([$userId, $pName, htmlspecialchars($customerName), htmlspecialchars($phone), htmlspecialchars($address)]);
}

function updateOrderStatus($id, $status, $userId = null) {
    $pdo = getDB();
    if ($userId) {
        $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ? AND user_id = ?");
        $stmt->execute([$status, $id, $userId]);
    } else {
        $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->execute([$status, $id]);
    }
}
?>