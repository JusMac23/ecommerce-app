<?php
// ==========================================
// 1. CONFIGURATION
// ==========================================
define('ADMIN_PHONE', '09458739896'); 
define('UPLOAD_DIR', 'uploads/'); 

// Database Credentials
define('DB_HOST', 'localhost');
define('DB_NAME', 'souvenir_shop');
define('DB_USER', 'root');
define('DB_PASS', 'CD@it1432'); 

if (!file_exists(UPLOAD_DIR)) { mkdir(UPLOAD_DIR, 0777, true); }

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