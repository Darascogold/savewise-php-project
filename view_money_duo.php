<?php
session_start();
include "db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.html");
    exit();
}

$user_id = $_SESSION["user_id"];
$message = "";


if ($_SERVER["REQUEST_METHOD"] === "POST") {
    
    if (isset($_POST["contribute_amount"], $_POST["money_duo_id"])) {
        $contribute_amount = floatval($_POST["contribute_amount"]);
        $money_duo_id = intval($_POST["money_duo_id"]);

        if ($contribute_amount > 0) {
            $stmt = $pdo->prepare("SELECT saved_amount FROM money_duo WHERE id = ?");
            $stmt->execute([$money_duo_id]);
            $money_duo = $stmt->fetch();

            if ($money_duo) {
                $new_saved_amount = $money_duo["saved_amount"] + $contribute_amount;
                $stmt = $pdo->prepare("UPDATE money_duo SET saved_amount = ? WHERE id = ?");
                $stmt->execute([$new_saved_amount, $money_duo_id]);

                $stmt = $pdo->prepare("INSERT INTO contribution_history (money_duo_id, user_id, amount) VALUES (?, ?, ?)");
                $stmt->execute([$money_duo_id, $user_id, $contribute_amount]);

            
                $stmt = $pdo->prepare("SELECT email FROM users WHERE id = ?");
                $stmt->execute([$user_id]);
                $user_email = $stmt->fetchColumn();

                $stmt = $pdo->prepare("SELECT user2_id FROM money_duo WHERE id = ?");
                $stmt->execute([$money_duo_id]);
                $partner_id = $stmt->fetchColumn();

                $stmt = $pdo->prepare("SELECT email FROM users WHERE id = ?");
                $stmt->execute([$partner_id]);
                $partner_email = $stmt->fetchColumn();

                
                $subject = "New Contribution to Your Money Duo";
                $message_body = "User $user_id has contributed $$contribute_amount to the Money Duo!";
                $headers = "From: no-reply@savewise.com\r\n";
                mail($user_email, $subject, $message_body, $headers);
                mail($partner_email, $subject, $message_body, $headers);

                $message = "Your contribution of $" . number_format($contribute_amount, 2) . " has been added!";
            } else {
                $message = "Invalid Money Duo selected.";
            }
        } else {
            $message = "Please enter a valid contribution amount.";
        }
    }

    
    if (isset($_POST["delete_money_duo_id"])) {
        $money_duo_id = intval($_POST["delete_money_duo_id"]);

        
        $stmt = $pdo->prepare("DELETE FROM contribution_history WHERE money_duo_id = ?");
        $stmt->execute([$money_duo_id]);

        $stmt = $pdo->prepare("DELETE FROM money_duo_invitations WHERE money_duo_id = ?");
        $stmt->execute([$money_duo_id]);

        $stmt = $pdo->prepare("DELETE FROM money_duo WHERE id = ? AND (user1_id = ? OR user2_id = ?)");
        $stmt->execute([$money_duo_id, $user_id, $user_id]);

        $message = "Money Duo deleted successfully.";
    }

    
    if (isset($_POST["invite_user"])) {
        $invite_email = trim($_POST["invite_email"]);
        $shared_goal = trim($_POST["shared_goal"]);
        $target_amount = floatval($_POST["target_amount"]);

        if (!filter_var($invite_email, FILTER_VALIDATE_EMAIL)) {
            $message = "Invalid email address.";
        } else {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$invite_email]);
            $user2 = $stmt->fetch();

            if (!$user2) {
                $message = "User with that email does not exist.";
            } elseif ($user2["id"] == $user_id) {
                $message = "You cannot invite yourself.";
            } else {
                $stmt = $pdo->prepare("SELECT * FROM money_duo WHERE 
                    (user1_id = ? AND user2_id = ?) OR (user1_id = ? AND user2_id = ?)");
                $stmt->execute([$user_id, $user2["id"], $user2["id"], $user_id]);
                $existing = $stmt->fetch();

                if ($existing) {
                    $message = "You already have a Money Duo with this user.";
                } else {
                    $stmt = $pdo->prepare("INSERT INTO money_duo 
                        (user1_id, user2_id, shared_goal, target_amount, saved_amount, locked, created_at) 
                        VALUES (?, ?, ?, ?, 0, 0, NOW())");
                    $stmt->execute([$user_id, $user2["id"], $shared_goal, $target_amount]);

                    $message = "Money Duo created and invitation sent!";
                    $subject = "Invitation to Join a Money Duo";
                    $body = "You've been invited by user $user_id to save towards: '$shared_goal' with a goal of $$target_amount.";
                    $headers = "From: no-reply@savewise.com\r\n";
                    mail($invite_email, $subject, $body, $headers);
                }
            }
        }
    }

    
    if (isset($_POST["accept_invitation"], $_POST["money_duo_id"])) {
        $money_duo_id = intval($_POST["money_duo_id"]);
        $action = $_POST["accept_invitation"];

        if ($action === "accept") {
            $stmt = $pdo->prepare("INSERT INTO money_duo_invitations (money_duo_id, invited_user_id, status) VALUES (?, ?, 'accepted')");
            $stmt->execute([$money_duo_id, $user_id]);

            $stmt = $pdo->prepare("SELECT COUNT(*) FROM money_duo_invitations WHERE money_duo_id = ? AND status = 'accepted'");
            $stmt->execute([$money_duo_id]);
            $accepted_count = $stmt->fetchColumn();

            if ($accepted_count == 2) {
                $stmt = $pdo->prepare("UPDATE money_duo SET locked = 1 WHERE id = ?");
                $stmt->execute([$money_duo_id]);
            }

            $message = "You have successfully accepted the Money Duo invitation!";
        } elseif ($action === "decline") {
            $stmt = $pdo->prepare("DELETE FROM money_duo_invitations WHERE money_duo_id = ? AND invited_user_id = ?");
            $stmt->execute([$money_duo_id, $user_id]);

            $message = "You have declined the Money Duo invitation.";
        }
    }

    
    if (isset($_POST["lock_action"], $_POST["money_duo_id"])) {
        $money_duo_id = intval($_POST["money_duo_id"]);
        $action = $_POST["lock_action"];
        $lock_value = $action === "lock" ? 1 : 0;

        $stmt = $pdo->prepare("UPDATE money_duo SET locked = ? WHERE id = ?");
        $stmt->execute([$lock_value, $money_duo_id]);

        $message = $lock_value ? "Money Duo locked." : "Money Duo unlocked.";
    }
}


