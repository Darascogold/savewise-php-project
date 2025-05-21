<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Include database connection
    include "db.php";

    // Sanitize and validate inputs
    $username = filter_var(trim($_POST["username"]), FILTER_SANITIZE_STRING);
    $email = filter_var(trim($_POST["email"]), FILTER_SANITIZE_EMAIL);
    $password = $_POST["password"];

    // Check if the email already exists in the database
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        // Redirect back with an error if the email is already in use
        header("Location: signup.html?error=Email is already in use.");
        exit;
    }

    // Hash the password before storing it
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Prepare and execute the insert statement
    $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
    $success = $stmt->execute([$username, $email, $hashed_password]);

    // Redirect to login page or show an error message
    if ($success) {
        // Redirect to login page if registration is successful
        header("Location: login.html");
        exit;
    } else {
        // Redirect with an error message if something went wrong
        header("Location: signup.html?error=Something went wrong. Please try again.");
        exit;
    }
}
?>
