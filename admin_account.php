<?php
session_start();

// --- 1. SINGLE DATABASE CONNECTION SOURCE ---
// We simply include the existing connection file. 
// No hardcoded passwords here.
require_once __DIR__ . '/database/connection.php';

// --- 2. STANDARDIZE CONNECTION VARIABLE ---
// Your connection.php might name the variable $conn or use a function getDB().
// This block ensures the rest of this script can use "$pdo" regardless of what you named it there.
if (!isset($pdo)) {
    if (isset($conn)) {
        $pdo = $conn; 
    } elseif (function_exists('getDB')) {
        $pdo = getDB();
    } else {
        die("Error: Database connection file loaded, but no connection variable ($conn or $pdo) was found.");
    }
}

// --- 3. AUTHENTICATION CHECK ---
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

$admin_id = $_SESSION['admin_id'];
$message = '';
$error = '';

// --- 4. HANDLE FORM SUBMISSION ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // A. Update Profile Info
    if (isset($_POST['update_profile'])) {
        $username = trim($_POST['username']);
        // Note: Ensure your database column is named 'contact_number' or change this variable
        $contact = trim($_POST['contact_number']); 

        if (!empty($username) && !empty($contact)) {
            // Update query uses contact_number based on your HTML form
            $sql = "UPDATE admins SET username = ?, contact_number = ? WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            
            // We wrap this in a try-catch to handle errors (like if column name is wrong)
            try {
                if ($stmt->execute([$username, $contact, $admin_id])) {
                    $message = "Profile details updated successfully!";
                    $_SESSION['admin_username'] = $username; 
                } else {
                    $error = "Failed to update profile.";
                }
            } catch (PDOException $e) {
                $error = "Database Error: " . $e->getMessage();
            }
        } else {
            $error = "All fields are required.";
        }
    }

    // B. Change Password
    if (isset($_POST['change_password'])) {
        $current_pass = $_POST['current_password'];
        $new_pass = $_POST['new_password'];
        $confirm_pass = $_POST['confirm_password'];

        if (!empty($current_pass) && !empty($new_pass)) {
            $stmt = $pdo->prepare("SELECT password FROM admins WHERE id = ?");
            $stmt->execute([$admin_id]);
            $user = $stmt->fetch();

            if ($user && password_verify($current_pass, $user['password'])) {
                if ($new_pass === $confirm_pass) {
                    $new_hashed_pass = password_hash($new_pass, PASSWORD_DEFAULT);
                    $updateStmt = $pdo->prepare("UPDATE admins SET password = ? WHERE id = ?");
                    if ($updateStmt->execute([$new_hashed_pass, $admin_id])) {
                        $message = "Password changed successfully!";
                    } else {
                        $error = "Error updating password.";
                    }
                } else {
                    $error = "New passwords do not match.";
                }
            } else {
                $error = "Incorrect current password.";
            }
        } else {
            $error = "Please fill in all password fields.";
        }
    }
}

// --- 5. FETCH CURRENT DATA ---
$stmt = $pdo->prepare("SELECT * FROM admins WHERE id = ?");
$stmt->execute([$admin_id]);
$admin = $stmt->fetch();

if (!$admin) {
    session_destroy();
    header("Location: login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Account - Admin Panel</title>
    <link rel="stylesheet" href="css/admin_account.css">
</head>
<body>

<div class="account-container">
    <div style="text-align: right;">
        <a href="admin.php" class="close-btn" aria-label="Close">&times;</a>
    </div>
    <h2>Account Information</h2>

    <?php if ($message): ?>
        <div class="alert success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="form-group">
            <label>Username</label>
            <input type="text" name="username" value="<?= htmlspecialchars($admin['username'] ?? '') ?>" required>
        </div>
        <div class="form-group">
            <label>Contact Number</label>
            <input type="text" name="contact_number" value="<?= htmlspecialchars($admin['contact_number'] ?? '') ?>" required>
        </div>
        <button type="submit" name="update_profile">Update Profile</button>
    </form>

    <hr>

    <form method="POST" action="">
        <h3>Change Password</h3>
        <div class="form-group">
            <label>Current Password</label>
            <input type="password" name="current_password" required>
        </div>
        <div class="form-group">
            <label>New Password</label>
            <input type="password" name="new_password" required>
        </div>
        <div class="form-group">
            <label>Confirm New Password</label>
            <input type="password" name="confirm_password" required>
        </div>
        <button type="submit" name="change_password" style="background-color: #007bff;">Change Password</button>
    </form>
</div>

</body>
</html>