$stmt = $pdo->prepare("SELECT * FROM money_duo WHERE user1_id = ? OR user2_id = ?");
$stmt->execute([$user_id, $user_id]);
$money_duos = $stmt->fetchAll();


$stmt = $pdo->prepare("SELECT md.shared_goal, mdi.money_duo_id FROM money_duo_invitations mdi 
                       JOIN money_duo md ON md.id = mdi.money_duo_id
                       WHERE mdi.invited_user_id = ? AND mdi.status = 'pending'");
$stmt->execute([$user_id]);
$invitations = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Your Money Duo - SaveWise</title>
    <link rel="stylesheet" href="style.css">
    <script>
        function confirmDelete() {
            return confirm("Are you sure you want to delete this Money Duo? This action cannot be undone.");
        }
    </script>
</head>
<body>
<header>
    <h1>Your Money Duo</h1>
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

    <section>
        <h2>Pending Invitations</h2>
        <?php if ($invitations): ?>
            <ul>
                <?php foreach ($invitations as $invitation): ?>
                    <li>
                        Invitation to join goal: <strong><?php echo htmlspecialchars($invitation["shared_goal"]); ?></strong>
                        <form action="view_money_duo.php" method="post">
                            <input type="hidden" name="money_duo_id" value="<?php echo $invitation["money_duo_id"]; ?>">
                            <button type="submit" name="accept_invitation" value="accept">Accept</button>
                            <button type="submit" name="accept_invitation" value="decline">Decline</button>
                        </form>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>No pending invitations.</p>
        <?php endif; ?>
    </section>

    <section>
        <h2>Create New Money Duo</h2>
        <form action="view_money_duo.php" method="post">
            <label for="invite_email">Partner's Email:</label>
            <input type="email" name="invite_email" id="invite_email" required>

            <label for="shared_goal">Goal Name:</label>
            <input type="text" name="shared_goal" id="shared_goal" required>

            <label for="target_amount">Target Amount:</label>
            <input type="number" name="target_amount" id="target_amount" step="0.01" required>

            <button type="submit" name="invite_user">Send Invite</button>
        </form>
    </section>

    <?php if ($money_duos): ?>
        <h2>Your Money Duos</h2>
        <ul>
            <?php foreach ($money_duos as $duo): ?>
                <li>
                    <strong><?php echo htmlspecialchars($duo["shared_goal"]); ?></strong> |
                    Target: $<?php echo number_format($duo["target_amount"], 2); ?> |
                    Saved: $<?php echo number_format($duo["saved_amount"], 2); ?> |
                    <?php echo $duo["locked"] ? "<em>(Locked)</em>" : "<em>(Unlocked)</em>"; ?>
                    <form action="view_money_duo.php" method="post" onsubmit="return confirmDelete();">
                        <input type="hidden" name="delete_money_duo_id" value="<?php echo $duo["id"]; ?>">
                        <button type="submit">Delete</button>
                    </form>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p>You have no Money Duos yet.</p>
    <?php endif; ?>
</main>
</body>
</html>
