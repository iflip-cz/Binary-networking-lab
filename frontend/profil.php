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
    <meta name="theme-color" content="#0d0f14">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 64 64%22><rect width=%2264%22 height=%2264%22 rx=%2214%22 fill=%22%23f97316%22/><text x=%2232%22 y=%2244%22 font-family=%22monospace%22 font-size=%2230%22 font-weight=%22700%22 text-anchor=%22middle%22 fill=%22%230d0f14%22>01</text></svg>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
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
                <span class="ssv"><?= max(0, (int)$user["Q_answerd"] - (int)$user["Q_correct"]) ?></span>
                <span class="ssl">Špatně</span>
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
            <p class="empty">Zatím žádné — zahraj si a odemkni první.</p>
        <?php endif; ?>
    </section>

    <!-- History -->
    <section>
        <h2>Posledních her <span class="count-pill"><?= count($history) ?></span></h2>
        <?php if ($history): ?>
        <div class="history">
            <div class="h-head">
                <span>Datum</span>
                <span>Mód</span>
                <span>Správně</span>
                <span>Špatně</span>
                <span>Streak</span>
            </div>
            <?php foreach ($history as $g):
                $gm = (int)$g["Gm"];
                $mode = $gm === 1 ? "Time Attack" : ($gm === 3 ? "Streak" : "Training");
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
<script>
    // Theme is applied by the inline script in <head> — no toggle button on this page.
</script>
</body>
</html>