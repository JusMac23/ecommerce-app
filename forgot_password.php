<?php
// 1. Enable full debugging to screen (Remove this when you go live!)
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require 'functions/functions.php';

$error_msg = ""; 
$entered_email = ""; // Keep the email in the box if it fails

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $entered_email = $email; // Save for sticky form
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_msg = "Invalid email format.";
    } else {
        $otp = rand(100000, 999999);

        // Send Email via Brevo
        $result = sendOtpViaBrevo($email, $otp);

        // Check if strictly TRUE
        if ($result === true) {
            $_SESSION['reset_email'] = $email;
            $_SESSION['reset_otp'] = $otp; // Save OTP for verification step
            
            // UPDATE: Redirect to the OTP Verification page first
            header("Location: verify_otp.php"); 
            exit;
        } else {
            // FIX: Handle cases where the function returns boolean FALSE or an error string
            if (is_string($result)) {
                $detailed_error = $result;
            } else {
                $detailed_error = "Unknown error. Check Brevo API Key & Sender Email.";
            }
            
            $error_msg = "<strong>Failed to send:</strong><br>" . $detailed_error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forgot Password</title>
    <link href="https://fonts.googleapis.com/css?family=Google+Sans:400,500" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .login-box { font-family: 'Google Sans', sans-serif; }
        .form-control { width: 100%; padding: 10px; margin-bottom: 15px; box-sizing: border-box; font-family: 'Google Sans', sans-serif; }
        .btn-primary { width: 100%; padding: 10px; cursor: pointer; font-family: 'Google Sans', sans-serif; background-color: #007bff; color: white; border: none; border-radius: 4px;}
        .btn-primary:hover { background-color: #0056b3; }
        
        /* High Visibility Error Box */
        .error-box { 
            background-color: #ffe6e6; 
            border: 1px solid #d93025;
            color: #d93025; 
            padding: 15px; 
            margin-bottom: 20px; /* Space below box */
            border-radius: 4px; 
            font-size: 0.9em; 
            display: block; /* Ensure it's not hidden */
            text-align: left;
        }
    </style>
</head>
<body class="admin-body">
    <div class="login-container">
        <div class="login-box">
            <h2>Recovery</h2>
            <p style="color:#666; margin-bottom: 20px;">Enter your registered email address to receive an OTP.</p>

            <?php if (!empty($error_msg)): ?>
                <div class="error-box">
                    <?= $error_msg ?>
                </div>
            <?php endif; ?>
            
            <form method="POST">
                <input type="email" name="email" class="form-control" placeholder="name@example.com" value="<?= htmlspecialchars($entered_email) ?>" required>
                <button type="submit" class="btn-primary" style="border-radius: 6px;">Send OTP Code</button>
            </form>
            
            <br>
            <a href="admin.php" style="color:#666; font-size:0.9em; text-decoration: none;">← Back to Login</a>
        </div>
    </div>
</body>
</html>