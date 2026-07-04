<?php
session_start();
require "funcDB.php";

// Logged-in users only; returns JSON rows for one leaderboard view.
if (!isset($_SESSION["user_id"])) {
    http_response_code(403);
    exit;
}
header("Content-Type: application/json; charset=utf-8");

$kind = ($_GET["kind"] ?? "ta") === "streak" ? "streak" : "ta";
$sys  = in_array($_GET["sys"] ?? "all", ["all", "bin", "hex", "oct"], true) ? $_GET["sys"] : "all";

$pdo = connectDB();

if ($kind === "streak") {
    $rows = getStreakLeaderboard($pdo, $sys, 10);
} else {
    $sec  = in_array((int)($_GET["seconds"] ?? 60), [30, 60, 120], true) ? (int)$_GET["seconds"] : 60;
    $rows = getTimeAttackLeaderboard($pdo, $sec, $sys, 10);
}

echo json_encode($rows);
