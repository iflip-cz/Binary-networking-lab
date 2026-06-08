<?php
session_start();
require "funcDB.php";

// Only accept POST requests – redirect GET to the login page
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../frontend/login.php");
    exit;
}

$username = trim($_POST["username"] ?? "");
$password = $_POST["password"] ?? "";

if ($username === "" || $password === "") {
    header("Location: ../frontend/login.php?error=1");
    exit;
}

$db = connectDB();
$user = getUserByUsername($db, $username);

if ($user && password_verify($password, $user["password"])) {

    // Store everything the rest of the app needs to know about this user
    $_SESSION["user_id"]  = $user["ID_user"];
    $_SESSION["username"] = $user["username"];
    $_SESSION["teacher"]  = (int)$user["teacher"];   // 1 = admin/teacher, 0 = student
    $_SESSION["anonym"]   = (int)$user["anonym"];    // 1 = hide name on leaderboard

    header("Location: ../frontend/mainMenu.php");
    exit;

} else {
    header("Location: ../frontend/login.php?error=1");
    exit;
}
