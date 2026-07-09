<?php
session_start();
if (!isset($_SESSION["user_id"])) { header("Location: login.php"); exit; }

$mode = in_array((int)($_GET["mode"] ?? 1), [1,2,3]) ? (int)($_GET["mode"] ?? 1) : 1;
$time = in_array((int)($_GET["time"] ?? 60), [30,60,120]) ? (int)($_GET["time"] ?? 60) : 60;
$type = in_array($_GET["type"] ?? "all", ["all","bin","hex","oct"]) ? ($_GET["type"] ?? "all") : "all";

$modeLabels = [1 => "Time Attack", 2 => "Training Lab", 3 => "Streak Challenge"];
$modeLabel  = $modeLabels[$mode] . ($mode === 1 ? " — {$time}s" : "");
?>
<!doctype html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($modeLabel) ?> — BNL</title>
    <script>document.documentElement.setAttribute('data-theme',localStorage.getItem('bnl-theme')||'dark');</script>
    <meta name="theme-color" content="#0d0f14">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 64 64%22><rect width=%2264%22 height=%2264%22 rx=%2214%22 fill=%22%230d0f14%22/><text x=%2232%22 y=%2244%22 font-family=%22monospace%22 font-size=%2230%22 font-weight=%22700%22 text-anchor=%22middle%22 fill=%22%23f97316%22>01</text></svg>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="s.css/lesson.css">
</head>
<body>

<header>
    <a href="mainMenu.php" id="btn-back">← Menu</a>
    <h1 id="mode-title"><?= htmlspecialchars($modeLabel) ?></h1>

    <div class="header-right">
        <button class="theme-btn" id="theme-toggle">[ light ]</button>

        <?php if ($mode === 1): ?>
            <div id="timer" class="timer"><?= $time ?></div>
        <?php elseif ($mode === 3): ?>
            <div class="streak-hud">
                <div class="streak-now" id="streak-count">0</div>
                <div class="streak-best">best <span id="streak-best">0</span></div>
            </div>
            <button id="btn-stop" onclick="endGame()">Ukončit</button>
        <?php else: ?>
            <button id="btn-stop" onclick="endGame()">Ukončit</button>
        <?php endif; ?>
    </div>
</header>

<main id="game-area">
    <div id="question-box">
        <span id="question-label" class="q-label">—</span>
        <p id="question-value" class="q-value">…</p>
    </div>

    <!-- Inline answer row: input + submit attached -->
    <div class="answer-row">
        <input type="text" id="answer-input" placeholder="tvoje odpověď"
               autocomplete="off" spellcheck="false">
        <button id="btn-submit" title="Odeslat (Enter)">↵</button>
    </div>

    <p id="feedback" class="feedback"></p>

    <!-- Explain panel — only shown after wrong answer in modes 2 & 3 -->
    <div id="explain-wrap" class="explain-wrap">
        <button id="btn-explain" class="btn-explain">[ ? ] jak na to</button>
        <div id="explain-panel" class="explain-panel"></div>
    </div>

    <div id="score-bar">
        <span class="sc-lbl">správně</span>
        <span id="score-correct" class="sc-val ok">0</span>
        <span class="sc-sep">/</span>
        <span class="sc-lbl">špatně</span>
        <span id="score-wrong" class="sc-val bad">0</span>
    </div>
</main>

<div id="overlay-gameover" class="overlay hidden">
    <div class="overlay-box">
        <h2>Hra skončila</h2>
        <div class="results">
            <div class="result-row">
                <span class="rl">Správně</span>
                <strong class="rv ok" id="result-correct">0</strong>
            </div>
            <div class="result-row">
                <span class="rl">Špatně</span>
                <strong class="rv bad" id="result-wrong">0</strong>
            </div>
            <div class="result-row" id="result-streak-line">
                <span class="rl">Nejdelší streak</span>
                <strong class="rv streak" id="result-streak">0</strong>
            </div>
        </div>

        <div id="new-achv" class="new-achv hidden"></div>

        <div class="overlay-btns">
            <button onclick="window.location.href='mainMenu.php'">← Menu</button>
            <button id="btn-play-again" class="btn-primary">Hrát znovu</button>
        </div>
    </div>
