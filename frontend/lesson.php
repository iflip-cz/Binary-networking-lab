<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$mode = isset($_GET["mode"]) ? (int)$_GET["mode"] : 1;   // 1 = Time Attack, 2 = Training Lab
$time = isset($_GET["time"]) ? (int)$_GET["time"] : 60;  // seconds (30 / 60 / 120)

// Validate allowed values
if (!in_array($mode, [1, 2]))        $mode = 1;
if (!in_array($time, [30, 60, 120])) $time = 60;

$modeLabel = $mode === 1
    ? "⚡ Time Attack – {$time}s"
    : "🧪 Training Lab";
?>
<!doctype html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $modeLabel ?> – Binary Networking Lab</title>
    <link rel="stylesheet" href=".css/lesson.css">
</head>
<body>

<header>
    <a href="mainMenu.php" id="btn-back">← Menu</a>
    <h2 id="mode-title"><?= $modeLabel ?></h2>

    <!-- Time Attack: countdown  |  Training Lab: streak counter -->
    <?php if ($mode === 1): ?>
        <div id="timer" class="timer"><?= $time ?></div>
    <?php else: ?>
        <div id="streak-display" class="streak">Streak: <span id="streak-count">0</span></div>
    <?php endif; ?>
</header>

<main id="game-area">
    <div id="question-box">
        <p id="question-text">Načítám otázku…</p>
    </div>

    <input type="text" id="answer-input" placeholder="Tvoje odpověď" autocomplete="off">
    <button id="btn-submit">Odeslat ↵</button>

    <p id="feedback" class="feedback"></p>

    <div id="score-bar">
        Správně: <span id="score-correct">0</span> &nbsp;|&nbsp;
        Chybně: <span id="score-wrong">0</span>
    </div>
</main>

<!-- Game-over overlay -->
<div id="overlay-gameover" class="overlay hidden">
    <div class="overlay-box">
        <h2>Hra skončila!</h2>
        <p>Správně: <strong id="result-correct">0</strong></p>
        <p>Chybně:  <strong id="result-wrong">0</strong></p>
        <p id="result-streak-line">Streak: <strong id="result-streak">0</strong></p>
        <button onclick="window.location.href='mainMenu.php'">Zpět na menu</button>
        <button id="btn-play-again">Hrát znovu</button>
    </div>
</div>

<script>
// ─────────────────────────────────────────────────────────────
//  Configuration (passed from PHP)
// ─────────────────────────────────────────────────────────────
const GAME_MODE   = <?= $mode ?>;
const TIME_LIMIT  = <?= $time ?>;   // seconds; ignored in Training Lab
const USER_ID     = <?= (int)$_SESSION["user_id"] ?>;

// ─────────────────────────────────────────────────────────────
//  State
// ─────────────────────────────────────────────────────────────
let correct = 0, wrong = 0, streak = 0, maxStreak = 0;
let timeLeft = TIME_LIMIT;
let timerInterval = null;
let currentQuestion = null;

// ─────────────────────────────────────────────────────────────
//  Question generator
//  Produces: { text, answer, hint }
// ─────────────────────────────────────────────────────────────
function generateQuestion() {
    const types = ["bin2dec", "dec2bin", "hex2bin", "bin2hex"];
    const type  = types[Math.floor(Math.random() * types.length)];

    let n, text, answer;

    if (type === "bin2dec") {
        n      = Math.floor(Math.random() * 256);          // 0–255 (one byte)
        text   = `Převeď binárně → decimálně:  ${n.toString(2).padStart(8,"0")}`;
        answer = String(n);
    } else if (type === "dec2bin") {
        n      = Math.floor(Math.random() * 256);
        text   = `Převeď decimálně → binárně:  ${n}`;
        answer = n.toString(2).padStart(8,"0");
    } else if (type === "hex2bin") {
        n      = Math.floor(Math.random() * 256);
        const hex = n.toString(16).toUpperCase().padStart(2,"0");
        text   = `Převeď hexadecimálně → binárně:  0x${hex}`;
        answer = n.toString(2).padStart(8,"0");
    } else {   // bin2hex
        n      = Math.floor(Math.random() * 256);
        text   = `Převeď binárně → hexadecimálně:  ${n.toString(2).padStart(8,"0")}`;
        answer = n.toString(16).toUpperCase().padStart(2,"0");
    }

    return { text, answer };
}

// ─────────────────────────────────────────────────────────────
//  DOM helpers
// ─────────────────────────────────────────────────────────────
const elQuestion = document.getElementById("question-text");
const elInput    = document.getElementById("answer-input");
const elFeedback = document.getElementById("feedback");
const elCorrect  = document.getElementById("score-correct");
const elWrong    = document.getElementById("score-wrong");
const elStreak   = document.getElementById("streak-count");
const elTimer    = document.getElementById("timer");

function showFeedback(ok) {
    elFeedback.textContent = ok ? "✅ Správně!" : `❌ Špatně! Správná odpověď: ${currentQuestion.answer}`;
    elFeedback.className   = "feedback " + (ok ? "ok" : "wrong");
    setTimeout(() => { elFeedback.textContent = ""; }, 1200);
}

function nextQuestion() {
    currentQuestion = generateQuestion();
    elQuestion.textContent = currentQuestion.text;
    elInput.value = "";
    elInput.focus();
}

function updateScoreDisplay() {
    elCorrect.textContent = correct;
    elWrong.textContent   = wrong;
    if (elStreak) elStreak.textContent = streak;
}

// ─────────────────────────────────────────────────────────────
//  Submit answer
// ─────────────────────────────────────────────────────────────
function submitAnswer() {
    const raw = elInput.value.trim().toUpperCase().replace(/\s+/g,"");
    if (raw === "") return;

    const ok = raw === currentQuestion.answer.toUpperCase().replace(/\s+/g,"");

    if (ok) {
        correct++;
        streak++;
        if (streak > maxStreak) maxStreak = streak;
        showFeedback(true);
    } else {
        wrong++;
        streak = 0;
        showFeedback(false);
        if (GAME_MODE === 2) { endGame(); return; }  // Training Lab: one strike ends it
    }

    updateScoreDisplay();
    nextQuestion();
}

// ─────────────────────────────────────────────────────────────
//  Timer (Time Attack only)
// ─────────────────────────────────────────────────────────────
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

// ─────────────────────────────────────────────────────────────
//  Game over & save result
// ─────────────────────────────────────────────────────────────
function endGame() {
    clearInterval(timerInterval);

    // Show overlay
    document.getElementById("result-correct").textContent = correct;
    document.getElementById("result-wrong").textContent   = wrong;
    document.getElementById("result-streak").textContent  = maxStreak;
    if (GAME_MODE === 1) document.getElementById("result-streak-line").style.display = "none";
    document.getElementById("overlay-gameover").classList.remove("hidden");

    // Send result to PHP via fetch
    const score = GAME_MODE === 1 ? correct : maxStreak;
    fetch("../backend/save_game.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
            game_mode:    GAME_MODE,
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

// ─────────────────────────────────────────────────────────────
//  Boot
// ─────────────────────────────────────────────────────────────
document.getElementById("btn-submit").addEventListener("click", submitAnswer);
elInput.addEventListener("keydown", e => { if (e.key === "Enter") submitAnswer(); });
document.getElementById("btn-play-again").addEventListener("click", () => {
    window.location.reload();
});

nextQuestion();
startTimer();
</script>

</body>
</html>
