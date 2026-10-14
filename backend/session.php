<?php

// =========================================================
//  session.php  –  Shared session bootstrap + CSRF helpers
//  Include this instead of calling session_start() directly,
//  so every page gets the same hardened session cookie.
// =========================================================

if (session_status() === PHP_SESSION_NONE) {
    $https = !empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off";

    ini_set("session.use_strict_mode", "1");   // ignore session IDs the server never issued
    session_set_cookie_params([
        "lifetime" => 0,
        "path"     => "/",
        "httponly" => true,     // JavaScript can't read the session cookie
        "secure"   => $https,   // HTTPS-only whenever the site runs on HTTPS
        "samesite" => "Lax",    // not sent on cross-site POSTs
    ]);
    session_start();
}

/** Per-session CSRF token, created on first use. */
function csrf_token() {
    if (empty($_SESSION["csrf"])) {
        $_SESSION["csrf"] = bin2hex(random_bytes(32));
    }
    return $_SESSION["csrf"];
}

/** Hidden input carrying the token — echo it inside every POST form. */
function csrf_field() {
    return '<input type="hidden" name="csrf" value="' . csrf_token() . '">';
}

/** True when $sent (default: the POSTed "csrf" field) matches this session's token. */
function csrf_valid($sent = null) {
    if ($sent === null) $sent = $_POST["csrf"] ?? "";
    return is_string($sent) && $sent !== "" && hash_equals(csrf_token(), $sent);
}
