<?php

include('db.php'); 


session_start();


if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id']; 


$stmt = $pdo->prepare("SELECT * FROM money_duo WHERE user1_id = ? OR user2_id = ?");
$stmt->execute([$user_id, $user_id]);
$money_duos = $stmt->fetchAll();


$contribution_histories = [];
foreach ($money_duos as $duo) {
    $stmt = $pdo->prepare("SELECT ch.amount, ch.contribution_date, u.email AS contributor_email 
                           FROM contribution_history ch
                           JOIN users u ON ch.user_id = u.id
                           WHERE ch.money_duo_id = ? ORDER BY ch.contribution_date DESC");
    $stmt->execute([$duo['id']]);
    $contribution_histories[$duo['id']] = $stmt->fetchAll();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contribution History - SaveWise</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <h1>Your Contribution History</h1>
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
        <h2>Your Money Duo Contribution History</h2>
        <?php if ($money_duos): ?>
            <ul>
                <?php foreach ($money_duos as $duo): ?>
                    <li>
                        <strong>Goal: <?php echo htmlspecialchars($duo['shared_goal']); ?></strong><br>
                        <ul>
                            <?php
                            if (isset($contribution_histories[$duo['id']])) {
                                $history = $contribution_histories[$duo['id']];
                                if ($history) {
                                    foreach ($history as $contribution) {
                                        echo "<li>";
                                        echo "Contributor: " . htmlspecialchars($contribution['contributor_email']) . "<br>";
                                        echo "Amount: $" . number_format($contribution['amount'], 2) . "<br>";
                                        echo "Date: " . $contribution['contribution_date'];
                                        echo "</li>";
                                    }
                                } else {
                                    echo "<p>No contributions yet.</p>";
                                }
                            }
                            ?>
                        </ul>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>You are not part of any Money Duo yet.</p>
        <?php endif; ?>
    </main>

    <footer>
        <p>&copy; 2025 SaveWise. All rights reserved.</p>
    </footer>
</body>
</html>
