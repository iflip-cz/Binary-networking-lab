<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$mode = in_array((int)($_GET["mode"] ?? 1), [1,2,3]) ? (int)$_GET["mode"] : 1;
$time = in_array((int)($_GET["time"] ?? 60), [30,60,120]) ? (int)$_GET["time"] : 60;
$type = in_array($_GET["type"] ?? "all", ["all","bin","hex","oct"]) ? ($_GET["type"] ?? "all") : "all";

$modeLabels = [1 => "Time Attack", 2 => "Training Lab", 3 => "Streak Challenge"];
$modeLabel  = $modeLabels[$mode];
if ($mode === 1) $modeLabel .= " — {$time}s";
?>
<!doctype html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $modeLabel ?> — BNL</title>
    <link rel="stylesheet" href="s.css/lesson.css">
</head>
<body>

<header>
    <a href="mainMenu.php" id="btn-back">← Menu</a>
    <h2 id="mode-title"><?= $modeLabel ?></h2>

    <?php if ($mode === 1): ?>
        <div id="timer" class="timer"><?= $time ?></div>

    <?php elseif ($mode === 3): ?>
        <div class="streak-hud">
            <span class="streak-now">🔥 <span id="streak-count">0</span></span>
            <span class="streak-best">Best: <span id="streak-best">0</span></span>
        </div>

    <?php else: /* mode 2 — Training Lab */ ?>
        <button id="btn-stop" onclick="endGame()">■ Stop</button>
    <?php endif; ?>
</header>

<main id="game-area">
    <div id="question-box">
        <span id="question-label" class="q-label">—</span>
        <p id="question-value" class="q-value">…</p>
    </div>

    <input type="text" id="answer-input" placeholder="odpověď" autocomplete="off" spellcheck="false">
    <button id="btn-submit">Odeslat ↵</button>

    <p id="feedback" class="feedback"></p>

    <div id="score-bar">
        ✓ <span id="score-correct">0</span>
        &nbsp;·&nbsp;
        ✗ <span id="score-wrong">0</span>
    </div>
</main>

<!-- Game-over overlay -->
<div id="overlay-gameover" class="overlay hidden">
    <div class="overlay-box">
        <h2>Hra skončila</h2>
        <p>Správně: <strong id="result-correct">0</strong></p>
        <p>Chybně: <strong id="result-wrong">0</strong></p>
        <p id="result-streak-line">Nejdelší streak: <strong id="result-streak">0</strong></p>
        <button onclick="window.location.href='mainMenu.php'">← Menu</button>
        <button id="btn-play-again">Hrát znovu</button>
    </div>
</div>

<script>
// ── Config ───────────────────────────────────────────
const GAME_MODE  = <?= $mode ?>;
const TIME_LIMIT = <?= $time ?>;
const TRAIN_TYPE = "<?= $type ?>";
const USER_ID    = <?= (int)$_SESSION["user_id"] ?>;

// ── State ────────────────────────────────────────────
let correct = 0, wrong = 0, streak = 0, maxStreak = 0;
let timeLeft = TIME_LIMIT;
let timerInterval = null;
let currentQuestion = null;
let gameActive = true;

// ── Question generator ───────────────────────────────
const POOLS = {
    bin: ["bin2dec", "dec2bin"],
    hex: ["hex2dec", "dec2hex", "bin2hex", "hex2bin"],
    oct: ["oct2dec", "dec2oct"]
};

function pickType() {
    const pool = TRAIN_TYPE === "all"
        ? [...POOLS.bin, ...POOLS.hex, ...POOLS.oct]
        : POOLS[TRAIN_TYPE];
    return pool[Math.floor(Math.random() * pool.length)];
}

function generateQuestion() {
    const t = pickType();
    const n = Math.floor(Math.random() * 256);
    const hex = n.toString(16).toUpperCase().padStart(2, "0");
    const bin = n.toString(2).padStart(8, "0");
    const oct = n.toString(8);

    const map = {
        "bin2dec": { label: "BIN → DEC", value: bin,       answer: String(n) },
        "dec2bin": { label: "DEC → BIN", value: String(n), answer: bin       },
        "hex2dec": { label: "HEX → DEC", value: "0x"+hex,  answer: String(n) },
        "dec2hex": { label: "DEC → HEX", value: String(n), answer: hex       },
        "bin2hex": { label: "BIN → HEX", value: bin,       answer: hex       },
        "hex2bin": { label: "HEX → BIN", value: "0x"+hex,  answer: bin       },
        "oct2dec": { label: "OCT → DEC", value: "0o"+oct,  answer: String(n) },
        "dec2oct": { label: "DEC → OCT", value: String(n), answer: oct       },
    };
    return { ...map[t], type: t };
}

