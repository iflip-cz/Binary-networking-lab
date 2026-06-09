<?php

// =========================================================
//  funcDB.php  –  Database helper functions
//  Change the dbname below to match your database!
// =========================================================

function connectDB() {
    $dsn = "mysql:host=localhost;dbname=projekt_zwa;charset=utf8mb4";
    $user = "root";
    $pass = "";

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];

    return new PDO($dsn, $user, $pass, $options);
}

// ---------------------------------------------------------
//  USER  functions
// ---------------------------------------------------------

/**
 * Insert a brand-new user (all defaults: teacher=0, anonym=0, scores=0).
 * $userInfo must contain: username, name, surname, email, password (already hashed).
 * Returns the new ID_user.
 */
function insertNewUser($pdo, $userInfo) {
    $stmt = $pdo->prepare("
        INSERT INTO user (username, name, surname, email, password, teacher, anonym)
        VALUES (?, ?, ?, ?, ?, 0, 0)
    ");
    $stmt->execute([
        $userInfo["username"],
        $userInfo["name"],
        $userInfo["surname"],
        $userInfo["email"],
        $userInfo["password"],   // hash produced by password_hash()
    ]);
    return (int)$pdo->lastInsertId();
}

/** Returns a single user row by username, or false when not found. */
function getUserByUsername($pdo, $username) {
    $stmt = $pdo->prepare("SELECT * FROM user WHERE username = ?");
    $stmt->execute([$username]);
    return $stmt->fetch();   // false if not found
}

/** Returns a single user row by email, or false when not found. */
function getUserByEmail($pdo, $email) {
    $stmt = $pdo->prepare("SELECT * FROM user WHERE email = ?");
    $stmt->execute([$email]);
    return $stmt->fetch();
}

/** Returns a single user row by ID_user, or false when not found. */
function getUserById($pdo, $id) {
    $stmt = $pdo->prepare("SELECT * FROM user WHERE ID_user = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

/** Returns all users ordered by username – for the admin user-management panel. */
function showUsers($pdo) {
    $stmt = $pdo->query("SELECT * FROM user ORDER BY username");
    return $stmt->fetchAll();
}

/** Deletes a user (CASCADE will clean up game_history and user_achivements). */
function deleteUser($pdo, $id_user) {
    $stmt = $pdo->prepare("DELETE FROM user WHERE ID_user = ?");
    return $stmt->execute([$id_user]);
}

/**
 * Updates the highscore for a given game mode (1 or 2).
 * Only writes if the new score beats the old one.
 */
function updateHighscore($pdo, $id_user, $game_mode, $new_score) {
    $col = $game_mode === 1 ? "highscore_1gm" : "highscore_2gm";
    // We use a CASE expression so one query does the check + update
    $stmt = $pdo->prepare("
        UPDATE user
        SET $col = GREATEST($col, ?)
        WHERE ID_user = ?
    ");
    $stmt->execute([$new_score, $id_user]);
}

/** Also bumps the cumulative Q_answerd and Q_correct counters on the user row. */
function addUserStats($pdo, $id_user, $answered, $correct) {
    $stmt = $pdo->prepare("
        UPDATE user
        SET Q_answerd = Q_answerd + ?,
            Q_correct = Q_correct + ?
        WHERE ID_user = ?
    ");
    $stmt->execute([$answered, $correct, $id_user]);
}

// ---------------------------------------------------------
//  LEADERBOARD  (uses pre-computed highscore columns)
// ---------------------------------------------------------

/**
 * Returns the top $limit players for the given game mode.
 * Respects the anonym flag – anonymous names are shown as "Anonym".
 *
 * $game_mode = 1  → Time Attack  (highscore_1gm)
 * $game_mode = 2  → Training Lab (highscore_2gm)
 */
function getLeaderboard($pdo, $game_mode = 1, $limit = 10) {
    if ($game_mode === 1) {
        $sql = "SELECT username, anonym, highscore_1gm AS highscore
                FROM user
                WHERE highscore_1gm > 0
                ORDER BY highscore_1gm DESC
                LIMIT :lim";
    } else {
        $sql = "SELECT username, anonym, highscore_2gm AS highscore
                FROM user
                WHERE highscore_2gm > 0
                ORDER BY highscore_2gm DESC
                LIMIT :lim";
    }
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':lim', (int)$limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

// ---------------------------------------------------------
//  GAME HISTORY  functions
// ---------------------------------------------------------

/**
 * Saves one completed game session.
 * $data must contain: user_id, game_mode (1/2), q_answered, q_correct,
 *                     q_wrong, q_skipped, time_seconds, streak
 */
function insertGameHistory($pdo, $data) {
    // Convert seconds integer → TIME string 'HH:MM:SS'
    $timeStr = gmdate("H:i:s", (int)$data["time_seconds"]);

    $stmt = $pdo->prepare("
        INSERT INTO game_history (Gm, Q_A, Q_AC, Q_AW, Q_AS, Time, Who, When_Played, streak)
        VALUES (?,   ?,    ?,    ?,    ?,    ?,       ?,   CURDATE(),   ?)
    ");
    $stmt->execute([
        $data["game_mode"],
        $data["q_answered"],
        $data["q_correct"],
        $data["q_wrong"],
        $data["q_skipped"],
        $timeStr,
        $data["user_id"],
        $data["streak"],
    ]);
    return (int)$pdo->lastInsertId();
}

/** Returns the last $limit games played by a specific user. */
function getUserHistory($pdo, $id_user, $limit = 10) {
    $stmt = $pdo->prepare("
        SELECT * FROM game_history
        WHERE Who = ?
        ORDER BY When_Played DESC, ID_GameH DESC
        LIMIT ?
    ");
    $stmt->execute([$id_user, $limit]);
    return $stmt->fetchAll();
}

// ---------------------------------------------------------
//  ACHIEVEMENT  functions
// ---------------------------------------------------------

/** Returns all achievements in the achivements table. */
function getAllAchievements($pdo) {
    $stmt = $pdo->query("SELECT * FROM achivements ORDER BY rarity, Name");
    return $stmt->fetchAll();
}

/** Returns achievements already earned by a given user. */
function getUserAchievements($pdo, $id_user) {
    $stmt = $pdo->prepare("
        SELECT a.*
        FROM achivements a
        JOIN user_achivements ua ON a.ID_achivements = ua.ID_achivement
        WHERE ua.ID_user = ?
    ");
    $stmt->execute([$id_user]);
    return $stmt->fetchAll();
}

/** Awards an achievement to a user (ignores duplicates via INSERT IGNORE). */
function awardAchievement($pdo, $id_user, $id_achievement) {
    $stmt = $pdo->prepare("
        INSERT IGNORE INTO user_achivements (ID_user, ID_achivement)
        VALUES (?, ?)
    ");
    $stmt->execute([$id_user, $id_achievement]);
}

/** Deletes an achievement row (admin CRUD). */
function deleteAchievement($pdo, $id_achievement) {
    $stmt = $pdo->prepare("DELETE FROM achivements WHERE ID_achivements = ?");
    return $stmt->execute([$id_achievement]);
}

// ---------------------------------------------------------
//  UTILITY
// ---------------------------------------------------------

/** Trims $value; calls die() with $message if the result is empty. */
function require_field($value, $message) {
    $value = trim($value ?? "");
    if ($value === "") {
        die($message);
    }
    return $value;
}
