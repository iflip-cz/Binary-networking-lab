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

    <section class="game-modes">
        <h2>game modes</h2>
        <div class="mode-grid">

            <div class="mode-card" data-mode="1" tabindex="0" role="button">
                <h3 class="card-name">Time Attack</h3>
                <div class="card-times">30 · 60 · 120 s</div>
                <p>Odpovídej co nejrychleji v časovém limitu. Každá správná odpověď = bod.</p>
            </div>

            <div class="mode-card" data-mode="3" tabindex="0" role="button">
                <h3 class="card-name">Streak Challenge</h3>
                <div class="card-times">∞</div>
                <p>Buduj co nejdelší sérii. Chyba streak resetuje — hra ale pokračuje.</p>
            </div>

            <div class="mode-card mode-card--free" data-mode="2" tabindex="0" role="button">
                <h3 class="card-name">Training Lab</h3>
                <div class="card-times">∞</div>
                <p>Volné procvičování bez omezení. Po chybě uvidíš správnou odpověď.</p>
            </div>

        </div>
    </section>

    <section class="leaderboard">
        <h2>leaderboard</h2>
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

<!-- ── Game config modal ──────────────────────────── -->
<div id="modal-overlay" class="modal-overlay hidden" role="dialog" aria-modal="true">
    <div class="modal" id="modal-box">

        <button class="modal-close" id="modal-close" aria-label="Zavřít">✕</button>

        <h3 id="modal-title">Time Attack</h3>
        <p id="modal-desc" class="modal-desc">Popis módu.</p>

        <!-- Time picker — only for Time Attack (mode 1) -->
        <div class="cfg-group" id="time-group">
            <span class="cfg-label">čas</span>
            <div class="cfg-row" id="time-btns">
                <button class="cfg-btn" data-time="30">30 s</button>
                <button class="cfg-btn active" data-time="60">60 s</button>
                <button class="cfg-btn" data-time="120">120 s</button>
            </div>
        </div>

        <!-- System picker — all modes -->
        <div class="cfg-group">
            <span class="cfg-label">soustava</span>
            <div class="cfg-row" id="stype-btns">
                <button class="cfg-btn active" data-stype="all">vše</button>
                <button class="cfg-btn" data-stype="bin">bin</button>
                <button class="cfg-btn" data-stype="hex">hex</button>
                <button class="cfg-btn" data-stype="oct">oct</button>
            </div>
        </div>

        <button class="modal-play" id="modal-play">Hrát →</button>
    </div>
</div>

<script>
const MODES = {
    1: { title: "Time Attack",      desc: "Odpovídej co nejrychleji. Jedno správné = jeden bod. Čas vyprší — hra skončí.",  showTime: true  },
    2: { title: "Training Lab",     desc: "Žádné limity. Po každé chybě uvidíš správnou odpověď — procvičuj v klidu.",     showTime: false },
    3: { title: "Streak Challenge", desc: "Odpovídej správně a buduj streak. Chyba ho resetuje — ale hra pokračuje dál.", showTime: false },
};

let currentMode  = 1;
let selectedTime  = parseInt(sessionStorage.getItem('lastTime')  || '60');
let selectedStype = sessionStorage.getItem('trainType') || 'all';

const overlay    = document.getElementById('modal-overlay');
const timeBtns   = document.querySelectorAll('#time-btns  .cfg-btn');
const stypeBtns  = document.querySelectorAll('#stype-btns .cfg-btn');

// Restore saved state
setActive(timeBtns,  String(selectedTime),  'data-time');
setActive(stypeBtns, selectedStype,          'data-stype');

// Open modal on card click or Enter key
document.querySelectorAll('.mode-card').forEach(card => {
    card.addEventListener('click',   () => open(parseInt(card.dataset.mode)));
    card.addEventListener('keydown', e => { if (e.key === 'Enter' || e.key === ' ') open(parseInt(card.dataset.mode)); });
});

function open(mode) {
    currentMode = mode;
    const cfg = MODES[mode];
    document.getElementById('modal-title').textContent = cfg.title;
    document.getElementById('modal-desc').textContent  = cfg.desc;
    document.getElementById('time-group').style.display = cfg.showTime ? '' : 'none';
    overlay.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
    document.getElementById('modal-play').focus();
}

function close() {
    overlay.classList.add('hidden');
    document.body.style.overflow = '';
}

document.getElementById('modal-close').addEventListener('click', close);
overlay.addEventListener('click', e => { if (e.target === overlay) close(); });
document.addEventListener('keydown', e => { if (e.key === 'Escape') close(); });

// Time selection
timeBtns.forEach(btn => btn.addEventListener('click', () => {
    setActive(timeBtns, btn.dataset.time, 'data-time');
    selectedTime = parseInt(btn.dataset.time);
    sessionStorage.setItem('lastTime', selectedTime);
}));

// Stype selection
stypeBtns.forEach(btn => btn.addEventListener('click', () => {
    setActive(stypeBtns, btn.dataset.stype, 'data-stype');
    selectedStype = btn.dataset.stype;
    sessionStorage.setItem('trainType', selectedStype);
}));

// Play
document.getElementById('modal-play').addEventListener('click', () => {
    let url = `lesson.php?mode=${currentMode}&type=${selectedStype}`;
    if (currentMode === 1) url += `&time=${selectedTime}`;
    window.location.href = url;
});

function setActive(btns, val, attr) {
    btns.forEach(b => b.classList.toggle('active', b.getAttribute(attr) === val));
}
</script>

</body>
</html>