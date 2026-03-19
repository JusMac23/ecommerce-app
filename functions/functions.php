<?php
// Prevent header errors
ob_start();

// Start session safely
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ==========================================
// 0. LOAD ENV & DB (FIXED PATHS)
// ==========================================

// Load env.php from PROJECT ROOT
require_once dirname(__DIR__) . '/env.php';

// Load database connection
require_once dirname(__DIR__) . '/database/connection.php';

// ==========================================
// 1. ADMIN PHONE
// ==========================================

function getAdminPhone() {
    try {
        $pdo = getDB();
        $stmt = $pdo->query("SELECT contact_number FROM admins LIMIT 1");
        return $stmt->fetchColumn() ?: '09123456789';
    } catch (Exception $e) {
        return '09123456789';
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

    // Admins
    $pdo->exec("CREATE TABLE IF NOT EXISTS admins (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        contact_number VARCHAR(20) NULL
    )");

    // Create default admin if none exists
    $stmt = $pdo->query("SELECT COUNT(*) FROM admins");
    if ($stmt->fetchColumn() == 0) {
        $defaultPass = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare(
            "INSERT INTO admins (username, password, contact_number)
             VALUES ('admin', ?, '09123456789')"
        );
        $stmt->execute([$defaultPass]);
    }

    // Customers
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

    // Products
    $pdo->exec("CREATE TABLE IF NOT EXISTS products (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        description TEXT NULL,
        price DECIMAL(10,2) NOT NULL,
        img VARCHAR(255) NOT NULL
    )");

    // Orders
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

// Run once
ensureTablesExist();

// ==========================================
// 3. AUTHENTICATION
// ==========================================

function checkAdminLogin($username, $password) {
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    return ($user && password_verify($password, $user['password']));
}

function registerUser($fname, $mname, $lname, $email, $phone, $password) {
    $pdo = getDB();

    $stmt = $pdo->prepare("SELECT id FROM customers WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        return "Email already registered.";
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare(
        "INSERT INTO customers 
        (first_name, middle_name, last_name, email, phone, password)
        VALUES (?, ?, ?, ?, ?, ?)"
    );

    return $stmt->execute([$fname, $mname, $lname, $email, $phone, $hash])
        ? true
        : "Registration failed.";
}

function loginUser($email, $password) {
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT * FROM customers WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    return ($user && password_verify($password, $user['password'])) ? $user : false;
}

// ==========================================
// 4. PRODUCT LOGIC
// ==========================================

function getProducts() {
    try {
        return getDB()
            ->query("SELECT * FROM products ORDER BY id DESC")
            ->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return [];
    }
}

function getProductById($id) {
    $stmt = getDB()->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function addProduct($name, $description, $price, $file) {
    $pdo = getDB();
    $uploadDir = 'uploads/';
    $imagePath = "https://via.placeholder.com/300";

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    if (isset($file) && $file['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        if (in_array($ext, $allowed)) {
            $cleanName = preg_replace("/[^a-zA-Z0-9.]/", "", basename($file['name']));
            $newName = time() . '_' . $cleanName;
            $dest = $uploadDir . $newName;

            if (move_uploaded_file($file['tmp_name'], $dest)) {
                $imagePath = $dest;
            }
        }
    }

    $stmt = $pdo->prepare(
        "INSERT INTO products (name, description, price, img)
         VALUES (?, ?, ?, ?)"
    );
    return $stmt->execute([$name, $description, (float)$price, $imagePath]);
}

function updateProduct($id, $name, $description, $price, $file) {
    $pdo = getDB();
    $uploadDir = 'uploads/';

    if (isset($file) && $file['error'] === UPLOAD_ERR_OK) {
        $old = getProductById($id);
        if ($old && file_exists($old['img']) && strpos($old['img'], 'uploads/') !== false) {
            unlink($old['img']);
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        if (in_array($ext, $allowed)) {
            $cleanName = preg_replace("/[^a-zA-Z0-9.]/", "", basename($file['name']));
            $newName = time() . '_' . $cleanName;
            $dest = $uploadDir . $newName;

            if (move_uploaded_file($file['tmp_name'], $dest)) {
                $stmt = $pdo->prepare(
                    "UPDATE products SET name=?, description=?, price=?, img=? WHERE id=?"
                );
                return $stmt->execute([$name, $description, (float)$price, $dest, $id]);
            }
        }
    }

    $stmt = $pdo->prepare(
        "UPDATE products SET name=?, description=?, price=? WHERE id=?"
    );
    return $stmt->execute([$name, $description, (float)$price, $id]);
}

function deleteProduct($id) {
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT img FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $product = $stmt->fetch();

    if ($product && file_exists($product['img']) && strpos($product['img'], 'uploads/') !== false) {
        unlink($product['img']);
    }

    $pdo->prepare("DELETE FROM products WHERE id = ?")->execute([$id]);
}

// ==========================================
// 5. ORDER LOGIC
// ==========================================

function getOrders() {
    return getDB()->query("SELECT * FROM orders ORDER BY id DESC")->fetchAll();
}

function getUserOrders($userId) {
    $stmt = getDB()->prepare(
        "SELECT * FROM orders WHERE user_id = ? ORDER BY id DESC"
    );
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

function addOrder($productId, $customerName, $phone, $address, $userId) {
    $pdo = getDB();

    $stmt = $pdo->prepare("SELECT name FROM products WHERE id = ?");
    $stmt->execute([$productId]);
    $product = $stmt->fetch();

    $pName = $product ? $product['name'] : 'Unknown Product';

    $stmt = $pdo->prepare(
        "INSERT INTO orders (user_id, product_name, customer_name, contact, address, status)
         VALUES (?, ?, ?, ?, ?, 'Pending')"
    );
    $stmt->execute([$userId, $pName, htmlspecialchars($customerName), htmlspecialchars($phone), htmlspecialchars($address)]);
}

function updateOrderStatus($id, $status, $userId = null) {
    $pdo = getDB();

    if ($userId) {
        $stmt = $pdo->prepare("UPDATE orders SET status=? WHERE id=? AND user_id=?");
        $stmt->execute([$status, $id, $userId]);
    } else {
        $stmt = $pdo->prepare("UPDATE orders SET status=? WHERE id=?");
        $stmt->execute([$status, $id]);
    }
}

// ==========================================
// 6. OTP EMAIL (BREVO)
// ==========================================

function sendOtpViaBrevo($user_email, $otp_code) {

    $data = [
        "sender" => [
            "name"  => SENDER_NAME,
            "email" => SENDER_EMAIL
        ],
        "to" => [[ "email" => $user_email, "name" => "Admin User" ]],
        "subject" => "Your Password Recovery Code",
        "htmlContent" => "
            <div style='font-family: Arial, sans-serif; padding: 20px;'>
                <h2>Password Recovery</h2>
                <h1 style='letter-spacing:5px;'>{$otp_code}</h1>
                <p>This code expires in 10 minutes.</p>
            </div>"
    ];

    $ch = curl_init('https://api.brevo.com/v3/smtp/email');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => [
            'accept: application/json',
            'api-key: ' . BREVO_API_KEY,
            'content-type: application/json'
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if (curl_errno($ch)) {
        return "CURL Error: " . curl_error($ch);
    }

    curl_close($ch);

    return ($httpCode === 201) ? true : "API Error ($httpCode): $response";
}

// ==========================================
// 7. PASSWORD RESET
// ==========================================

function updatePasswordInDb($email_input, $plain_password) {
    $pdo = getDB();

    $hashed_password = password_hash($plain_password, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare(
        "UPDATE admins SET password = :pass WHERE username = :user"
    );

    return $stmt->execute([
        ':pass' => $hashed_password,
        ':user' => $email_input
    ]);
}
