<?php
session_start();
include "db.php";

// Check if user is logged in
if (!isset($_SESSION["user_id"])) {
    header("Location: login.html");
    exit();
}

$user_id = $_SESSION["user_id"];

// Unlock savings plans where the deadline has passed
$current_date = date('Y-m-d');
$stmt = $pdo->prepare("UPDATE savings_plans SET status = 'active' WHERE user_id = ? AND status = 'locked' AND deadline < ?");
$stmt->execute([$user_id, $current_date]);

// Handle delete or lock action
if (isset($_GET['action']) && isset($_GET['plan_id'])) {
    $plan_id = $_GET['plan_id'];

    if ($_GET['action'] === 'delete') {
        $stmt = $pdo->prepare("DELETE FROM savings_plans WHERE id = ? AND user_id = ?");
        $stmt->execute([$plan_id, $user_id]);
    } elseif ($_GET['action'] === 'lock') {
        $stmt = $pdo->prepare("UPDATE savings_plans SET status = 'locked' WHERE id = ? AND user_id = ?");
        $stmt->execute([$plan_id, $user_id]);
    }

    header("Location: view_savings.php");
    exit();
}

// Fetch all valid savings plans
$stmt = $pdo->prepare("SELECT * FROM savings_plans WHERE user_id = ? AND (status = 'active' OR status = 'locked')");
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
    <style>
        .locked-plan {
            opacity: 0.6;
            font-style: italic;
            color: #666;
        }
        .status-badge {
            font-size: 0.9em;
            font-weight: bold;
            padding: 2px 6px;
            border-radius: 5px;
            color: white;
            background-color: gray;
        }
        .status-active {
            background-color: green;
        }
        .status-locked {
            background-color: red;
        }
    </style>
</head>
<body>
    <header>
        <h1>Your Savings Plans</h1>
        <nav>
            <ul>
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="create_savings.php">Create Savings Plan</a></li>
                <li><a href="view_money_duo.php">Money Duo</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <h2>Your Existing Savings Plans</h2>
        <?php if ($plans): ?>
            <ul>
                <?php foreach ($plans as $plan): ?>
                    <li class="<?php echo ($plan['status'] === 'locked') ? 'locked-plan' : ''; ?>">
                        <strong><?php echo htmlspecialchars($plan['title']); ?></strong><br>
                        Category: <?php echo htmlspecialchars($plan['category']); ?><br>
                        Target Amount: $<?php echo number_format($plan['target_amount'], 2); ?><br>
                        Description: <?php echo htmlspecialchars($plan['description']); ?><br>
                        Deadline: <?php echo $plan['deadline']; ?><br>
                        Status:
                        <span class="status-badge <?php echo 'status-' . htmlspecialchars($plan['status']); ?>">
                            <?php echo ucfirst($plan['status']); ?>
                        </span><br>

                        <!-- Actions -->
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
