<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include "db.php";

// Check if user is logged in
if (!isset($_SESSION["user_id"])) {
    header("Location: login.html");
    exit();
}

$user_id = $_SESSION["user_id"];


if ($_SERVER["REQUEST_METHOD"] === "POST") {
    
    $title = isset($_POST["title"]) ? $_POST["title"] : '';
    $category = isset($_POST["category"]) ? $_POST["category"] : '';
    $target_amount = isset($_POST["target_amount"]) ? $_POST["target_amount"] : '';
    $description = isset($_POST["description"]) ? $_POST["description"] : '';
    $deadline = isset($_POST["deadline"]) ? $_POST["deadline"] : '';  

    
    if (!empty($title) && !empty($category) && !empty($target_amount)) {
        $status = 'active';

        $stmt = $pdo->prepare("INSERT INTO savings_plans (user_id, title, category, target_amount, description, deadline, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $title, $category, $target_amount, $description, $deadline, $status]);

        
        $success_message = "Your savings plan has been created successfully! Redirecting to your savings view...";

    
        header("refresh:2;url=view_savings.php");
        exit();
    } else {
        
        $error_message = "Please fill in all required fields (Title, Category, Target Amount).";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Savings Plan - SaveWise</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <h1>Create a New Savings Plan</h1>
        <nav>
            <ul>
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="view_savings.php">View Savings</a></li>
                <li><a href="view_money_duo.php">Money Duo</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <?php if (isset($success_message)): ?>
            <div class="success-message"><?php echo $success_message; ?></div>
        <?php elseif (isset($error_message)): ?>
            <div class="error-message"><?php echo $error_message; ?></div>
        <?php endif; ?>
        
        <h2>Create Your Savings Plan</h2>
        <form action="create_savings.php" method="post">
            <label for="title">Savings Plan Title:</label>
            <input type="text" name="title" id="title" required>

            <label for="category">Category (e.g., Food, Tuition):</label>
            <input type="text" name="category" id="category" required>

            <label for="target_amount">Target Amount ($):</label>
            <input type="number" name="target_amount" id="target_amount" required>

            <label for="description">Description (Optional):</label>
            <textarea name="description" id="description"></textarea>

            
            <label for="deadline">Deadline (yyyy-mm-dd):</label>
            <input type="date" name="deadline" id="deadline" required>

            <button type="submit">Create Plan</button>
        </form>
    </main>

    <footer>
        <p>&copy; 2025 SaveWise. All rights reserved.</p>
    </footer>
</body>
</html>