</div>

<script>
// ── Config ────────────────────────────────────────────
const GAME_MODE  = <?= $mode ?>;
const TIME_LIMIT = <?= $time ?>;
const TRAIN_TYPE = "<?= $type ?>";
const USER_ID    = <?= (int)$_SESSION["user_id"] ?>;

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

// ── State ─────────────────────────────────────────────
let correct = 0, wrong = 0, streak = 0, maxStreak = 0;
let timeLeft = TIME_LIMIT, timerInterval = null;
let currentQuestion = null, gameActive = true;

// ── Question pool ─────────────────────────────────────
const POOLS = {
    bin: ["bin2dec","dec2bin"],
    hex: ["hex2dec","dec2hex","bin2hex","hex2bin"],
    oct: ["oct2dec","dec2oct"]
} 

function pickType() {
    const pool = TRAIN_TYPE === "all"
        ? [...POOLS.bin,...POOLS.hex,...POOLS.oct]
        : POOLS[TRAIN_TYPE];
    return pool[Math.floor(Math.random() * pool.length)];
}

function generateQuestion() {
    const t = pickType();
    const n = Math.floor(Math.random() * 256);
    const hex = n.toString(16).toUpperCase().padStart(2,"0");
    const bin = n.toString(2).padStart(8,"0");
    const oct = n.toString(8);
    const map = {
        "bin2dec": {label:"BIN → DEC", value:bin,      answer:String(n)},
        "dec2bin": {label:"DEC → BIN", value:String(n), answer:bin},
        "hex2dec": {label:"HEX → DEC", value:"0x"+hex, answer:String(n)},
        "dec2hex": {label:"DEC → HEX", value:String(n), answer:hex},
        "bin2hex": {label:"BIN → HEX", value:bin,      answer:hex},
        "hex2bin": {label:"HEX → BIN", value:"0x"+hex, answer:bin},
        "oct2dec": {label:"OCT → DEC", value:"0o"+oct, answer:String(n)},
        "dec2oct": {label:"DEC → OCT", value:String(n), answer:oct},
    };
    return {...map[t], type:t};
}

