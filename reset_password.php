<?php
require 'functions.php';

$token = $_GET['token'] ?? '';
$success = false;
$error = "";

// 1. Check if token is present
if (!$token) {
    die("Invalid Request.");
}

// 2. Handle Password Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newPass = $_POST['new_password'];
    $confirmPass = $_POST['confirm_password'];

    if ($newPass === $confirmPass) {
        if (resetAdminPassword($token, $newPass)) {
            $success = true;
        } else {
            $error = "Invalid or expired token.";
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
    <title>Reset Password</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="admin-body">
    <div class="login-container">
        <div class="login-box">
            
            <?php if ($success): ?>
                <h2 style="color: var(--accent);">Success!</h2>
                <p>Your password has been updated.</p>
                <a href="admin.php" class="btn-primary w-100" style="display:block; text-decoration:none;">Login Now</a>
            
            <?php else: ?>
                
                <h2>Reset Password</h2>
                <p>Enter your new password below.</p>
                
                <form method="POST">
                    <input type="password" name="new_password" placeholder="New Password" required>
                    <input type="password" name="confirm_password" placeholder="Confirm Password" required>
                    <button type="submit" class="btn-primary w-100">Update Password</button>
                </form>

                <?php if ($error): ?>
                    <p class="error-msg" style="display:block;"><?= $error ?></p>
                <?php endif; ?>

            <?php endif; ?>
            
        </div>
    </div>
</body>
</html>