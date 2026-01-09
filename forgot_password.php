<?php

require 'functions/functions.php';

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phone = $_POST['phone'];
    
    // 1. Generate OTP
    $otp = requestOTP($phone);

    if ($otp) {
        // 2. Store Phone in Session so reset_password.php knows who we are
        $_SESSION['reset_phone'] = $phone;
        
        // 3. Redirect to verification page
        // We put the OTP in the URL strictly for testing so you can see it
        header("Location: reset_password.php?simulated_otp=" . $otp);
        exit;
    } else {
        $error = "Contact number not found.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forgot Password</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="admin-body">
    <div class="login-container">
        <div class="login-box">
            <h2>Recovery</h2>
            <p>Enter your registered contact number.</p>
            
            <form method="POST">
                <input type="text" name="phone" placeholder="e.g. 09123456789" required>
                <button type="submit" class="btn-primary w-100">Send OTP Code</button>
            </form>

            <?php if ($error): ?>
                <p class="error-msg" style="display:block;"><?= $error ?></p>
            <?php endif; ?>
            
            <br>
            <a href="admin.php" class="back-link-muted">← Back to Login</a>
        </div>
    </div>
</body>
</html>