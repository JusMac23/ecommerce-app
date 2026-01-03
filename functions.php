<?php
// ==========================================
// 1. CONFIGURATION
// ==========================================
define('ADMIN_PHONE', '1234567890'); 
define('UPLOAD_DIR', 'uploads/'); 

// Database Credentials
define('DB_HOST', 'localhost');
define('DB_NAME', 'souvenir_shop');
define('DB_USER', 'root');
define('DB_PASS', 'CD@it1432'); 

// Ensure upload directory exists
if (!file_exists(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0777, true);
}

function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $pdo = new PDO($dsn, DB_USER, DB_PASS);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            die("Database Connection Failed: " . $e->getMessage());
        }
    }
    return $pdo;
}

// ==========================================
// 2. AUTHENTICATION & PASSWORD RESET
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

// Ensure Admin Table Exists (Updated with new columns)
function ensureAdminTableExists() {
    $pdo = getDB();
    // Logic to create table if it doesn't exist...
    $pdo->exec("CREATE TABLE IF NOT EXISTS admins (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        reset_token VARCHAR(255) NULL,
        reset_expires DATETIME NULL
    )");

    $stmt = $pdo->query("SELECT count(*) FROM admins");
    if ($stmt->fetchColumn() == 0) {
        $defaultPass = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO admins (username, password) VALUES ('admin', ?)");
        $stmt->execute([$defaultPass]);
    }
}
ensureAdminTableExists();

// NEW: Generate Token for Reset
function generateResetToken($username) {
    $pdo = getDB();
    
    // Check if user exists
    $stmt = $pdo->prepare("SELECT id FROM admins WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user) {
        // Generate random token
        $token = bin2hex(random_bytes(32));
        // Set expiry (1 hour from now)
        $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));

        // Update DB
        $update = $pdo->prepare("UPDATE admins SET reset_token = ?, reset_expires = ? WHERE username = ?");
        $update->execute([$token, $expiry, $username]);

        return $token; // Return token to display/email
    }
    return false;
}

// NEW: Process the Password Reset
function resetAdminPassword($token, $newPassword) {
    $pdo = getDB();
    $now = date('Y-m-d H:i:s');

    // Find user with this token and ensure it hasn't expired
    $stmt = $pdo->prepare("SELECT id FROM admins WHERE reset_token = ? AND reset_expires > ?");
    $stmt->execute([$token, $now]);
    $user = $stmt->fetch();

    if ($user) {
        // Hash new password
        $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
        
        // Update password and clear token
        $update = $pdo->prepare("UPDATE admins SET password = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?");
        $update->execute([$newHash, $user['id']]);
        
        return true;
    }
    return false;
}

// ==========================================
// 3. PRODUCT FUNCTIONS
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

        $allowedfileExtensions = array('jpg', 'gif', 'png', 'jpeg', 'webp');
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if (in_array($fileExtension, $allowedfileExtensions)) {
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
// 4. ORDER FUNCTIONS
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

    $sql = "INSERT INTO orders (product_name, customer_name, contact, address, status) VALUES (?, ?, ?, ?, 'Pending')";
    $stmtInsert = $pdo->prepare($sql);
    $stmtInsert->execute([$pName, htmlspecialchars($customerName), htmlspecialchars($phone), htmlspecialchars($address)]);

    return [
        'id' => $pdo->lastInsertId(),
        'productName' => $pName,
        'customerName' => htmlspecialchars($customerName),
        'address' => htmlspecialchars($address)
    ];
}

function updateOrderStatus($id, $status) {
    $pdo = getDB();
    $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->execute([$status, $id]);
}
?>