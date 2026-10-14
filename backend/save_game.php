<?php
require "session.php";
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

// CSRF token travels in the body — sendBeacon (used on page leave) can't set headers
if (!csrf_valid($data["csrf"] ?? "")) {
    http_response_code(403);
    exit;
}

// ── The run must have been started by lesson.php ─────────────
// Mode, duration and number system come from the server's record, not the
// client, and each started game can be saved exactly once.
$token = $data["game_token"] ?? "";
$game  = is_string($token) ? ($_SESSION["games"][$token] ?? null) : null;
if (!$game) {
    http_response_code(422);
    echo json_encode(["status" => "rejected", "reasons" => ["token"]]);
    exit;
}
unset($_SESSION["games"][$token]);
$elapsed = microtime(true) - $game["start"];   // real seconds since the game page loaded

// ── Coerce every field to a non-negative-ish int ─────────────
$gameMode = $game["mode"];
$sysType  = $game["sys"];
$timeSec  = $gameMode === 1 ? $game["time"] : (int)round($elapsed);   // Streak: actual play time
$answered = (int)($data["q_answered"]   ?? 0);
$correct  = (int)($data["q_correct"]    ?? 0);
$wrong    = (int)($data["q_wrong"]      ?? 0);
$skipped  = (int)($data["q_skipped"]    ?? 0);
$streak   = (int)($data["streak"]       ?? 0);
$score    = (int)($data["score"]        ?? 0);

// ── Server-side sanity checks (anti-cheat) ───────────────────
// The game runs in the browser, so results can't be fully trusted. We can't
// prove a score is legitimate, but we can reject ones that are internally
// impossible or physically implausible, which stops trivial leaderboard spoofing.
$errors = [];

if (!in_array($gameMode, [1, 3], true))  $errors[] = "mode";      // only TA (1) & Streak (3) save
if (min($answered, $correct, $wrong, $skipped, $streak, $score) < 0) $errors[] = "negative";
if ($correct + $wrong > $answered)       $errors[] = "sum";       // correct+wrong can't exceed answered
if ($streak > $correct)                  $errors[] = "streak";    // streak can't exceed total correct

// Floor of 0.6 s per answer (~1.7/s) — fast, but no human keeps it up for a whole game.
if ($gameMode === 1) {                                            // Time Attack
    if ($answered > (int)ceil($timeSec / 0.6)) $errors[] = "rate";
    if ($elapsed + 2 < $timeSec)   $errors[] = "early";           // saved before the round could end
    if ($score !== $correct)       $errors[] = "score";           // TA score == correct answers
} else {                                                          // Streak Challenge
    if ($answered > (int)ceil($elapsed / 0.6)) $errors[] = "rate"; // no timer, so use real elapsed time
    if ($score !== $streak)        $errors[] = "score";           // streak score == best streak
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
