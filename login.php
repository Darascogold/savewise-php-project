<?php
session_start();


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    require_once "db.php";

    
    $email = filter_var(trim($_POST["email"]), FILTER_SANITIZE_EMAIL);
    $password = $_POST["password"];

    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    
    if ($user && password_verify($password, $user["password"])) {
        
        $_SESSION["user_id"] = $user["id"];
        $_SESSION["username"] = $user["username"];

        
        header("Location: dashboard.php");
        exit;
    } else {
        
        header("Location: login.html?error=Invalid email or password");
        exit;
    }
}
?>
