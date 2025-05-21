<?php
session_start();
include "db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.html");
    exit();
}

$user_id = $_SESSION["user_id"];
$message = "";

// Accept invitation logic
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["accept_invitation"], $_POST["money_duo_id"])) {
    $money_duo_id = $_POST["money_duo_id"];
    $action = $_POST["accept_invitation"];

    if ($action === "accept") {
        // Insert into money_duo_invitations table to mark acceptance
        $stmt = $pdo->prepare("INSERT INTO money_duo_invitations (money_duo_id, user_id, status) VALUES (?, ?, 'accepted')");
        $stmt->execute([$money_duo_id, $user_id]);

        // Check if both users have accepted the invitation, if so, lock the duo
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM money_duo_invitations WHERE money_duo_id = ? AND status = 'accepted'");
        $stmt->execute([$money_duo_id]);
        $accepted_count = $stmt->fetchColumn();

        if ($accepted_count == 2) {
            // Lock the duo if both users have accepted
            $stmt = $pdo->prepare("UPDATE money_duo SET locked = 1 WHERE id = ?");
            $stmt->execute([$money_duo_id]);
        }

        $message = "You have successfully accepted the Money Duo invitation!";
    } elseif ($action === "decline") {
        // Decline the invitation
        $stmt = $pdo->prepare("DELETE FROM money_duo_invitations WHERE money_duo_id = ? AND user_id = ?");
        $stmt->execute([$money_duo_id, $user_id]);

        $message = "You have declined the Money Duo invitation.";
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accept Invitation - SaveWise</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <h1>Accept or Decline Invitation</h1>
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

        <!-- Accept Invitation Form -->
        <section>
            <h2>Invitation to Join Money Duo</h2>
            <p>If you want to accept or decline the invitation to join a Money Duo, click the appropriate button below.</p>
            <form action="accept_money_duo.php" method="post">
                <input type="hidden" name="money_duo_id" value="<?php echo $_GET['money_duo_id']; ?>">

                <button type="submit" name="accept_invitation" value="accept">Accept Invitation</button>
                <button type="submit" name="accept_invitation" value="decline">Decline Invitation</button>
            </form>
        </section>
    </main>
</body>
</html>
