<?php
session_start();
require "funcDB.php";

// Must be logged in
if (!isset($_SESSION["user_id"])) {
    http_response_code(403);
    exit;
}

// Accept only JSON POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    exit;
}

$raw  = file_get_contents("php://input");
$data = json_decode($raw, true);

if (!$data) {
    http_response_code(400);
    exit;
}

$pdo     = connectDB();
$userId  = (int)$_SESSION["user_id"];

// Insert into game_history
insertGameHistory($pdo, [
    "user_id"      => $userId,
    "game_mode"    => (int)$data["game_mode"],
    "time_seconds" => (int)$data["time_seconds"],
    "q_answered"   => (int)$data["q_answered"],
    "q_correct"    => (int)$data["q_correct"],
    "q_wrong"      => (int)$data["q_wrong"],
    "q_skipped"    => (int)$data["q_skipped"],
    "streak"       => (int)$data["streak"],
]);

// Update cumulative stats on user row
addUserStats($pdo, $userId, (int)$data["q_answered"], (int)$data["q_correct"]);

// Update highscore if beaten
$score = (int)$data["score"];
updateHighscore($pdo, $userId, (int)$data["game_mode"], $score);

http_response_code(200);
echo json_encode(["status" => "ok"]);
