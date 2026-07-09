<?php

// =========================================================
//  funcDB.php  –  Database helper functions
//  Change the dbname below to match your database!
// =========================================================

function connectDB() {
    // Per-environment credentials live in backend/config.php (gitignored), so the
    // same code runs on XAMPP, webzdarma and the Oracle VPS. Falls back to local
    // XAMPP defaults when that file isn't present.
    $defaults = [
        "host"    => "localhost",
        "dbname"  => "projekt_zwa",
        "user"    => "root",
        "pass"    => "",
        "charset" => "utf8mb4",
    ];
    $cfgFile = __DIR__ . "/config.php";
    $cfg = file_exists($cfgFile) ? array_merge($defaults, require $cfgFile) : $defaults;

    $dsn = "mysql:host={$cfg['host']};dbname={$cfg['dbname']};charset={$cfg['charset']}";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];

    try {
        return new PDO($dsn, $cfg["user"], $cfg["pass"], $options);
    } catch (PDOException $e) {
        http_response_code(500);
        exit("Database connection failed. Set the correct credentials in backend/config.php for this server.");
    }
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
        VALUES (?, ?, ?, ?, ?, ?, 0)
    ");
    $stmt->execute([
        $userInfo["username"],
        $userInfo["name"],
        $userInfo["surname"],
        $userInfo["email"],
        $userInfo["password"],
        (int)($userInfo["teacher"] ?? 0),
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
//  LEADERBOARDS  (aggregated from game_history, so Time Attack
//  stays split per duration and streaks are read accurately)
// ---------------------------------------------------------

/**
 * Time Attack leaderboard for ONE duration (30 / 60 / 120 s).
 * A player's entry is their best correct-count in a round of that exact length,
 * so a 120 s run never competes against a 30 s run.
 */
function getTimeAttackLeaderboard($pdo, $seconds, $sysType = 'all', $limit = 10) {
    $timeStr   = gmdate("H:i:s", (int)$seconds);   // 30 -> "00:00:30"
    $filterSys = $sysType !== 'all';               // 'all' = every system combined
    $stmt = $pdo->prepare("
        SELECT u.username, u.anonym, MAX(gh.Q_AC) AS highscore
        FROM game_history gh
        JOIN user u ON u.ID_user = gh.Who
        WHERE gh.Gm = 1 AND gh.Time = :t" . ($filterSys ? " AND gh.sys_type = :sys" : "") . "
        GROUP BY u.ID_user, u.username, u.anonym
        HAVING highscore > 0
        ORDER BY highscore DESC
        LIMIT :lim
    ");
    $stmt->bindValue(':t', $timeStr);
    if ($filterSys) $stmt->bindValue(':sys', $sysType);
    $stmt->bindValue(':lim', (int)$limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

/**
 * Streak Challenge leaderboard: each player's best streak (Gm = 3).
 */
function getStreakLeaderboard($pdo, $sysType = 'all', $limit = 10) {
    $filterSys = $sysType !== 'all';               // 'all' = every system combined
    $stmt = $pdo->prepare("
        SELECT u.username, u.anonym, MAX(gh.streak) AS highscore
        FROM game_history gh
        JOIN user u ON u.ID_user = gh.Who
        WHERE gh.Gm = 3" . ($filterSys ? " AND gh.sys_type = :sys" : "") . "
        GROUP BY u.ID_user, u.username, u.anonym
        HAVING highscore > 0
        ORDER BY highscore DESC
        LIMIT :lim
    ");
    if ($filterSys) $stmt->bindValue(':sys', $sysType);
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
    $sysType = in_array($data["sys_type"] ?? "all", ["all", "bin", "hex", "oct"], true)
        ? $data["sys_type"] : "all";

    $stmt = $pdo->prepare("
        INSERT INTO game_history (Gm, sys_type, Q_A, Q_AC, Q_AW, Q_AS, Time, Who, When_Played, streak)
        VALUES (?,  ?,        ?,    ?,    ?,    ?,    ?,    ?,   CURDATE(),   ?)
    ");
    $stmt->execute([
        $data["game_mode"],
        $sysType,
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
        WHERE Who = :who
        ORDER BY When_Played DESC, ID_GameH DESC
        LIMIT :lim
    ");
    $stmt->bindValue(':who', (int)$id_user, PDO::PARAM_INT);
    $stmt->bindValue(':lim', (int)$limit,   PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

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

/**
 * Awards an achievement to a user (ignores duplicates via INSERT IGNORE).
 * Returns true only if the badge was newly awarded (not already owned).
 */
function awardAchievement($pdo, $id_user, $id_achievement) {
    $stmt = $pdo->prepare("
        INSERT IGNORE INTO user_achivements (ID_user, ID_achivement)
        VALUES (?, ?)
    ");
    $stmt->execute([$id_user, $id_achievement]);
    return $stmt->rowCount() > 0;
}

/**
 * Checks all achievement conditions after a finished game and awards any that
 * are newly met. Rules are keyed by achievement Name and matched against the
 * rows seeded in the `achivements` table (see backend/seed_achievements.sql),
 * so the DB stays the single source of truth for which badges exist.
 *
 * $game keys: game_mode, q_answered, q_correct, q_wrong, streak, score.
 * Returns the list of achievement Names newly awarded this call.
 */
function checkAndAwardAchievements($pdo, $id_user, $game) {
    $user = getUserById($pdo, $id_user);
    if (!$user) return [];

    // Cumulative stats (already include the game that just finished)
    $answered   = (int)$user["Q_answerd"];
    $correct    = (int)$user["Q_correct"];
    $accuracy   = $answered > 0 ? $correct / $answered * 100 : 0;
    $h1         = (int)$user["highscore_1gm"];
    $bestStreak = max((int)$user["highscore_2gm"], (int)$game["streak"]);

    // Condition per achievement Name (must match seeded Names exactly).
    $rules = [
        "První hra"     => true,
        "Stovka otázek" => $answered >= 100,
        "Tisíc otázek"  => $answered >= 1000,
        "Přesná muška"  => $answered >= 20 && $accuracy >= 90,
        "Série 10"      => $bestStreak >= 10,
        "Série 25"      => $bestStreak >= 25,
        "Rychloprsty"   => $h1 >= 20,
        "Mistr převodů" => $h1 >= 40,
        "Bez chybičky"  => (int)$game["q_wrong"] === 0 && (int)$game["q_correct"] >= 10,
    ];

    // Map Name -> ID from whatever is actually seeded in the DB.
    $byName = [];
    foreach (getAllAchievements($pdo) as $a) {
        $byName[$a["Name"]] = $a["ID_achivements"];
    }
    
    $awarded = [];
    foreach ($rules as $name => $met) {
        if ($met && isset($byName[$name])) {
            // Only report badges that were actually new this game.
            if (awardAchievement($pdo, $id_user, $byName[$name])) {
                $awarded[] = $name;
            }
        }
    }
    return $awarded;
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
function generateClassCode($pdo) {
    do {
        $code = strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
        $check = $pdo->prepare("SELECT 1 FROM classes WHERE code = ?");
        $check->execute([$code]);
    } while ($check->rowCount() > 0);
    return $code;
}
function createClass($pdo, $id_teacher, $name) {
    $code = generateClassCode($pdo);
    $stmt = $pdo->prepare("
        INSERT INTO classes (name, code, ID_teacher, created_at)
        VALUES (?, ?, ?, CURDATE())
    ");
    $stmt->execute([$name, $code, $id_teacher]);
    return ['id' => (int)$pdo->lastInsertId(), 'code' => $code];
}
function deleteClass($pdo, $id_class, $id_teacher) {
    $stmt = $pdo->prepare("DELETE FROM classes WHERE ID_class = ? AND ID_teacher = ?");
    return $stmt->execute([$id_class, $id_teacher]);
}
/**
 * Join a class by code. Returns:
 *   int   → success (class id)
 *   'not_found'      → code doesn't exist
 *   'already_member' → already in this class
 *   'is_teacher'     → user is the teacher of this class
 */
function joinClassByCode($pdo, $code, $id_user) {
    $stmt = $pdo->prepare("SELECT * FROM classes WHERE code = ?");
    $stmt->execute([strtoupper(trim($code))]);
    $class = $stmt->fetch();
    if (!$class) return 'not_found';

    if ((int)$class['ID_teacher'] === (int)$id_user) return 'is_teacher';

    $check = $pdo->prepare("SELECT 1 FROM class_members WHERE ID_class = ? AND ID_user = ?");
    $check->execute([$class['ID_class'], $id_user]);
    if ($check->rowCount() > 0) return 'already_member';

    $ins = $pdo->prepare("INSERT INTO class_members (ID_class, ID_user) VALUES (?, ?)");
    $ins->execute([$class['ID_class'], $id_user]);
    return (int)$class['ID_class'];
}

/** Leave a class (student removes themselves). */
function leaveClass($pdo, $id_class, $id_user) {
    $stmt = $pdo->prepare("DELETE FROM class_members WHERE ID_class = ? AND ID_user = ?");
    return $stmt->execute([$id_class, $id_user]);
}

/** Remove any student from a class (teacher action). */
function removeFromClass($pdo, $id_class, $id_user, $id_teacher) {
    // Verify caller is actually the teacher of this class
    $chk = $pdo->prepare("SELECT 1 FROM classes WHERE ID_class = ? AND ID_teacher = ?");
    $chk->execute([$id_class, $id_teacher]);
    if (!$chk->rowCount()) return false;

    $stmt = $pdo->prepare("DELETE FROM class_members WHERE ID_class = ? AND ID_user = ?");
    return $stmt->execute([$id_class, $id_user]);
}

/** All classes a teacher owns. */
function getTeacherClasses($pdo, $id_teacher) {
    $stmt = $pdo->prepare("
        SELECT c.*, COUNT(cm.ID_user) AS member_count
        FROM classes c
        LEFT JOIN class_members cm ON c.ID_class = cm.ID_class
        WHERE c.ID_teacher = ?
        GROUP BY c.ID_class
        ORDER BY c.created_at DESC
    ");
    $stmt->execute([$id_teacher]);
    return $stmt->fetchAll();
}

/** All classes a student has joined. */
function getStudentClasses($pdo, $id_user) {
    $stmt = $pdo->prepare("
        SELECT c.*, u.username AS teacher_name
        FROM classes c
        JOIN class_members cm ON c.ID_class = cm.ID_class
        JOIN user u           ON c.ID_teacher = u.ID_user
        WHERE cm.ID_user = ?
        ORDER BY c.name
    ");
    $stmt->execute([$id_user]);
    return $stmt->fetchAll();
}

/** One class row (no permission check — caller must verify). */
function getClassById($pdo, $id_class) {
    $stmt = $pdo->prepare("SELECT * FROM classes WHERE ID_class = ?");
    $stmt->execute([$id_class]);
    return $stmt->fetch();
}

/**
 * Class leaderboard.
 * $type: 'time_attack' | 'streak' | 'total_correct'
 */
function getClassLeaderboard($pdo, $id_class, $type = 'time_attack', $limit = 30) {
    switch ($type) {
        case 'streak':        $col = 'u.highscore_2gm'; break;
        case 'total_correct': $col = 'u.Q_correct';     break;
        default:              $col = 'u.highscore_1gm';
    }

    $stmt = $pdo->prepare("
        SELECT u.username, u.anonym, u.highscore_1gm, u.highscore_2gm,
               u.Q_correct, u.Q_answerd,
               $col AS score
        FROM class_members cm
        JOIN user u ON cm.ID_user = u.ID_user
        WHERE cm.ID_class = :cid
        ORDER BY $col DESC
        LIMIT :lim
    ");
    $stmt->bindValue(':cid', (int)$id_class, PDO::PARAM_INT);
    $stmt->bindValue(':lim', (int)$limit,    PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

/** All members with full stats — for the teacher's view. */
function getClassMembersDetailed($pdo, $id_class) {
    $stmt = $pdo->prepare("
        SELECT u.ID_user, u.username, u.name, u.surname,
               u.highscore_1gm, u.highscore_2gm,
               u.Q_answerd, u.Q_correct,
               ROUND(
                 CASE WHEN u.Q_answerd > 0
                      THEN u.Q_correct / u.Q_answerd * 100
                      ELSE 0 END, 1
               ) AS accuracy
        FROM class_members cm
        JOIN user u ON cm.ID_user = u.ID_user
        WHERE cm.ID_class = :cid
        ORDER BY u.highscore_1gm DESC
    ");
    $stmt->bindValue(':cid', (int)$id_class, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}
