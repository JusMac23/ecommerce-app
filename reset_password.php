<?php
session_start();
require 'functions.php';

// FIX: If the session doesn't have the phone number, send them back to the start.
// This prevents the "Invalid Request" error.
if (!isset($_SESSION['reset_phone'])) {
    header("Location: forgot_password.php");
    exit;
}

$phone = $_SESSION['reset_phone'];
$error = "";
$success = false;
$simulatedOTP = $_GET['simulated_otp'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $otpInput = $_POST['otp'];
    $newPass = $_POST['new_password'];
    $confirmPass = $_POST['confirm_password'];

    if ($newPass === $confirmPass) {
        // Verify OTP using your function
        if (verifyResetOTP($phone, $otpInput, $newPass)) {
            $success = true;
            // Clear session so they can't reuse this verification
            unset($_SESSION['reset_phone']);
        } else {
            $error = "Invalid or expired OTP code.";
        }
    } else {
        $error = "Passwords do not match.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Verify OTP</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="admin-body">
    <div class="login-container">
        <div class="login-box">
            
            <?php if ($success): ?>
                <h2 style="color: var(--accent);">Success!</h2>
                <p>Your password has been reset.</p>
                <br>
                <a href="admin.php" class="btn-primary w-100" style="display:block; text-decoration:none;">Login Now</a>
            
            <?php else: ?>
                
                <h2>Verify OTP</h2>
                <p>We sent a code to: <strong><?= htmlspecialchars($phone) ?></strong></p>

                <?php if($simulatedOTP): ?>
                <div style="background:#ecfdf5; border:1px solid #10b981; padding:10px; border-radius:8px; margin-bottom:15px; color:#065f46;">
                    <strong>🔔 SMS Simulation:</strong><br>
                    Your OTP is: <strong><?= $simulatedOTP ?></strong>
                </div>
                <?php endif; ?>

                <form method="POST">
                    <label style="font-size:0.85rem; color:#64748b; float:left; width:100%; text-align:left;">Enter OTP Code</label>
                    <input type="text" name="otp" placeholder="e.g. 123456" required style="text-align:center; letter-spacing:2px; font-weight:bold;">
                    
                    <label style="font-size:0.85rem; color:#64748b; float:left; width:100%; text-align:left;">New Password</label>
                    <input type="password" name="new_password" placeholder="New Password" required>
                    <input type="password" name="confirm_password" placeholder="Confirm Password" required>
                    
                    <button type="submit" class="btn-primary w-100">Reset Password</button>
                </form>

                <?php if ($error): ?>
                    <p class="error-msg" style="display:block;"><?= $error ?></p>
                <?php endif; ?>
                
                <br>
                <a href="forgot_password.php" class="back-link-muted">Resend Code</a>

            <?php endif; ?>
            
        </div>
    </div>
</body>
</html>