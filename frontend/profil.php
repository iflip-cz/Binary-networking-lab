<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

require "../backend/funcDB.php";
$pdo  = connectDB();
$user = getUserById($pdo, $_SESSION["user_id"]);

if (!$user) {
    // Session is stale – force logout
    header("Location: ../backend/logout.php");
    exit;
}

$achievements = getUserAchievements($pdo, $_SESSION["user_id"]);
$history      = getUserHistory($pdo, $_SESSION["user_id"], 10);

// Calculate accuracy
$accuracy = ($user["Q_answerd"] > 0)
    ? round($user["Q_correct"] / $user["Q_answerd"] * 100, 1)
    : 0;
?>
<!doctype html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil – <?= htmlspecialchars($user["username"]) ?></title>
    <link rel="stylesheet" href="s.css/profil.css">
</head>
<body>

<header>
    <a href="mainMenu.php">← Zpět na hlavní menu</a>
</header>

<main>

    <!-- ── Info ─────────────────────────────────────────── -->
    <section class="profile-info">
        <div class="pfp-placeholder">
            <!-- TODO: profile picture upload goes here -->
            <span>👤</span>
        </div>
        <div class="profile-details">
            <h2><?= htmlspecialchars($user["username"]) ?></h2>
            <p><?= htmlspecialchars($user["name"]) ?> <?= htmlspecialchars($user["surname"]) ?></p>
            <p><?= htmlspecialchars($user["email"]) ?></p>
            <?php if ($user["teacher"]): ?>
                <span class="badge teacher">Admin / Učitel</span>
            <?php endif; ?>
        </div>
    </section>

    <!-- ── Stats ────────────────────────────────────────── -->
    <section class="stats">
        <h3>Statistiky</h3>
        <ul>
            <li><strong><?= (int)$user["highscore_1gm"] ?></strong>Nejlepší skóre — Time Attack</li>
            <li><strong><?= (int)$user["highscore_2gm"] ?></strong>Nejlepší streak — Training Lab</li>
            <li><strong><?= (int)$user["Q_answerd"] ?></strong>Celkově zodpovězeno</li>
            <li><strong><?= (int)$user["Q_correct"] ?></strong>Správně zodpovězeno</li>
            <li><strong><?= $accuracy ?>%</strong>Přesnost odpovědí</li>
        </ul>
    </section>

    <!-- ── Badges ───────────────────────────────────────── -->
    <section class="achievements">
        <h3>Achievementy (<?= count($achievements) ?>)</h3>
        <?php if (count($achievements) > 0): ?>
            <ul class="badge-list">
                <?php foreach ($achievements as $a): ?>
                    <li class="badge-item rarity-<?= htmlspecialchars($a["rarity"]) ?>">
                        <strong><?= htmlspecialchars($a["Name"]) ?></strong>
                        <span class="rarity"><?= htmlspecialchars($a["rarity"]) ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>Zatím žádné achievementy – hraj a odemykej je!</p>
        <?php endif; ?>
    </section>

    <!-- ── Game history ─────────────────────────────────── -->
    <section class="history">
        <h3>Posledních 10 her</h3>
        <?php if (count($history) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Datum</th>
                        <th>Mód</th>
                        <th>Správně</th>
                        <th>Chybně</th>
                        <th>Streak</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($history as $g): ?>
                    <tr>
                        <td><?= htmlspecialchars($g["When_Played"]) ?></td>
                        <td><?= $g["Gm"] == 1 ? "⚡ Time Attack" : "🧪 Training Lab" ?></td>
                        <td><?= (int)$g["Q_AC"] ?></td>
                        <td><?= (int)$g["Q_AW"] ?></td>
                        <td><?= (int)$g["streak"] ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>Ještě jsi nehrál/a žádnou hru.</p>
        <?php endif; ?>
    </section>

</main>

</body>
</html>
