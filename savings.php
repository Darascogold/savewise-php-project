<?php
session_start();
include "db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.html");
    exit();
}

$user_id = $_SESSION["user_id"];


if ($_SERVER["REQUEST_METHOD"] === "POST") {
    
    $title = $_POST["title"];
    $category = $_POST["category"];
    $target_amount = $_POST["target_amount"];
    $description = $_POST["description"];

    
    $stmt = $pdo->prepare("INSERT INTO savings_plans (user_id, title, category, target_amount, description) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$user_id, $title, $category, $target_amount, $description]);


    header("Location: view_savings.php");
    exit();
}


$stmt = $pdo->prepare("SELECT * FROM savings_plans WHERE user_id = ?");
$stmt->execute([$user_id]);
$plans = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Savings - SaveWise</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <h1>Your Savings Plans</h1>
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
        <h2>Create a New Savings Plan</h2>
        <form action="savings.php" method="post">
            <label for="title">Savings Plan Title:</label>
            <input type="text" name="title" id="title" required>

            <label for="category">Category (e.g., Food, Tuition):</label>
            <input type="text" name="category" id="category" required>

            <label for="target_amount">Target Amount ($):</label>
            <input type="number" name="target_amount" id="target_amount" required>

            <label for="description">Description (Optional):</label>
            <textarea name="description" id="description"></textarea>

            <button type="submit">Create Plan</button>
        </form>

        <h2>Your Existing Savings Plans</h2>
        <?php if ($plans): ?>
            <ul>
                <?php foreach ($plans as $plan): ?>
                    <li>
                        <strong><?php echo htmlspecialchars($plan['title']); ?></strong><br>
                        Category: <?php echo htmlspecialchars($plan['category']); ?><br>
                        Target Amount: $<?php echo number_format($plan['target_amount'], 2); ?><br>
                        Description: <?php echo htmlspecialchars($plan['description']); ?><br>
                        Status: <?php echo htmlspecialchars($plan['status']); ?><br>

                        
                        <?php if ($plan['status'] !== 'locked'): ?>
                            <a href="view_savings.php?action=lock&plan_id=<?php echo $plan['id']; ?>">Lock</a> |
                        <?php endif; ?>
                        <a href="view_savings.php?action=delete&plan_id=<?php echo $plan['id']; ?>" onclick="return confirm('Are you sure you want to delete this savings plan?')">Delete</a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>You have no savings plans yet.</p>
        <?php endif; ?>
    </main>

    <footer>
        <p>&copy; 2025 SaveWise. All rights reserved.</p>
    </footer>
</body>
</html>
