<?php
// Include database connection
require_once __DIR__ . '/database/connection.php'; 

// === FIX: Call the function to get the connection ===
$conn = getDB(); 
// ====================================================

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // 1. Sanitize input data
    $name = htmlspecialchars(strip_tags($_POST['name']));
    $email = htmlspecialchars(strip_tags($_POST['email']));
    $message = htmlspecialchars(strip_tags($_POST['message']));

    // 2. Insert into Database
    $sql = "INSERT INTO messages (name, email, message) VALUES (:name, :email, :message)";
    
    try {
        $stmt = $conn->prepare($sql);
        
        // Bind parameters
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':message', $message);
        
        $stmt->execute();

        echo "<script>alert('Message sent successfully!'); window.location.href='index.php';</script>";
        
    } catch(PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
} else {
    header("Location: index.html");
}
?>