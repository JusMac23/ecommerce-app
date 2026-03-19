<?php

session_start();

require 'functions/functions.php';

$error = "";
$success = "";

// Security: Ensure email is set AND OTP was actually verified in the previous step
if (!isset($_SESSION['reset_email']) || !isset($_SESSION['otp_verified']) || $_SESSION['otp_verified'] !== true) {
    header("Location: verify_otp.php"); // Send them back if they skipped logic
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if ($new_password !== $confirm_password) {
        $error = "Passwords do not match.";
    } elseif (strlen($new_password) < 6) {
        $error = "Password must be at least 6 characters.";
    } else {
        // Update Database
        $email = $_SESSION['reset_email'];
        
        if (updatePasswordInDb($email, $new_password)) {
            $success = "Password updated successfully!";
            
            // CLEAN UP SESSION (Very Important)
            session_destroy(); 
            
            // Redirect to login
            header("refresh:2;url=admin.php");
        } else {
            $error = "Database error. Could not update password.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>New Password</title>
    <link href="https://fonts.googleapis.com/css?family=Google+Sans:400,500" rel="stylesheet">
    <link rel="stylesheet" href="css/forgot_password_style.css">
</head>
<body class="admin-body">
    <div class="login-container">
        <div class="login-box">
            
            <?php if ($success): ?>
                <div class="success-msg">
                    <h2>Success!</h2>
                    <p><?= $success ?></p>
                    <small>Redirecting to login...</small>
                </div>
            <?php else: ?>
                <h2>New Password</h2>
                <p style="color:#666; margin-bottom: 20px;">Create a new secure password.</p>

                <form method="POST">
                    <input type="password" name="new_password" class="form-control" placeholder="New Password" required>
                    <input type="password" name="confirm_password" class="form-control" placeholder="Confirm Password" required>
                    <button type="submit" class="btn-primary" style="border-radius: 6px;">Update Password</button>
                </form>

                <?php if ($error): ?>
                    <p class="error-msg"><?= $error ?></p>
                <?php endif; ?>
            <?php endif; ?>

        </div>
    </div>
</body>
</html>