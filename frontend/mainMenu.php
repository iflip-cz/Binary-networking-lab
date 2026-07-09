<?php
session_start();
if (!isset($_SESSION["user_id"])) { header("Location: login.php"); exit; }
require "../backend/funcDB.php";
$pdo = connectDB();

// Initial leaderboard view (Time Attack 30s, all systems). Other views load via
// backend/leaderboard.php as the user switches tab / number-system filter.
$lbInit = getTimeAttackLeaderboard($pdo, 30, 'all', 10);
$initial       = strtoupper(substr($_SESSION["username"], 0, 1));
$isTeacher     = (int)$_SESSION["teacher"] === 1;

// ── Handle class actions ────────────────────────────
$classMsg = "";

// Student: join class
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["join_code"])) {
    $result = joinClassByCode($pdo, $_POST["join_code"], $_SESSION["user_id"]);
    $classMsgMap = [
        'not_found'      => "Kód třídy nebyl nalezen.",
        'already_member' => "Jsi už v této třídě.",
        'is_teacher'     => "Nemůžeš vstoupit do vlastní třídy.",
    ];
    // success ($result is an int class id) falls through to "" and redirects below
    $classMsg = (is_string($result) && isset($classMsgMap[$result])) ? $classMsgMap[$result] : "";
    if (is_int($result)) {
        header("Location: mainMenu.php");
        exit;
    }
}

// Teacher: create class
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["class_name"]) && $isTeacher) {
    $name = trim($_POST["class_name"]);
    if ($name !== "") {
        createClass($pdo, $_SESSION["user_id"], $name);
        header("Location: mainMenu.php");
        exit;
    }
}

// Teacher: delete class
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["delete_class"]) && $isTeacher) {
    deleteClass($pdo, (int)$_POST["delete_class"], $_SESSION["user_id"]);
    header("Location: mainMenu.php");
    exit;
}

// Load classes
$teacherClasses = $isTeacher ? getTeacherClasses($pdo, $_SESSION["user_id"]) : [];
$studentClasses = !$isTeacher ? getStudentClasses($pdo, $_SESSION["user_id"]) : [];
?>
<!doctype html>
<html lang="cs" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu — Binary Networking Lab</title>
    <script>document.documentElement.setAttribute('data-theme',localStorage.getItem('bnl-theme')||'dark');</script>
    <meta name="theme-color" content="#0d0f14">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 64 64%22><rect width=%2264%22 height=%2264%22 rx=%2214%22 fill=%22%230d0f14%22/><text x=%2232%22 y=%2244%22 font-family=%22monospace%22 font-size=%2230%22 font-weight=%22700%22 text-anchor=%22middle%22 fill=%22%23f97316%22>01</text></svg>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="s.css/mainMenu.css">
</head>
<body>

<header>
    <h1><span class="prompt">//</span> Binary Networking Lab</h1>
    <nav>
        <button class="theme-btn" id="theme-toggle">[ light ]</button>
        <a href="profil.php" class="nav-avatar">
            <span class="avatar-init"><?= htmlspecialchars($initial) ?></span>
            <span class="avatar-name"><?= htmlspecialchars($_SESSION["username"]) ?></span>
        </a>
        <a href="../backend/logout.php" class="nav-link">odhlásit</a>
    </nav>
</header>