// ── Explanation generator ─────────────────────────────
function generateExplanation(q) {
    const n = parseInt(q.answer, 10);
    switch(q.type) {
        case 'bin2dec': {
            const bits = q.value.split('');
            const rows = bits.map((b,i) => ({pos:7-i, bit:b, val: b==='1' ? Math.pow(2,7-i) : 0}));
            const active = rows.filter(r=>r.bit==='1');
            let h = `<p class="exp-rule">Každý bit 1 přispívá svou mocninou 2:</p>`;
            h += `<div class="exp-bit-grid">`;
            rows.forEach(r => {
                h += `<div class="exp-cell ${r.bit==='1'?'active':''}">`;
                h += `<span class="ec-pos">${r.pos}</span>`;
                h += `<span class="ec-bit">${r.bit}</span>`;
                h += `<span class="ec-val">${r.val>0?r.val:''}</span>`;
                h += `</div>`;
            });
            h += `</div>`;
            h += `<p class="exp-sum">${active.map(r=>r.val).join(' + ')} = <strong>${n}</strong></p>`;
            return h;
        }
        case 'dec2bin': {
            let v = parseInt(q.value);
            const steps = [];
            while(v > 0) { steps.push({d:v, q:Math.floor(v/2), r:v%2}); v=Math.floor(v/2); }
            let h = `<p class="exp-rule">Opakované dělení 2 — zbytky čti zdola:</p>`;
            h += `<div class="exp-steps">`;
            steps.forEach(s => h += `<div class="exp-step">${s.d} ÷ 2 = ${s.q} <span class="rem">zbytek ${s.r}</span></div>`);
            h += `</div>`;
            h += `<p class="exp-sum">zpětně: <strong>${q.answer}</strong></p>`;
            return h;
        }
        case 'hex2dec': {
            const hex = q.value.replace('0x','');
            const parts = hex.split('').reverse().map((h,i)=>({h, dec:parseInt(h,16), val:parseInt(h,16)*Math.pow(16,i), pos:i})).reverse();
            let html = `<p class="exp-rule">Každý hex-digit × mocnina 16:</p>`;
            html += `<div class="exp-steps">`;
            parts.forEach(p => html += `<div class="exp-step">${p.h} × 16^${p.pos} = ${p.dec} × ${Math.pow(16,p.pos)} = <span class="rem">${p.val}</span></div>`);
            html += `</div>`;
            html += `<p class="exp-sum">${parts.map(p=>p.val).join(' + ')} = <strong>${n}</strong></p>`;
            return html;
        }
        case 'dec2hex': {
            let v = parseInt(q.value);
            const steps = [];
            while(v>0){const r=v%16;steps.push({d:v,q:Math.floor(v/16),r,h:r.toString(16).toUpperCase()});v=Math.floor(v/16);}
            let html = `<p class="exp-rule">Dělení 16 — zbytky čti zdola:</p>`;
            html += `<div class="exp-steps">`;
            steps.forEach(s => html += `<div class="exp-step">${s.d} ÷ 16 = ${s.q} <span class="rem">zbytek ${s.r} → ${s.h}</span></div>`);
            html += `</div>`;
            html += `<p class="exp-sum">zpětně: <strong>${q.answer}</strong></p>`;
            return html;
        }
        case 'bin2hex': {
            const nibs = [];
            for(let i=0;i<q.value.length;i+=4) {
                const s=q.value.substr(i,4);
                nibs.push({bin:s, hex:parseInt(s,2).toString(16).toUpperCase()});
            }
            let html = `<p class="exp-rule">Rozdel na nibbles (skupiny 4 bitů):</p>`;
            html += `<div class="exp-nibbles">`;
            nibs.forEach(n => html += `<div class="exp-nib"><span class="nb-top">${n.bin}</span><span class="nb-bot">${n.hex}</span></div>`);
            html += `</div>`;
            html += `<p class="exp-sum"><strong>${q.answer}</strong></p>`;
            return html;
        }
        case 'hex2bin': {
            const hex = q.value.replace('0x','');
            const chars = hex.split('').map(h=>({hex:h, bin:parseInt(h,16).toString(2).padStart(4,'0')}));
            let html = `<p class="exp-rule">Každý hex-digit → 4 bity:</p>`;
            html += `<div class="exp-nibbles">`;
            chars.forEach(c => html += `<div class="exp-nib"><span class="nb-top">${c.hex}</span><span class="nb-bot">${c.bin}</span></div>`);
            html += `</div>`;
            html += `<p class="exp-sum">Spoj: <strong>${q.answer}</strong></p>`;
            return html;
        }
        case 'oct2dec': {
            const oct = q.value.replace('0o','');
            const parts = oct.split('').reverse().map((d,i)=>({d, pos:i, val:parseInt(d)*Math.pow(8,i)})).reverse();
            let html = `<p class="exp-rule">Každá cifra × mocnina 8:</p>`;
            html += `<div class="exp-steps">`;
            parts.forEach(p => html += `<div class="exp-step">${p.d} × 8^${p.pos} = <span class="rem">${p.val}</span></div>`);
            html += `</div>`;
            html += `<p class="exp-sum">${parts.map(p=>p.val).join(' + ')} = <strong>${n}</strong></p>`;
            return html;
        }
        case 'dec2oct': {
            let v = parseInt(q.value);
            const steps = [];
            while(v>0){steps.push({d:v,q:Math.floor(v/8),r:v%8});v=Math.floor(v/8);}
            let html = `<p class="exp-rule">Dělení 8 — zbytky čti zdola:</p>`;
            html += `<div class="exp-steps">`;
            steps.forEach(s => html += `<div class="exp-step">${s.d} ÷ 8 = ${s.q} <span class="rem">zbytek ${s.r}</span></div>`);
            html += `</div>`;
            html += `<p class="exp-sum">zpětně: <strong>${q.answer}</strong></p>`;
            return html;
        }
    }
    return '';
}

