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

if (!is_array($data)) {
    http_response_code(400);
    exit;
}

// ── Coerce every field to a non-negative-ish int ─────────────
$gameMode = (int)($data["game_mode"]    ?? 0);
$timeSec  = (int)($data["time_seconds"] ?? 0);
$answered = (int)($data["q_answered"]   ?? 0);
$correct  = (int)($data["q_correct"]    ?? 0);
$wrong    = (int)($data["q_wrong"]      ?? 0);
$skipped  = (int)($data["q_skipped"]    ?? 0);
$streak   = (int)($data["streak"]       ?? 0);
$score    = (int)($data["score"]        ?? 0);
$sysType  = in_array($data["sys_type"] ?? "all", ["all", "bin", "hex", "oct"], true) ? $data["sys_type"] : "all";

// ── Server-side sanity checks (anti-cheat) ───────────────────
// The game runs in the browser, so results can't be fully trusted. We can't
// prove a score is legitimate, but we can reject ones that are internally
// impossible or physically implausible, which stops trivial leaderboard spoofing.
$errors = [];

if (!in_array($gameMode, [1, 3], true))  $errors[] = "mode";      // only TA (1) & Streak (3) save
if (min($answered, $correct, $wrong, $streak, $score) < 0) $errors[] = "negative";
if ($correct + $wrong > $answered)       $errors[] = "sum";       // correct+wrong can't exceed answered
if ($streak > $correct)                  $errors[] = "streak";    // streak can't exceed total correct

if ($gameMode === 1) {                                            // Time Attack
    if (!in_array($timeSec, [30, 60, 120], true)) {
        $errors[] = "time";
    } else {
        // Floor of 0.6 s per answer (~1.7/s) — fast, but no human beats it for a whole round.
        $maxPlausible = (int)ceil($timeSec / 0.6);
        if ($answered > $maxPlausible) $errors[] = "rate";
    }
    if ($score !== $correct) $errors[] = "score";                 // TA score == correct answers
} else {                                                          // Streak Challenge
    if ($score !== $streak)  $errors[] = "score";                 // streak score == best streak
}

if ($errors) {
    http_response_code(422);
    echo json_encode(["status" => "rejected", "reasons" => $errors]);
    exit;
}

$pdo    = connectDB();
$userId = (int)$_SESSION["user_id"];

// Insert into game_history
insertGameHistory($pdo, [
    "user_id"      => $userId,
    "game_mode"    => $gameMode,
    "sys_type"     => $sysType,
    "time_seconds" => $timeSec,
    "q_answered"   => $answered,
    "q_correct"    => $correct,
    "q_wrong"      => $wrong,
    "q_skipped"    => $skipped,
    "streak"       => $streak,
]);

// Update cumulative stats + highscore
addUserStats($pdo, $userId, $answered, $correct);
updateHighscore($pdo, $userId, $gameMode, $score);

// Award any achievements newly unlocked by this game
$newAchievements = checkAndAwardAchievements($pdo, $userId, [
    "game_mode"  => $gameMode,
    "q_answered" => $answered,
    "q_correct"  => $correct,
    "q_wrong"    => $wrong,
    "streak"     => $streak,
    "score"      => $score,
]);

http_response_code(200);
echo json_encode(["status" => "ok", "new_achievements" => $newAchievements]);