<main>

    <!-- ── Game modes ────────────────────────────────── -->
    <section class="game-modes">
        <h2>Game Modes</h2>
        <div class="mode-grid">
            <div class="mode-card" data-mode="1" tabindex="0" role="button">
                <h3 class="card-name">Time Attack</h3>
                <div class="card-times">30 · 60 · 120 s</div>
                <p>Odpovídej co nejrychleji. Každá správná odpověď = bod.</p>
            </div>
            <div class="mode-card" data-mode="3" tabindex="0" role="button">
                <h3 class="card-name">Streak Challenge</h3>
                <div class="card-times">∞</div>
                <p>Buduj co nejdelší sérii. Chyba streak resetuje — hra pokračuje.</p>
            </div>
            <div class="mode-card mode-card--free" data-mode="2" tabindex="0" role="button">
                <h3 class="card-name">Training Lab</h3>
                <div class="card-times">∞</div>
                <p>Volné procvičování. Po chybě uvidíš správnou odpověď i postup.</p>
            </div>
        </div>
    </section>

    <!-- ── Leaderboards (Time Attack per duration + Streak, filterable by system) ── -->
    <section class="leaderboard-section">
        <h2 class="lb-title">Leaderboard</h2>
        <div class="lb-controls">
            <div class="lb-tabs">
                <button class="lb-tab active" data-kind="ta" data-seconds="30">TA 30s</button>
                <button class="lb-tab" data-kind="ta" data-seconds="60">TA 60s</button>
                <button class="lb-tab" data-kind="ta" data-seconds="120">TA 120s</button>
                <button class="lb-tab" data-kind="streak">Streak</button>
            </div>
            <div class="lb-filter">
                <label class="lb-filter-label" for="lb-sys">soustava</label>
                <select id="lb-sys" class="lb-select">
                    <option value="all">vše — všechny soustavy</option>
                    <option value="bin">bin — binární</option>
                    <option value="hex">hex — hexadecimální</option>
                    <option value="oct">oct — oktalová</option>
                </select>
            </div>
        </div>

        <div id="lb-body" class="lb-table-wrap">
            <?php if (count($lbInit) > 0): ?>
            <table>
                <thead><tr><th>#</th><th>Hráč</th><th>Skóre</th></tr></thead>
                <tbody>
                    <?php foreach ($lbInit as $i => $r): ?>
                    <tr <?= $r["username"] === $_SESSION["username"] ? 'class="highlight"' : '' ?>>
                        <td><?= $i+1 ?></td>
                        <td><?= $r["anonym"] ? "anonym" : htmlspecialchars($r["username"]) ?></td>
                        <td><?= (int)$r["highscore"] ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?><p class="empty-state">Zatím žádné záznamy.</p><?php endif; ?>
        </div>
    </section>

    <!-- ── Classes ───────────────────────────────────── -->
    <section class="classes-section">
        <h2><?= $isTeacher ? "Moje třídy" : "Třídy" ?></h2>

        <?php if ($classMsg): ?>
            <p class="class-msg-error"><?= htmlspecialchars($classMsg) ?></p>
        <?php endif; ?>

        <?php if ($isTeacher): ?>
            <!-- Teacher: list own classes + create form -->
            <div class="class-list">
                <?php foreach ($teacherClasses as $c): ?>
                <div class="class-card">
                    <div class="cc-info">
                        <strong><?= htmlspecialchars($c["name"]) ?></strong>
                        <span class="cc-code"><?= htmlspecialchars($c["code"]) ?></span>
                        <span class="cc-count"><?= (int)$c["member_count"] ?> studentů</span>
                    </div>
                    <div class="cc-actions">
                        <a href="class_view.php?id=<?= $c["ID_class"] ?>" class="cc-btn">Zobrazit →</a>
                        <form method="post" style="display:inline;" onsubmit="return confirm('Smazat třídu?')">
                            <input type="hidden" name="delete_class" value="<?= $c["ID_class"] ?>">
                            <button type="submit" class="cc-btn-del">Smazat</button>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php if (empty($teacherClasses)): ?>
                    <p class="empty-state">Zatím žádné třídy.</p>
                <?php endif; ?>
            </div>

            <form method="post" class="class-create-form">
                <input type="text" name="class_name" placeholder="Název nové třídy" maxlength="60" required>
                <button type="submit">Vytvořit třídu</button>
            </form>

        <?php else: ?>
            <!-- Student: list joined classes + join form -->
            <div class="class-list">
                <?php foreach ($studentClasses as $c): ?>
                <div class="class-card">
                    <div class="cc-info">
                        <strong><?= htmlspecialchars($c["name"]) ?></strong>
                        <span class="cc-count">Učitel: <?= htmlspecialchars($c["teacher_name"]) ?></span>
                    </div>
                    <a href="class_view.php?id=<?= $c["ID_class"] ?>" class="cc-btn">Žebříček →</a>
                </div>
                <?php endforeach; ?>
                <?php if (empty($studentClasses)): ?>
                    <p class="empty-state">Zatím v žádné třídě.</p>
                <?php endif; ?>
            </div>

            <form method="post" class="class-create-form">
                <input type="text" name="join_code" placeholder="Kód třídy (např. XK3M2P)"
                       maxlength="8" style="text-transform:uppercase;" required>
                <button type="submit">Připojit se</button>
            </form>
        <?php endif; ?>
    </section>

