<?php
require 'functions.php';

$message = "";
$resetLink = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    
    // Generate Token
    $token = generateResetToken($username);

    if ($token) {
        // Since we are on localhost, we cannot easily send emails.
        // We will display the link directly on screen for testing.
        $message = "Recovery mode initiated.";
        $resetLink = "reset_password.php?token=" . $token;
    } else {
        $message = "Username not found.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forgot Password</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="admin-body">
    <div class="login-container">
        <div class="login-box">
            <h2>Recovery</h2>
            <p>Enter your username to reset password.</p>
            
            <form method="POST">
                <input type="text" name="username" placeholder="Enter Username" required>
                <button type="submit" class="btn-primary w-100">Request Reset</button>
            </form>

            <?php if ($message): ?>
                <p class="error-msg" style="display:block; color: #333; background: #e2e8f0; padding: 10px; border-radius: 6px; margin-top:15px;">
                    <?= $message ?>
                </p>
            <?php endif; ?>

            <?php if ($resetLink): ?>
                <div style="margin-top: 15px; padding: 15px; background: #ecfdf5; border: 1px solid #10b981; border-radius: 8px;">
                    <strong>Simulated Email:</strong><br>
                    <p style="font-size: 0.9rem; margin: 5px 0;">Click below to reset your password:</p>
                    <a href="<?= $resetLink ?>" class="btn-confirm" style="display:block; text-align:center; text-decoration:none; margin-top:5px;">Reset Password Now</a>
                </div>
            <?php endif; ?>
            
            <br>
            <a href="admin.php" class="back-link-muted">← Back to Login</a>
        </div>
    </div>
</body>
</html>