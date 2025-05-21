<?php
session_start();
include "db.php";

header('Content-Type: application/json');

if (!isset($_SESSION["user_id"])) {
    http_response_code(403);
    echo json_encode(["status" => "error", "message" => "Unauthorized"]);
    exit();
}

$user_id = $_SESSION["user_id"];

// Validate required POST data
if (!isset($_POST["duo_id"], $_POST["action"])) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Missing required parameters."]);
    exit();
}

$duo_id = filter_var($_POST["duo_id"], FILTER_VALIDATE_INT);
$action = $_POST["action"];

if ($duo_id === false || !in_array($action, ['lock', 'unlock'], true)) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Invalid input."]);
    exit();
}

$lock_value = ($action === 'lock') ? 1 : 0;

// Ensure the user is part of the duo before updating
$stmt = $pdo->prepare("UPDATE money_duo SET locked = ? WHERE id = ? AND (user1_id = ? OR user2_id = ?)");
$stmt->execute([$lock_value, $duo_id, $user_id, $user_id]);

if ($stmt->rowCount() > 0) {
    // Optional logging (uncomment if needed)
    // file_put_contents("duo_lock_log.txt", date("Y-m-d H:i:s") . " | User $user_id set duo $duo_id to " . ($lock_value ? "LOCKED" : "UNLOCKED") . "\n", FILE_APPEND);

    echo json_encode([
        "status" => "success",
        "message" => "Duo has been " . ($lock_value ? "locked" : "unlocked") . "."
    ]);
} else {
    http_response_code(400);
    echo json_encode([
        "status" => "error",
        "message" => "No update made. You may not have permission or duo doesn't exist."
    ]);
}