// ── DOM refs ─────────────────────────────────────────
const elLabel   = document.getElementById("question-label");
const elValue   = document.getElementById("question-value");
const elInput   = document.getElementById("answer-input");
const elFeed    = document.getElementById("feedback");
const elCorrect = document.getElementById("score-correct");
const elWrong   = document.getElementById("score-wrong");
const elTimer   = document.getElementById("timer");
const elStreak  = document.getElementById("streak-count");
const elBest    = document.getElementById("streak-best");

// ── Display ──────────────────────────────────────────
function nextQuestion() {
    currentQuestion = generateQuestion();
    elLabel.textContent = currentQuestion.label;
    elValue.textContent = currentQuestion.value;
    elInput.value = "";
    elInput.disabled = false;
    document.getElementById("btn-submit").disabled = false;
    elInput.focus();
}

function showFeedback(ok, correctAns) {
    if (ok) {
        elFeed.textContent = "✓ Správně!";
        elFeed.className = "feedback ok";
    } else {
        elFeed.textContent = "✗ Špatně — správně: " + correctAns;
        elFeed.className = "feedback wrong";
    }
    if (ok) setTimeout(() => { elFeed.textContent = ""; }, 900);
}

function updateDisplay() {
    elCorrect.textContent = correct;
    elWrong.textContent   = wrong;
    if (elStreak) elStreak.textContent = streak;
    if (elBest)   elBest.textContent   = maxStreak;
}

// ── Submit ───────────────────────────────────────────
function submitAnswer() {
    if (!gameActive || !currentQuestion) return;
    const raw = elInput.value.trim().toUpperCase().replace(/\s+/g, "");
    if (raw === "") return;

    const ok = raw === currentQuestion.answer.toUpperCase();

    if (ok) {
        correct++;
        streak++;
        if (streak > maxStreak) maxStreak = streak;
        showFeedback(true, null);
        updateDisplay();
        nextQuestion();
    } else {
        wrong++;
        showFeedback(false, currentQuestion.answer);
        streak = 0;
        updateDisplay();

        if (GAME_MODE === 1 || GAME_MODE === 3) {
            // Time Attack & Streak: immediately continue
            nextQuestion();
        } else {
            // Training Lab: freeze for 1.8s so user can read the answer
            elInput.disabled = true;
            document.getElementById("btn-submit").disabled = true;
            setTimeout(() => {
                elFeed.textContent = "";
                nextQuestion();
            }, 1800);
        }
    }
}

// ── Timer (mode 1 only) ──────────────────────────────
function startTimer() {
    if (GAME_MODE !== 1) return;
    timerInterval = setInterval(() => {
        timeLeft--;
        if (elTimer) {
            elTimer.textContent = timeLeft;
            elTimer.className = "timer";
            if      (timeLeft <= 5)  elTimer.classList.add("timer--crit");
            else if (timeLeft <= 15) elTimer.classList.add("timer--warn");
        }
        if (timeLeft <= 0) { clearInterval(timerInterval); endGame(); }
    }, 1000);
}

// ── End game ─────────────────────────────────────────
function endGame() {
    gameActive = false;
    clearInterval(timerInterval);

    document.getElementById("result-correct").textContent = correct;
    document.getElementById("result-wrong").textContent   = wrong;
    document.getElementById("result-streak").textContent  = maxStreak;

    // Hide streak line for Time Attack
    if (GAME_MODE === 1) {
        document.getElementById("result-streak-line").style.display = "none";
    }

    document.getElementById("overlay-gameover").classList.remove("hidden");

    // mode 2 (free practice) — no score to save
    if (GAME_MODE === 2) return;

    // Determine score to record
    const score = GAME_MODE === 1 ? correct : maxStreak;
    // Map mode 3 → highscore_2gm slot (same as old training lab)
    const dbMode = GAME_MODE === 1 ? 1 : 2;

    fetch("../backend/save_game.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
            game_mode:    dbMode,
            time_seconds: TIME_LIMIT,
            q_answered:   correct + wrong,
            q_correct:    correct,
            q_wrong:      wrong,
            q_skipped:    0,
            streak:       maxStreak,
            score:        score
        })
    });
}

// ── Boot ─────────────────────────────────────────────
document.getElementById("btn-submit").addEventListener("click", submitAnswer);
elInput.addEventListener("keydown", e => { if (e.key === "Enter") submitAnswer(); });
document.getElementById("btn-play-again").addEventListener("click", () => window.location.reload());

nextQuestion();
startTimer();
</script>

</body>
</html>