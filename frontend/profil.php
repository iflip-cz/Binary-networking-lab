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
    <script>document.documentElement.setAttribute('data-theme',localStorage.getItem('bnl-theme')||'dark');</script>
    <link rel="stylesheet" href="s.css/profil.css">
</head>
<body>

<header>
    <a href="mainMenu.php" class="back-link">← Menu</a>
    <span class="header-hint"><span class="prompt">//</span> profil</span>
</header>

<main>

    <!-- One card, two zones — no negative margin tricks -->
    <div class="hero-card">

        <div class="hero-top">
            <div class="hero-identity">
                <div class="pfp" aria-hidden="true"><?= htmlspecialchars($initial) ?></div>
                <div class="hero-text">
                    <h1><?= htmlspecialchars($user["username"]) ?></h1>
                    <p class="real-name"><?= htmlspecialchars($user["name"]) ?> <?= htmlspecialchars($user["surname"]) ?></p>
                    <p class="email"><?= htmlspecialchars($user["email"]) ?></p>
                    <?php if ($user["teacher"]): ?>
                        <span class="role-tag">admin</span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="hero-scores">
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
        </div>

        <!-- Supporting numbers attached to hero — same card, step down -->
        <div class="hero-bottom">
            <div class="ss">
                <span class="ssv"><?= $accuracy ?>%</span>
                <span class="ssl">Presnost</span>
            </div>
            <div class="ss">
                <span class="ssv"><?= (int)$user["Q_answerd"] ?></span>
                <span class="ssl">Zodpovezeno</span>
            </div>
            <div class="ss">
                <span class="ssv"><?= (int)$user["Q_correct"] ?></span>
                <span class="ssl">Spravne</span>
            </div>
            <div class="ss">
                <span class="ssv"><?= max(0, (int)$user["Q_answerd"] - (int)$user["Q_correct"]) ?></span>
                <span class="ssl">Spatne</span>
            </div>
        </div>

    </div>

    <!-- Achievements -->
    <section>
        <h2>Achievementy <span class="count-pill"><?= count($achievements) ?></span></h2>
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
            <p class="empty">Zatim zadne — zahraj si a odemkni prvni.</p>
        <?php endif; ?>
    </section>

    <!-- History -->
    <section>
        <h2>Poslednich her <span class="count-pill"><?= count($history) ?></span></h2>
        <?php if ($history): ?>
        <div class="history">
            <div class="h-head">
                <span>Datum</span>
                <span>Mod</span>
                <span>Spravne</span>
                <span>Spatne</span>
                <span>Streak</span>
            </div>
            <?php foreach ($history as $g):
                $mode = match((int)$g["Gm"]) { 1 => "Time Attack", 3 => "Streak", default => "Training" };
            ?>
            <div class="h-row">
                <span class="h-date"><?= htmlspecialchars($g["When_Played"]) ?></span>
                <span class="h-mode"><?= $mode ?></span>
                <span class="h-correct"><?= (int)$g["Q_AC"] ?></span>
                <span class="h-wrong"><?= (int)$g["Q_AW"] ?></span>
                <span class="h-streak"><?= (int)$g["streak"] > 0 ? (int)$g["streak"] : "—" ?></span>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
            <p class="empty">Zatim zadne hry.</p>
        <?php endif; ?>
    </section>

</main>

<footer class="page-footer">
    <span>Binary Networking Lab</span>
    <span class="sep">·</span>
    <a href="mainMenu.php">Menu</a>
    <span class="sep">·</span>
    <a href="../backend/logout.php">Odhlasit se</a>
</footer>
<script>
    // Theme is applied by the inline script in <head> — no toggle button on this page.
</script>
</body>
</html>