<?php
session_start();

// Check if the form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Include the database connection
    require_once "db.php";

    // Sanitize user inputs to prevent SQL injection
    $email = filter_var(trim($_POST["email"]), FILTER_SANITIZE_EMAIL);
    $password = $_POST["password"];

    // Prepare the query to fetch user data based on the email
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    // Check if the user exists and verify the password
    if ($user && password_verify($password, $user["password"])) {
        // Start the session and set session variables
        $_SESSION["user_id"] = $user["id"];
        $_SESSION["username"] = $user["username"];

        // Redirect to the dashboard after successful login
        header("Location: dashboard.php");
        exit;
    } else {
        // Redirect with error message if login fails
        header("Location: login.html?error=Invalid email or password");
        exit;
    }
}
?>
