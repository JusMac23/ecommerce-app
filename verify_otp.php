<?php

session_start();

$error = "";

// Security: Kick them out if they didn't come from forgot_password.php
if (!isset($_SESSION['reset_email']) || !isset($_SESSION['reset_otp'])) {
    header("Location: forgot_password.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $entered_otp = trim($_POST['otp']);

    // Check if OTP matches the one in session
    if ($entered_otp == $_SESSION['reset_otp']) {
        // SUCCESS: Mark as verified
        $_SESSION['otp_verified'] = true;
        
        // Redirect to step 2
        header("Location: new_password.php");
        exit;
    } else {
        $error = "Invalid OTP Code. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Verify OTP</title>
    <link href="https://fonts.googleapis.com/css?family=Google+Sans:400,500" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .login-box { font-family: 'Google Sans', sans-serif; }
        .form-control { width: 100%; padding: 10px; margin-bottom: 15px; box-sizing: border-box; font-family: 'Google Sans', sans-serif; text-align: center; letter-spacing: 5px; font-size: 1.2rem;}
        .btn-primary { width: 100%; padding: 10px; cursor: pointer; font-family: 'Google Sans', sans-serif; background-color: #007bff; color: white; border: none; border-radius: 4px;}
        .error-msg { color: #d93025; margin-top: 10px; text-align: center; display: block;}
    </style>
</head>
<body class="admin-body">
    <div class="login-container">
        <div class="login-box">
            <h2>Enter Code</h2>
            <p style="color:#666; margin-bottom: 20px;">
                We sent a code to <strong><?= htmlspecialchars($_SESSION['reset_email']); ?></strong>
            </p>

            <form method="POST">
                <input type="text" name="otp" class="form-control" placeholder="######" maxlength="6" required autocomplete="off">
                <button type="submit" class="btn-primary" style="border-radius: 6px;">Verify Code</button>
            </form>

            <?php if ($error): ?>
                <p class="error-msg"><?= $error ?></p>
            <?php endif; ?>
            
            <br>
            <a href="forgot_password.php" style="color:#666; font-size:0.9em;">← Resend Code</a>
        </div>
    </div>
</body>
</html>