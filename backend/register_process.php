<?php
session_start();
require "funcDB.php";

// Only accept POST requests
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../frontend/register.php");
    exit;
}

// ── Error codes used for redirects ───────────────────────────
// fields          → a required field was left empty
// password        → password failed the strength / match rules
// exists_username → username already taken
// exists_email    → email already registered

function validate_password($password, $confirm) {
    if ($password !== $confirm)                  return "password";
    if (strlen($password) < 8)                   return "password";
    if (!preg_match('/[A-Z]/', $password))        return "password";
    if (!preg_match('/[a-z]/', $password))        return "password";
    if (!preg_match('/[0-9]/', $password))        return "password";
    return true;
}

function redirect_error($code) {
    header("Location: ../frontend/register.php?error=$code");
    exit;
}

// ── Collect & sanitise input ─────────────────────────────────
$username         = trim($_POST["username"]         ?? "");
$name             = trim($_POST["name"]             ?? "");
$surname          = trim($_POST["surname"]          ?? "");
$email            = trim($_POST["email"]            ?? "");
$password         = $_POST["password"]              ?? "";
$confirm_password = $_POST["confirm_password"]      ?? "";

// Remember the non-password fields so an error reload can refill them.
$_SESSION["reg_old"] = compact("username", "name", "surname", "email");

// ── 1. All fields must be filled ─────────────────────────────
if ($username === "" || $name === "" || $surname === "" ||
    $email    === "" || $password === "") {
    redirect_error("fields");
}

// ── 2. Password strength ─────────────────────────────────────
$pwResult = validate_password($password, $confirm_password);
if ($pwResult !== true) {
    redirect_error($pwResult);
}

$pdo = connectDB();

// ── 3. Username must be unique ───────────────────────────────
if (getUserByUsername($pdo, $username)) {
    redirect_error("exists_username");
}

// ── 4. Email must be unique ──────────────────────────────────
if (getUserByEmail($pdo, $email)) {
    redirect_error("exists_email");
}

// ── 5. Create the user ───────────────────────────────────────
$hash = password_hash($password, PASSWORD_DEFAULT);  // bcrypt ≈ 60 chars

$newId = insertNewUser($pdo, [
    "username" => $username,
    "name"     => $name,
    "surname"  => $surname,
    "email"    => $email,
    "password" => $hash,
    'teacher' => isset($_POST['is_teacher']) ? 1 : 0,
]);

// ── 6. Auto-login after successful registration ──────────────
$_SESSION["user_id"]  = $newId;
$_SESSION["username"] = $username;
$_SESSION["teacher"]  = isset($_POST['is_teacher']) ? 1 : 0;
$_SESSION["anonym"]   = 0;
unset($_SESSION["reg_old"]);

header("Location: ../frontend/mainMenu.php");
exit;