// ── DOM refs ──────────────────────────────────────────
const elLabel    = document.getElementById("question-label");
const elValue    = document.getElementById("question-value");
const elInput    = document.getElementById("answer-input");
const elFeed     = document.getElementById("feedback");
const elCorrect  = document.getElementById("score-correct");
const elWrong    = document.getElementById("score-wrong");
const elTimer    = document.getElementById("timer");
const elStreak   = document.getElementById("streak-count");
const elBest     = document.getElementById("streak-best");
const explainWrap  = document.getElementById("explain-wrap");
const explainPanel = document.getElementById("explain-panel");
const btnExplain   = document.getElementById("btn-explain");

let explainOpen = false;
let waitingForNext = false; 

btnExplain.addEventListener('click', () => {
    explainOpen = !explainOpen;
    explainPanel.classList.toggle('open', explainOpen);
    btnExplain.textContent = explainOpen ? '[ × ] zavřít' : '[ ? ] jak na to';
});

function showExplain(q) {
    if (GAME_MODE === 1) return;   // no explain in Time Attack
    explainPanel.innerHTML = generateExplanation(q);
    explainWrap.classList.add('visible');
    explainOpen = false;
    explainPanel.classList.remove('open');
    btnExplain.textContent = '[ ? ] jak na to';
}

function hideExplain() {
    explainWrap.classList.remove('visible');
    explainOpen = false;
}

// ── Game logic ────────────────────────────────────────
function nextQuestion() {
    currentQuestion = generateQuestion();
    elLabel.textContent = currentQuestion.label;
    elValue.textContent = currentQuestion.value;
    elInput.value = "";
    elInput.disabled = false;
    document.getElementById("btn-submit").disabled = false;
    hideExplain();
    elInput.focus();
}

function showFeedback(ok, ans) {
    elFeed.textContent = ok ? "správně" : "špatně — bylo: " + ans;
    elFeed.className   = "feedback " + (ok ? "ok" : "bad");
    if (ok) setTimeout(() => { elFeed.textContent = ""; elFeed.className = "feedback"; }, 800);
}

function updateDisplay() {
    elCorrect.textContent = correct;
    elWrong.textContent   = wrong;
    if (elStreak) elStreak.textContent = streak;
    if (elBest)   elBest.textContent   = maxStreak;
}

// Accept a correct answer regardless of leading zeros — so "101" counts for
// DEC→BIN of 5 just like the zero-padded "00000101" the game stores internally.
function normalizeAnswer(s) {
    return String(s).trim().toUpperCase().replace(/\s+/g, '').replace(/^0+(?=.)/, '');
}

function submitAnswer() {
    // Čeká se na pokračování po špatné odpovědi (mód 2 & 3)
    if (waitingForNext) {
        waitingForNext = false;
        elFeed.textContent = ''; elFeed.className = 'feedback';
        elInput.disabled = false;
        const btnS = document.getElementById('btn-submit');
        btnS.disabled = false;
        btnS.innerHTML = '&#x21B5;';   // ↵
        nextQuestion();
        return;
    }

    if (!gameActive || !currentQuestion) return;
    const rawInput = elInput.value.trim();
    if (!rawInput) return;

    const ok = normalizeAnswer(rawInput) === normalizeAnswer(currentQuestion.answer);

    if (ok) {
        correct++; streak++;
        if (streak > maxStreak) maxStreak = streak;
        showFeedback(true, null);
        updateDisplay();
        nextQuestion();
    } else {
        wrong++; streak = 0;
        showFeedback(false, currentQuestion.answer);
        updateDisplay();

        if (GAME_MODE === 2 || GAME_MODE === 3) {
            showExplain(currentQuestion);
            // Automaticky otevři panel
            explainOpen = true;
            explainPanel.classList.add('open');
            btnExplain.textContent = '[ × ] zavřít';
            // Zamkni vstup, přeměň tlačítko na "Pokračovat"
            elInput.disabled = true;
            elInput.value = '';
            const btnS = document.getElementById('btn-submit');
            btnS.disabled = false;
            btnS.innerHTML = '&#9654;';    // ▶
            waitingForNext = true;
        } else {
            nextQuestion();  // Mód 1 Time Attack: žádné vysvětlení
        }
    }
}

