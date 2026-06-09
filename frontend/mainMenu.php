<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

require "../backend/funcDB.php";
$pdo = connectDB();

// Leaderboard for Time Attack (game_mode = 1) — shown by default
$leaderboard = getLeaderboard($pdo, 1, 10);
?>
<!doctype html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hlavní menu – Binary Networking Lab</title>
    <link rel="stylesheet" href="s.css/mainMenu.css">
</head>
<body>

<header>
    <h1>Binary Networking Lab</h1>
    <nav>
        <a href="profil.php">👤 <?= htmlspecialchars($_SESSION["username"]) ?></a>
        <?php if ($_SESSION["teacher"]): ?>
            <a href="admin.php">⚙️ Admin</a>
        <?php endif; ?>
        <a href="../backend/logout.php">Odhlásit se</a>
    </nav>
</header>

<main>

    <!-- ── Game modes ────────────────────────────────────── -->
    <section class="game-modes">
        <h2>Vyber herní mód</h2>
        <div class="mode-grid">

            <a href="lesson.php?mode=1&amp;time=30" class="mode-card">
                <h3>⚡ Time Attack</h3>
                <span class="badge">30 s — ICMP Ping</span>
                <p>Rychlostní sprint. Co nejvíce správných odpovědí za 30 sekund.</p>
            </a>

            <a href="lesson.php?mode=1&amp;time=60" class="mode-card">
                <h3>⚡ Time Attack</h3>
                <span class="badge">60 s — TCP Handshake</span>
                <p>Standardní hra. Vyvážená obtížnost a čas.</p>
            </a>

            <a href="lesson.php?mode=1&amp;time=120" class="mode-card">
                <h3>⚡ Time Attack</h3>
                <span class="badge">120 s — Packet Flood</span>
                <p>Maratonský výkon. Vydrž a překonej sám sebe.</p>
            </a>

            <a href="lesson.php?mode=2" class="mode-card">
                <h3>🧪 Training Lab</h3>
                <span class="badge">Bez limitu</span>
                <p>Klid a přesnost. Soustřeď se na streak — jedna chyba ho ukončí.</p>
            </a>

        </div>
    </section>

    <!-- ── Leaderboard ───────────────────────────────────── -->
    <section class="leaderboard">
        <h2>🏆 Žebříček — Time Attack</h2>

        <?php if (count($leaderboard) > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Hráč</th>
                    <th>Nejlepší skóre</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($leaderboard as $i => $row): ?>
                <tr <?= ($row["username"] === $_SESSION["username"]) ? 'class="highlight"' : '' ?>>
                    <td><?= $i + 1 ?></td>
                    <td>
                        <?= $row["anonym"]
                            ? "Anonym"
                            : htmlspecialchars($row["username"]) ?>
                    </td>
                    <td><?= (int)$row["highscore"] ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
            <p class="empty-state">Žebříček je zatím prázdný – buď první, kdo ho zaplní!</p>
        <?php endif; ?>
    </section>

</main>

</body>
</html>
