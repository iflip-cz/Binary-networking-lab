<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}
require "../backend/funcDB.php";
$pdo = connectDB();
$leaderboard = getLeaderboard($pdo, 1, 10);

// First letter of username for the avatar chip
$initial = strtoupper(substr($_SESSION["username"], 0, 1));
?>
<!doctype html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu — Binary Networking Lab</title>
    <link rel="stylesheet" href="s.css/mainMenu.css">
</head>
<body>

<header>
    <h1>BNL</h1>
    <nav>
        <!-- Profile avatar chip top-right -->
        <a href="profil.php" class="nav-avatar">
            <span class="avatar-init"><?= $initial ?></span>
            <span class="avatar-name"><?= htmlspecialchars($_SESSION["username"]) ?></span>
        </a>
        <?php if ($_SESSION["teacher"]): ?>
            <a href="admin.php">Admin</a>
        <?php endif; ?>
        <a href="../backend/logout.php">Odhlásit se</a>
    </nav>
</header>

<main>
    <section class="game-modes">
        <h2>Vyber herní mód</h2>
        <div class="mode-grid">
            <a href="lesson.php?mode=1&amp;time=30" class="mode-card">
                <h3>⚡ Time Attack</h3>
                <span class="badge">30 s — ICMP Ping</span>
                <p>Rychlostní sprint. Správné odpovědi v čistých 30 vteřinách.</p>
            </a>
            <a href="lesson.php?mode=1&amp;time=60" class="mode-card">
                <h3>⚡ Time Attack</h3>
                <span class="badge">60 s — TCP Handshake</span>
                <p>Standardní hra. Vyvážený čas i obtížnost.</p>
            </a>
            <a href="lesson.php?mode=1&amp;time=120" class="mode-card">
                <h3>⚡ Time Attack</h3>
                <span class="badge">120 s — Packet Flood</span>
                <p>Maratonský výkon. Vydrž a překonej sám sebe.</p>
            </a>
            <a href="lesson.php?mode=2" class="mode-card">
                <h3>🧪 Training Lab</h3>
                <span class="badge">Bez limitu</span>
                <p>Jedna chyba ukončí streak. Soustřeď se na přesnost.</p>
            </a>
        </div>
    </section>

    <section class="leaderboard">
        <h2>🏆 Žebříček — Time Attack</h2>
        <?php if (count($leaderboard) > 0): ?>
        <table>
            <thead>
                <tr><th>#</th><th>Hráč</th><th>Nejlepší skóre</th></tr>
            </thead>
            <tbody>
                <?php foreach ($leaderboard as $i => $row): ?>
                <tr <?= ($row["username"] === $_SESSION["username"]) ? 'class="highlight"' : '' ?>>
                    <td><?= $i + 1 ?></td>
                    <td><?= $row["anonym"] ? "Anonym" : htmlspecialchars($row["username"]) ?></td>
                    <td><?= (int)$row["highscore"] ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
            <p class="empty-state">Žebříček je zatím prázdný — buď první!</p>
        <?php endif; ?>
    </section>
</main>

<footer class="page-footer">
    <span>© 2026 Binary Networking Lab</span>
    <span class="sep">·</span>
    <a href="profil.php">Profil</a>
    <span class="sep">·</span>
    <a href="../backend/logout.php">Odhlásit se</a>
</footer>

</body>
</html>