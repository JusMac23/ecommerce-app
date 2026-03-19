<?php
// 1. Enable full debugging to screen
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require 'functions/functions.php';

$error_msg = ""; 
$entered_email = ""; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $entered_email = $email; 
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_msg = "Invalid email format.";
    } else {
        if (!function_exists('getDB')) { 
            require_once __DIR__ . '/database/connection.php'; 
        }
        
        try {
            $pdo = getDB();
            // Force PDO to show exact errors if something is wrong
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // --- THE FIX IS HERE ---
            // If your database column is named 'email', change 'username' back to 'email' below.
            // If you are trying to reset a CUSTOMER password, change 'admins' to your users table name!
            $stmt = $pdo->prepare("SELECT id FROM admins WHERE username = ?");
            $stmt->execute([$email]);
            $adminRecord = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($adminRecord) {
                // RECORD WAS FOUND! Proceeding to send OTP...
                $otp = rand(100000, 999999);
                $result = sendOtpViaBrevo($email, $otp);

                if ($result === true) {
                    $_SESSION['reset_email'] = $email;
                    $_SESSION['reset_otp'] = $otp; 
                    
                    header("Location: verify_otp.php"); 
                    exit;
                } else {
                    if (is_string($result)) {
                        $detailed_error = $result;
                    } else {
                        $detailed_error = "Unknown error. Check Brevo API Key & Sender Email.";
                    }
                    $error_msg = "<strong>Record found, but Email Failed to send:</strong><br>" . $detailed_error;
                }
            } else {
                // Query succeeded, but no matching record was found
                $error_msg = "We couldn't find an account associated with that email address.";
            }
            
        } catch (PDOException $e) {
            // If the database crashes (e.g., wrong column name), it will tell you exactly what is wrong here
            $error_msg = "<strong>Database Error:</strong> " . $e->getMessage();
        } catch (Exception $e) {
            $error_msg = "<strong>System Error:</strong> " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>
    <link href="https://fonts.googleapis.com/css2?family=Google+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/forgot_password_style.css">
</head>
<body class="admin-body">
    <div class="login-container">
        <div class="login-box">
            <h2>Recovery</h2>
            <p>Enter your registered email address.</p>

            <?php if (!empty($error_msg)): ?>
                <div class="error-box">
                    <?= $error_msg ?>
                </div>
            <?php endif; ?>
            
            <form method="POST">
                <input type="email" name="email" class="form-control" placeholder="name@example.com" value="<?= htmlspecialchars($entered_email) ?>" required>
                <button type="submit" class="btn-primary">Send OTP Code</button>
            </form>
            
            <a href="admin.php" class="back-link">← Back to Login</a>
        </div>
    </div>
</body>
</html>