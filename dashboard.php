<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.html");
    exit;
}

require_once 'db.php';
$user_id = $_SESSION["user_id"];

$stmt = $pdo->prepare("SELECT username, balance FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    session_destroy();
    header("Location: login.html");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM money_duo WHERE user1_id = ? OR user2_id = ?");
$stmt->execute([$user_id, $user_id]);
$money_duos = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT COUNT(*) as total, SUM(target_amount) as total_goal FROM savings_plans WHERE user_id = ?");
$stmt->execute([$user_id]);
$savings_stats = $stmt->fetch();

$stmt = $pdo->prepare("SELECT mdi.*, u.email AS partner_email FROM money_duo_invitations mdi 
    JOIN money_duo md ON mdi.money_duo_id = md.id 
    JOIN users u ON mdi.invited_user_id = u.id 
    WHERE md.user1_id = ? AND mdi.status = 'pending'");
$stmt->execute([$user_id]);
$sent_invitations = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT mdi.*, md.shared_goal, u.username AS inviter_name 
    FROM money_duo_invitations mdi 
    JOIN money_duo md ON mdi.money_duo_id = md.id 
    JOIN users u ON md.user1_id = u.id 
    WHERE mdi.invited_user_id = ? AND mdi.status = 'pending'");
$stmt->execute([$user_id]);
$received_invitations = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - SaveWise</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
</head>
<body>
<header>
    <h1>SaveWise Dashboard</h1>
    <nav>
        <ul>
            <li><a href="dashboard.php">Home</a></li>
            <li><a href="view_savings.php">Savings Plans</a></li>
            <li><a href="view_money_duo.php">Money Duo</a></li>
            <li><a href="logout.php">Logout</a></li>
        </ul>
    </nav>
</header>

<main>
    <h2>Welcome, <?php echo htmlspecialchars($user['username']); ?>!</h2>
    <p>Your current balance: $<?php echo number_format($user['balance'], 2); ?></p>

    <!-- Personal Savings Stats -->
    <section>
        <h3>Your Savings Plans</h3>
        <p>Total Plans: <strong><?php echo $savings_stats['total']; ?></strong></p>
        <p>Combined Target Amount: $<?php echo number_format($savings_stats['total_goal'] ?? 0, 2); ?></p>
        <a href="view_savings.php">View All Savings Plans</a>
    </section>

    <!-- Active Money Duos -->
    <section>
        <h3>Active Money Duos</h3>
        <?php if ($money_duos): ?>
            <ul>
                <?php foreach ($money_duos as $duo): ?>
                    <?php
                        $partner_id = ($duo['user1_id'] == $user_id) ? $duo['user2_id'] : $duo['user1_id'];
                        $partner_stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
                        $partner_stmt->execute([$partner_id]);
                        $partner = $partner_stmt->fetch();
                        $partner_name = $partner ? $partner['username'] : 'Waiting for partner...';

                        $progress = ($duo['target_amount'] > 0) 
                            ? round(($duo['saved_amount'] / $duo['target_amount']) * 100, 1) 
                            : 0;

                        $latest = $pdo->prepare("SELECT amount, contribution_date FROM contribution_history WHERE money_duo_id = ? ORDER BY contribution_date DESC LIMIT 1");
                        $latest->execute([$duo['id']]);
                        $contrib = $latest->fetch();
                    ?>
                    <li>
                        <strong>Goal:</strong> <?php echo htmlspecialchars($duo['shared_goal']); ?><br>
                        <strong>With:</strong> <?php echo htmlspecialchars($partner_name); ?><br>
                        <strong>Saved:</strong> $<?php echo number_format($duo['saved_amount'], 2); ?> / $<?php echo number_format($duo['target_amount'], 2); ?>
                        <div class="progress-bar">
                            <div style="width: <?php echo $progress; ?>%; background: #4CAF50; height: 10px;"></div>
                        </div>
                        <small>Progress: <?php echo $progress; ?>%</small><br>
                        <?php if ($contrib): ?>
                            <small>Last: $<?php echo number_format($contrib['amount'], 2); ?> on <?php echo $contrib['contribution_date']; ?></small><br>
                        <?php endif; ?>
                        <a href="view_contribution_history.php?duo_id=<?php echo $duo['id']; ?>">View History</a>

                        <form action="toggle_lock_duo.php" method="POST" style="display:inline;">
                            <input type="hidden" name="duo_id" value="<?php echo $duo['id']; ?>">
                            <button type="submit" name="action" value="<?php echo $duo['is_locked'] ? 'unlock' : 'lock'; ?>">
                                <?php echo $duo['is_locked'] ? '🔓 Unlock' : '🔒 Lock'; ?>
                            </button>
                        </form>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>You are not in any Money Duo yet.</p>
        <?php endif; ?>
    </section>

    
    <section>
        <h3>Invitations You Sent</h3>
        <?php if ($sent_invitations): ?>
            <ul>
                <?php foreach ($sent_invitations as $inv): ?>
                    <li>
                        <strong>Goal:</strong> <?php echo htmlspecialchars($inv['shared_goal']); ?><br>
                        <strong>Partner:</strong> <?php echo htmlspecialchars($inv['partner_email']); ?><br>
                        <em>Status: Waiting for partner to accept...</em>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>No outgoing pending invitations.</p>
        <?php endif; ?>
    </section>

    
    <section>
        <h3>Invitations You Received</h3>
        <?php if ($received_invitations): ?>
            <ul>
                <?php foreach ($received_invitations as $inv): ?>
                    <li>
                        <strong>Goal:</strong> <?php echo htmlspecialchars($inv['shared_goal']); ?><br>
                        <strong>From:</strong> <?php echo htmlspecialchars($inv['inviter_name']); ?><br>
                        <a href="accept_money_duo.php?money_duo_id=<?php echo $inv['money_duo_id']; ?>">Respond to Invitation</a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>No incoming invitations.</p>
        <?php endif; ?>
    </section>

    
    <div style="margin-top: 30px;">
        <a href="savings.html" class="button-link">Create Savings Plan</a>
        <a href="money_duo.html" class="button-link">Start a Money Duo</a>
    </div>
</main>

<footer>
    <p>&copy; 2025 SaveWise. All rights reserved.</p>
</footer>
</body>
</html>
