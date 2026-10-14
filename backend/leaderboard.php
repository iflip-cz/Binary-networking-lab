<?php
require "session.php";
require "funcDB.php";

// Logged-in users only; returns JSON rows for one leaderboard view.
if (!isset($_SESSION["user_id"])) {
    http_response_code(403);
    exit;
}
header("Content-Type: application/json; charset=utf-8");

$kind = ($_GET["kind"] ?? "ta") === "streak" ? "streak" : "ta";
$sys  = $_GET["sys"] ?? "all";
if (!in_array($sys, ["all", "bin", "hex", "oct"], true)) $sys = "all";

$pdo = connectDB();

if ($kind === "streak") {
    $rows = getStreakLeaderboard($pdo, $sys, 10);
} else {
    $sec  = (int)($_GET["seconds"] ?? 60);
    if (!in_array($sec, [30, 60, 120], true)) $sec = 60;
    $rows = getTimeAttackLeaderboard($pdo, $sec, $sys, 10);
}

// Anonymous players' usernames never leave the server; the client only gets the
// display name and whether the row is the viewer's own (for highlighting).
$me  = (int)$_SESSION["user_id"];
$out = [];
foreach ($rows as $r) {
    $out[] = [
        "name"      => $r["anonym"] ? "anonym" : $r["username"],
        "me"        => (int)$r["ID_user"] === $me,
        "highscore" => (int)$r["highscore"],
    ];
}

echo json_encode($out);