function startTimer() {
    if (GAME_MODE !== 1) return;
    timerInterval = setInterval(() => {
        timeLeft--;
        if (elTimer) {
            elTimer.textContent = timeLeft;
            elTimer.className   = "timer";
            if      (timeLeft <= 5)  elTimer.classList.add("timer--crit");
            else if (timeLeft <= 15) elTimer.classList.add("timer--warn");
        }
        if (timeLeft <= 0) { clearInterval(timerInterval); endGame(); }
    }, 1000);
}

function endGame() {
    gameActive = false;
    clearInterval(timerInterval);
    document.getElementById("result-correct").textContent = correct;
    document.getElementById("result-wrong").textContent   = wrong;
    document.getElementById("result-streak").textContent  = maxStreak;
    if (GAME_MODE === 1) document.getElementById("result-streak-line").style.display = "none";
    document.getElementById("overlay-gameover").classList.remove("hidden");
    saveGame();
}

// One payload builder so the overlay-save and the leave-save always agree.
function buildResultPayload() {
    const score = GAME_MODE === 1 ? correct : maxStreak;
    return JSON.stringify({game_mode: GAME_MODE, time_seconds: TIME_LIMIT,
        q_answered: correct + wrong, q_correct: correct, q_wrong: wrong,
        q_skipped: 0, streak: maxStreak, score, sys_type: TRAIN_TYPE});
}

let gameSaved = false;
function saveGame() {
    // Training Lab (mode 2) never saves; skip an untouched game too.
    if (gameSaved || GAME_MODE === 2 || (correct + wrong) === 0) return;
    gameSaved = true;
    fetch("../backend/save_game.php", {
        method:"POST", headers:{"Content-Type":"application/json"},
        body: buildResultPayload()
    })
    .then(r => r.json())
    .then(showNewAchievements)
    .catch(() => {});
}

// Streak Challenge has no timer and no forced end, so if the player leaves
// mid-run (back to menu, closes the tab) still save their best streak.
window.addEventListener('pagehide', () => {
    if (GAME_MODE === 3 && !gameSaved && (correct + wrong) > 0) {
        gameSaved = true;
        navigator.sendBeacon(
            "../backend/save_game.php",
            new Blob([buildResultPayload()], {type: "application/json"})
        );
    }
});

// Show any badges unlocked by this game on the game-over overlay.
function showNewAchievements(resp) {
    const list = resp && resp.new_achievements;
    if (!list || !list.length) return;
    const box = document.getElementById("new-achv");
    const title = document.createElement("span");
    title.className = "na-title";
    title.textContent = "🏆 " + (list.length > 1 ? "Nové achievementy" : "Nový achievement");
    box.appendChild(title);
    list.forEach(name => {
        const item = document.createElement("span");
        item.className = "na-item";
        item.textContent = name;
        box.appendChild(item);
    });
    box.classList.remove("hidden");
}

document.getElementById("btn-submit").addEventListener("click", submitAnswer);
elInput.addEventListener("keydown", e => { if (e.key === "Enter") submitAnswer(); });
document.getElementById("btn-play-again").addEventListener("click", () => location.reload());

nextQuestion();
startTimer();
</script>
</body>
</html>