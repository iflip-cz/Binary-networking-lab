<?php
session_start();
if (!isset($_SESSION["user_id"])) { header("Location: login.php"); exit; }
require "../backend/funcDB.php";
$pdo  = connectDB();
$user = getUserById($pdo, $_SESSION["user_id"]);
if (!$user) { header("Location: ../backend/logout.php"); exit; }

$achievements = getUserAchievements($pdo, $_SESSION["user_id"]);
$history      = getUserHistory($pdo, $_SESSION["user_id"], 10);
$accuracy     = $user["Q_answerd"] > 0
    ? round($user["Q_correct"] / $user["Q_answerd"] * 100, 1) : 0;
$initial = strtoupper(substr($user["username"], 0, 1));
?>
<!doctype html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($user["username"]) ?> — BNL</title>
    <link rel="stylesheet" href="s.css/profil.css">
</head>
<body>

<header>
    <a href="mainMenu.php" class="back-link">← Menu</a>
    <span class="header-hint"><span class="prompt">//</span> profil</span>
</header>

<main>

    <!-- ── Hero ──────────────────────────────────────
         Two-zone layout: identity left, big numbers right.
         Visitor reads name first, performance second.
    ─────────────────────────────────────────────────── -->
    <section class="hero">
        <div class="hero-identity">
            <div class="pfp" aria-hidden="true"><?= $initial ?></div>
            <div>
                <h1><?= htmlspecialchars($user["username"]) ?></h1>
                <p class="real-name">
                    <?= htmlspecialchars($user["name"]) ?>
                    <?= htmlspecialchars($user["surname"]) ?>
                </p>
                <p class="email"><?= htmlspecialchars($user["email"]) ?></p>
                <?php if ($user["teacher"]): ?>
                    <span class="role-tag">admin</span>
                <?php endif; ?>
            </div>
        </div>

        <div class="hero-stats">
            <div class="big-stat">
                <span class="bsv"><?= (int)$user["highscore_1gm"] ?></span>
                <span class="bsl">Time Attack</span>
            </div>
            <div class="stat-divider"></div>
            <div class="big-stat">
                <span class="bsv"><?= (int)$user["highscore_2gm"] ?></span>
                <span class="bsl">Best Streak</span>
            </div>
        </div>
    </section>

    <!-- Supporting numbers — one step down from the hero -->
    <div class="support-stats">
        <div class="ss">
            <span class="ssv"><?= $accuracy ?>%</span>
            <span class="ssl">Přesnost</span>
        </div>
        <div class="ss">
            <span class="ssv"><?= (int)$user["Q_answerd"] ?></span>
            <span class="ssl">Zodpovězeno</span>
        </div>
        <div class="ss">
            <span class="ssv"><?= (int)$user["Q_correct"] ?></span>
            <span class="ssl">Správně</span>
        </div>
        <div class="ss">
            <span class="ssv"><?= (int)$user["Q_answerd"] - (int)$user["Q_correct"] ?></span>
            <span class="ssl">Špatně</span>
        </div>
    </div>

    <!-- ── Achievements ──────────────────────────────── -->
    <section>
        <h2>
            Achievementy
            <span class="count-pill"><?= count($achievements) ?></span>
        </h2>
        <?php if ($achievements): ?>
        <div class="badge-grid">
            <?php foreach ($achievements as $a): ?>
            <div class="badge rarity-<?= htmlspecialchars($a["rarity"]) ?>">
                <strong><?= htmlspecialchars($a["Name"]) ?></strong>
                <span><?= htmlspecialchars($a["rarity"]) ?></span>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
            <p class="empty">Zatím žádné — zahraj si a odemkni první.</p>
        <?php endif; ?>
    </section>

    <!-- ── Game history ───────────────────────────────── -->
    <section>
        <h2>
            Posledních her
            <span class="count-pill"><?= count($history) ?></span>
        </h2>
        <?php if ($history): ?>
        <div class="history">
            <?php foreach ($history as $g):
                $mode = match((int)$g["Gm"]) { 1 => "Time Attack", 3 => "Streak", default => "Training" };
            ?>
            <div class="h-row">
                <span class="h-date"><?= htmlspecialchars($g["When_Played"]) ?></span>
                <span class="h-mode"><?= $mode ?></span>
                <span class="h-correct">+<?= (int)$g["Q_AC"] ?></span>
                <span class="h-wrong">–<?= (int)$g["Q_AW"] ?></span>
                <?php if ((int)$g["streak"] > 0): ?>
                    <span class="h-streak">🔥 <?= (int)$g["streak"] ?></span>
                <?php else: ?>
                    <span class="h-streak-empty"></span>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
            <p class="empty">Zatím žádné hry.</p>
        <?php endif; ?>
    </section>

</main>

<footer class="page-footer">
    <span>Binary Networking Lab</span>
    <span class="sep">·</span>
    <a href="mainMenu.php">Menu</a>
    <span class="sep">·</span>
    <a href="../backend/logout.php">Odhlásit se</a>
</footer>

</body>
</html>