</main>

<footer class="page-footer">
    <span>Binary Networking Lab</span>
    <span class="sep">·</span>
    <span class="footer-ver">v0.2 · ZWA projekt</span>
    <span class="sep">·</span>
    <a href="profil.php">profil</a>
    <span class="sep">·</span>
    <a href="../backend/logout.php">odhlásit se</a>
</footer>

<!-- ── Game config modal ─────────────────────────────── -->
<div id="modal-overlay" class="modal-overlay hidden">
    <div class="modal">
        <button class="modal-close" id="modal-close">✕</button>
        <h3 id="modal-title">Time Attack</h3>
        <p id="modal-desc" class="modal-desc"></p>
        <div class="cfg-group" id="time-group">
            <span class="cfg-label">čas</span>
            <div class="cfg-row" id="time-btns">
                <button class="cfg-btn" data-time="30">30 s</button>
                <button class="cfg-btn active" data-time="60">60 s</button>
                <button class="cfg-btn" data-time="120">120 s</button>
            </div>
        </div>
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
// ── Theme ─────────────────────────────────────────────
(function() {
    const saved = localStorage.getItem('bnl-theme') || 'dark';
    document.getElementById('theme-toggle').textContent = saved === 'dark' ? '[ light ]' : '[ dark ]';
})();
document.getElementById('theme-toggle').addEventListener('click', function() {
    const curr = document.documentElement.getAttribute('data-theme');
    const next = curr === 'dark' ? 'light' : 'dark';
    document.documentElement.setAttribute('data-theme', next);
    localStorage.setItem('bnl-theme', next);
    this.textContent = next === 'dark' ? '[ light ]' : '[ dark ]';
});

// ── Leaderboard (tabs + number-system filter, loaded from backend) ──
const ME = <?= json_encode($_SESSION['username']) ?>;
// Remember the last viewed board across page loads (e.g. returning from a game).
let lbKind    = sessionStorage.getItem('lbKind') || 'ta';
let lbSeconds = parseInt(sessionStorage.getItem('lbSeconds') || '30');
let lbSys     = sessionStorage.getItem('lbSys')  || 'all';
const lbBody  = document.getElementById('lb-body');

function lbEsc(s) { const d = document.createElement('div'); d.textContent = s; return d.innerHTML; }

function lbRender(rows) {
    const metric = lbKind === 'streak' ? 'Streak' : 'Skóre';
    if (!rows.length) return '<p class="empty-state">Zatím žádné záznamy — zahraj si a buď první.</p>';
    let h = '<table><thead><tr><th>#</th><th>Hráč</th><th>' + metric + '</th></tr></thead><tbody>';
    rows.forEach((r, i) => {
        const name = Number(r.anonym) ? 'anonym' : lbEsc(r.username);
        const hl = r.username === ME ? ' class="highlight"' : '';
        h += '<tr' + hl + '><td>' + (i + 1) + '</td><td>' + name + '</td><td>' + (parseInt(r.highscore) || 0) + '</td></tr>';
    });
    return h + '</tbody></table>';
}

function lbLoad() {
    sessionStorage.setItem('lbKind', lbKind);
    sessionStorage.setItem('lbSeconds', lbSeconds);
    sessionStorage.setItem('lbSys', lbSys);
    lbBody.classList.add('lb-loading');
    lbBody.setAttribute('aria-busy', 'true');
    const p = new URLSearchParams({ kind: lbKind, sys: lbSys });
    if (lbKind === 'ta') p.set('seconds', lbSeconds);
    fetch('../backend/leaderboard.php?' + p.toString())
        .then(r => { if (!r.ok) throw new Error(r.status); return r.json(); })
        .then(rows => { lbBody.innerHTML = lbRender(rows); })
        .catch(() => {
            lbBody.innerHTML = '<p class="empty-state">Žebříček se nepodařilo načíst — zkus přepnout záložku.</p>';
        })
        .finally(() => {
            lbBody.classList.remove('lb-loading');
            lbBody.removeAttribute('aria-busy');
        });
}

