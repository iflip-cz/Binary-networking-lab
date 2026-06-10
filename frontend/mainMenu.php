<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}
require "../backend/funcDB.php";
$pdo         = connectDB();
$leaderboard = getLeaderboard($pdo, 1, 10);
$initial     = strtoupper(substr($_SESSION["username"], 0, 1));
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
    <h1><span class="prompt">//</span> Binary Networking Lab</h1>
    <nav>
        <a href="profil.php" class="nav-avatar">
            <span class="avatar-init"><?= $initial ?></span>
            <span class="avatar-name"><?= htmlspecialchars($_SESSION["username"]) ?></span>
        </a>
        <?php if ($_SESSION["teacher"]): ?>
            <a href="admin.php" class="nav-link">admin</a>
        <?php endif; ?>
        <a href="../backend/logout.php" class="nav-link">odhlásit</a>
    </nav>
</header>

<main>

    <section class="type-section">
        <span class="type-label">procvičovat</span>
        <div class="type-btns">
            <button class="type-btn active" data-type="all">vše</button>
            <button class="type-btn" data-type="bin">bin</button>
            <button class="type-btn" data-type="hex">hex</button>
            <button class="type-btn" data-type="oct">oct</button>
        </div>
    </section>

    <section class="game-modes">
        <h2>game modes</h2>
        <div class="mode-grid">

            <a href="lesson.php?mode=1&amp;time=30" class="mode-card" data-base="lesson.php?mode=1&time=30">
                <h3 class="card-name">ICMP Ping</h3>
                <div class="card-time">30 s</div>
                <span class="card-cat">Time Attack</span>
                <p>Rychlostní sprint — co nejvíce správných v 30 vteřinách.</p>
            </a>

            <a href="lesson.php?mode=1&amp;time=60" class="mode-card" data-base="lesson.php?mode=1&time=60">
                <h3 class="card-name">TCP Handshake</h3>
                <div class="card-time">60 s</div>
                <span class="card-cat">Time Attack</span>
                <p>Standardní délka — vyvážená obtížnost i čas.</p>
            </a>

            <a href="lesson.php?mode=1&amp;time=120" class="mode-card" data-base="lesson.php?mode=1&time=120">
                <h3 class="card-name">Packet Flood</h3>
                <div class="card-time">120 s</div>
                <span class="card-cat">Time Attack</span>
                <p>Maratonský výkon — překonej sám sebe.</p>
            </a>

            <a href="lesson.php?mode=3" class="mode-card" data-base="lesson.php?mode=3">
                <h3 class="card-name">Streak Challenge</h3>
                <div class="card-time">∞</div>
                <span class="card-cat">Streak mód</span>
                <p>Jedna chyba resetuje streak. Hra pokračuje dál.</p>
            </a>

            <a href="lesson.php?mode=2" class="mode-card mode-card--free" data-base="lesson.php?mode=2">
                <h3 class="card-name">Training Lab</h3>
                <div class="card-time">∞</div>
                <span class="card-cat">Volná hra</span>
                <p>Žádné limity. Po chybě uvidíš správnou odpověď.</p>
            </a>

        </div>
    </section>

    <section class="leaderboard">
        <h2>leaderboard — time attack</h2>
        <?php if (count($leaderboard) > 0): ?>
        <table>
            <thead>
                <tr><th>#</th><th>hráč</th><th>skóre</th></tr>
            </thead>
            <tbody>
                <?php foreach ($leaderboard as $i => $row): ?>
                <tr <?= ($row["username"] === $_SESSION["username"]) ? 'class="highlight"' : '' ?>>
                    <td><?= $i + 1 ?></td>
                    <td><?= $row["anonym"] ? "anonym" : htmlspecialchars($row["username"]) ?></td>
                    <td><?= (int)$row["highscore"] ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
            <p class="empty-state">žebříček je zatím prázdný — buď první</p>
        <?php endif; ?>
    </section>

</main>

<footer class="page-footer">
    <span>Binary Networking Lab</span>
    <span class="sep">·</span>
    <span class="footer-ver">v0.1 · školní projekt ZWA</span>
    <span class="sep">·</span>
    <a href="profil.php">profil</a>
    <span class="sep">·</span>
    <a href="../backend/logout.php">odhlásit se</a>
</footer>

<script>
const typeBtns  = document.querySelectorAll('.type-btn');
const modeCards = document.querySelectorAll('.mode-card');

let selectedType = sessionStorage.getItem('trainType') || 'all';
applyType(selectedType);
typeBtns.forEach(b => b.classList.toggle('active', b.dataset.type === selectedType));

typeBtns.forEach(btn => {
    btn.addEventListener('click', () => {
        typeBtns.forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        selectedType = btn.dataset.type;
        sessionStorage.setItem('trainType', selectedType);
        applyType(selectedType);
    });
});

function applyType(type) {
    modeCards.forEach(card => {
        const base = card.dataset.base;
        card.href = base + (base.includes('?') ? '&' : '?') + 'type=' + type;
    });
}
</script>

</body>
</html>