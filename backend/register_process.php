<?php
require "session.php";
require "funcDB.php";

// Only accept POST requests
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../frontend/register.php");
    exit;
}

// ── Error codes used for redirects ───────────────────────────
// fields          → a required field was left empty
// length          → a field is longer than its DB column
// email           → e-mail address isn't valid
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

// ── 1. All fields must be filled and fit their DB columns ────
if ($username === "" || $name === "" || $surname === "" ||
    $email    === "" || $password === "") {
    redirect_error("fields");
}
// The form's maxlength is only a hint — a crafted POST skips it, and MySQL
// would then either error out (strict mode) or silently cut the value.
if (mb_strlen($username) > 20 || mb_strlen($name) > 20 ||
    mb_strlen($surname)  > 20 || mb_strlen($email) > 100) {
    redirect_error("length");
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    redirect_error("email");
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

try {
    $newId = insertNewUser($pdo, [
        "username" => $username,
        "name"     => $name,
        "surname"  => $surname,
        "email"    => $email,
        "password" => $hash,
        'teacher' => isset($_POST['is_teacher']) ? 1 : 0,
    ]);
} catch (PDOException $e) {
    // A UNIQUE index (migration_unique_keys.sql) caught a duplicate that slipped
    // past steps 3–4, e.g. two registrations at the same moment.
    if ($e->getCode() !== "23000") throw $e;
    redirect_error(stripos($e->getMessage(), "uq_user_email") !== false ? "exists_email" : "exists_username");
}

// ── 6. Auto-login after successful registration ──────────────
session_regenerate_id(true);   // fresh session ID for the now-logged-in user
$_SESSION["user_id"]  = $newId;
$_SESSION["username"] = $username;
$_SESSION["teacher"]  = isset($_POST['is_teacher']) ? 1 : 0;
$_SESSION["anonym"]   = 0;
unset($_SESSION["reg_old"]);

header("Location: ../frontend/mainMenu.php");
exit;