const lbSysSel = document.getElementById('lb-sys');

function lbSyncButtons() {
    document.querySelectorAll('.lb-tab').forEach(t => {
        const isActive = t.dataset.kind === lbKind &&
            (lbKind !== 'ta' || parseInt(t.dataset.seconds) === lbSeconds);
        t.classList.toggle('active', isActive);
    });
    lbSysSel.value = lbSys;
}

document.querySelectorAll('.lb-tab').forEach(tab => {
    tab.addEventListener('click', () => {
        lbKind = tab.dataset.kind;
        if (lbKind === 'ta') lbSeconds = parseInt(tab.dataset.seconds);
        lbSyncButtons();
        lbLoad();
    });
});
lbSysSel.addEventListener('change', () => {
    lbSys = lbSysSel.value;
    lbLoad();
});

// Restore the remembered view (server renders TA-30s/vše by default).
if (lbKind !== 'ta' || lbSeconds !== 30 || lbSys !== 'all') {
    lbSyncButtons();
    lbLoad();
}

// ── Game modal ────────────────────────────────────────
const MODES = {
    1: {title:"Time Attack",      desc:"Odpovídej co nejrychleji v časovém limitu.",      showTime:true},
    2: {title:"Training Lab",     desc:"Procvičuj bez omezení. Špatná odpověď ukáže postup řešení.", showTime:false},
    3: {title:"Streak Challenge", desc:"Buduj sérii. Chyba ji resetuje — ale hra pokračuje.",     showTime:false},
};

let currentMode=1, selectedTime=parseInt(sessionStorage.getItem('lastTime')||'60'),
    selectedStype=sessionStorage.getItem('trainType')||'all';

const overlay   = document.getElementById('modal-overlay');
const timeBtns  = document.querySelectorAll('#time-btns  .cfg-btn');
const stypeBtns = document.querySelectorAll('#stype-btns .cfg-btn');

setActive(timeBtns,  String(selectedTime),  'data-time');
setActive(stypeBtns, selectedStype,          'data-stype');

document.querySelectorAll('.mode-card').forEach(card => {
    card.addEventListener('click',   () => openModal(parseInt(card.dataset.mode)));
    card.addEventListener('keydown', e => { if (e.key==='Enter'||e.key===' ') openModal(parseInt(card.dataset.mode)); });
});

function openModal(mode) {
    currentMode = mode;
    const cfg = MODES[mode];
    document.getElementById('modal-title').textContent = cfg.title;
    document.getElementById('modal-desc').textContent  = cfg.desc;
    document.getElementById('time-group').style.display = cfg.showTime ? '' : 'none';
    overlay.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closeModal() { overlay.classList.add('hidden'); document.body.style.overflow = ''; }

document.getElementById('modal-close').addEventListener('click', closeModal);
overlay.addEventListener('click', e => { if (e.target===overlay) closeModal(); });
document.addEventListener('keydown', e => { if (e.key==='Escape') closeModal(); });

timeBtns.forEach(btn => btn.addEventListener('click', () => {
    setActive(timeBtns, btn.dataset.time, 'data-time');
    selectedTime = parseInt(btn.dataset.time);
    sessionStorage.setItem('lastTime', selectedTime);
}));

stypeBtns.forEach(btn => btn.addEventListener('click', () => {
    setActive(stypeBtns, btn.dataset.stype, 'data-stype');
    selectedStype = btn.dataset.stype;
    sessionStorage.setItem('trainType', selectedStype);
}));

document.getElementById('modal-play').addEventListener('click', () => {
    let url = `lesson.php?mode=${currentMode}&type=${selectedStype}`;
    if (currentMode===1) url += `&time=${selectedTime}`;
    window.location.href = url;
});

function setActive(btns, val, attr) {
    btns.forEach(b => b.classList.toggle('active', b.getAttribute(attr)===val));
}
</script>
</body>
</html>