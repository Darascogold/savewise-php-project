<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    include "db.php";

    
    $username = filter_var(trim($_POST["username"]), FILTER_SANITIZE_STRING);
    $email = filter_var(trim($_POST["email"]), FILTER_SANITIZE_EMAIL);
    $password = $_POST["password"];

    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        
        header("Location: signup.html?error=Email is already in use.");
        exit;
    }


    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    
    $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
    $success = $stmt->execute([$username, $email, $hashed_password]);

    
    if ($success) {
        
        header("Location: login.html");
        exit;
    } else {
        
        header("Location: signup.html?error=Something went wrong. Please try again.");
        exit;
    }
}
?>
