<?php
session_start();
if (isset($_SESSION["user_id"])) {
    header("Location: mainMenu.php");
    exit;
}

$errorMessages = [
    "fields"          => "Vyplňte prosím všechna povinná pole.",
    "password"        => "Heslo nesplňuje požadavky (min. 8 znaků, velké + malé písmeno, číslice).",
    "exists_username" => "Toto uživatelské jméno je již obsazeno.",
    "exists_email"    => "Tento e-mail je již zaregistrovaný.",
];
$errorCode = $_GET["error"] ?? "";
?>
<!doctype html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrace — BNL</title>
    <script>document.documentElement.setAttribute('data-theme',localStorage.getItem('bnl-theme')||'dark');</script>
    <link rel="stylesheet" href="s.css/register.css">
</head>
<body>

<p class="site-tag">Binary Networking Lab</p>
<h2>Registrace</h2>

<form method="post" action="../backend/register_process.php">

    <label for="username">Uživatelské jméno</label>
    <input type="text" id="username" name="username" placeholder=" " maxlength="20" required autofocus>

    <label for="name">Jméno</label>
    <input type="text" id="name" name="name" placeholder=" " maxlength="20" required>

    <label for="surname">Příjmení</label>
    <input type="text" id="surname" name="surname" placeholder=" " maxlength="20" required>

    <label for="email">E-mail</label>
    <input type="email" id="email" name="email" placeholder=" " maxlength="100" required>

    <label for="password">Heslo</label>
    <input type="password" id="password" name="password" placeholder=" " required>

    <!-- Live strength widget (shown as soon as user starts typing) -->
    <div class="pw-strength-wrap" id="pw-strength-wrap">
        <div class="pw-bar-track"><div class="pw-bar-fill" id="pw-bar-fill"></div></div>
        <ul class="pw-reqs">
            <li class="req" id="req-len">min. 8 znaků</li>
            <li class="req" id="req-upper">velké písmeno</li>
            <li class="req" id="req-lower">malé písmeno</li>
            <li class="req" id="req-num">číslice</li>
        </ul>
    </div>

    <label for="confirm_password">Potvrzení hesla</label>

    <input type="password" id="confirm_password" name="confirm_password" placeholder=" " required>
    <p class="pw-match" id="pw-match"></p>
<div class="form-group">
    <label>
        <input type="checkbox" name="is_teacher" value="1">
        Jsem učitel/ka
    </label>
</div>
    <input type="submit" value="Zaregistrovat se">

    <div>Máš už účet? <a href="login.php">Přihlásit se</a></div>

</form>

<?php if ($errorCode !== "" && isset($errorMessages[$errorCode])): ?>
    <p class="error"><?= htmlspecialchars($errorMessages[$errorCode]) ?></p>
<?php endif; ?>

<footer class="page-footer">
    <span>© 2026 Binary Networking Lab</span>
    <span class="sep">·</span>
    <a href="index.php">Úvod</a>
    <span class="sep">·</span>
    <a href="login.php">Přihlásit se</a>
</footer>

<script>
const pw        = document.getElementById('password');
const confirmPw = document.getElementById('confirm_password');
const wrap      = document.getElementById('pw-strength-wrap');
const bar       = document.getElementById('pw-bar-fill');
const matchEl   = document.getElementById('pw-match');
const reqs = {
    len:   document.getElementById('req-len'),
    upper: document.getElementById('req-upper'),
    lower: document.getElementById('req-lower'),
    num:   document.getElementById('req-num'),
};

pw.addEventListener('input', function () {
    const v = pw.value;
    wrap.classList.add('visible');

    const checks = {
        len:   v.length >= 8,
        upper: /[A-Z]/.test(v),
        lower: /[a-z]/.test(v),
        num:   /[0-9]/.test(v),
    };

    const score = Object.values(checks).filter(Boolean).length;

    for (const [key, el] of Object.entries(reqs)) {
        el.classList.toggle('ok', checks[key]);
    }

    bar.className = 'pw-bar-fill';
    if (score > 0) bar.classList.add('str-' + score);

    if (confirmPw.value) checkMatch();
});

confirmPw.addEventListener('input', checkMatch);

function checkMatch() {
    if (!confirmPw.value) { matchEl.textContent = ''; return; }
    const ok = pw.value === confirmPw.value;
    matchEl.textContent = ok ? '✓ Hesla se shodují' : '✗ Hesla se neshodují';
    matchEl.className = 'pw-match ' + (ok ? 'ok' : 'err');
}
</script>
</body>
</html>