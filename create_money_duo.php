<?php
session_start();
include "db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.html");
    exit();
}

$user_id = $_SESSION["user_id"];
$message = "";

// Send invitation logic
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["invite_user"])) {
    $invite_email = trim($_POST["invite_email"]);
    $shared_goal = trim($_POST["shared_goal"]);
    $target_amount = floatval($_POST["target_amount"]);

    if (!filter_var($invite_email, FILTER_VALIDATE_EMAIL)) {
        $message = "Invalid email address.";
    } else {
        try {
            // Check if invited user exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$invite_email]);
            $user2 = $stmt->fetch();

            if (!$user2) {
                $message = "User with that email does not exist.";
            } elseif ($user2["id"] == $user_id) {
                $message = "You cannot invite yourself.";
            } else {
                // Check for existing Money Duo
                $stmt = $pdo->prepare("SELECT * FROM money_duo WHERE 
                    (user1_id = ? AND user2_id = ?) OR (user1_id = ? AND user2_id = ?)");
                $stmt->execute([$user_id, $user2["id"], $user2["id"], $user_id]);
                $existing = $stmt->fetch();

                if ($existing) {
                    $message = "You already have a Money Duo with this user.";
                } else {
                    // Create new Money Duo
                    $stmt = $pdo->prepare("INSERT INTO money_duo (user1_id, user2_id, shared_goal, target_amount, saved_amount, locked, created_at) 
                                           VALUES (?, ?, ?, ?, 0, 0, NOW())");
                    $stmt->execute([$user_id, $user2["id"], $shared_goal, $target_amount]);

                    // Send invitation email
                    $subject = "Invitation to join a Money Duo";
                    $body = "You have been invited by user $user_id to join a Money Duo with the goal of $shared_goal and a target amount of $$target_amount.";
                    $headers = "From: no-reply@savewise.com\r\n";
                    if (!mail($invite_email, $subject, $body, $headers)) {
                        $message = "Invitation email could not be sent.";
                    }

                    $message = "Money Duo created successfully!";
                }
            }
        } catch (Exception $e) {
            $message = "Error: " . $e->getMessage();
            error_log($e->getMessage());
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Money Duo - SaveWise</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <h1>Create Money Duo</h1>
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
        <?php if (!empty($message)): ?>
            <p><strong><?php echo htmlspecialchars($message); ?></strong></p>
        <?php endif; ?>

        <!-- Invite Form -->
        <section>
            <h2>Invite a Partner to a Money Duo</h2>
            <form action="create_money_duo.php" method="post">
                <label for="invite_email">Partner's Email:</label>
                <input type="email" name="invite_email" id="invite_email" required>

                <label for="shared_goal">Goal Name:</label>
                <input type="text" name="shared_goal" id="shared_goal" required>

                <label for="target_amount">Target Amount:</label>
                <input type="number" name="target_amount" id="target_amount" min="1" step="0.01" required>

                <button type="submit" name="invite_user">Send Invite</button>
            </form>
        </section>
    </main>
</body>
</html>
