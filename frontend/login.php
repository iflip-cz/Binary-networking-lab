<?php
session_start();
if (isset($_SESSION["user_id"])) {
    header("Location: mainMenu.php");
    exit;
}
$oldUsername = $_SESSION["login_old_username"] ?? "";
unset($_SESSION["login_old_username"]);
?>
<!doctype html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Přihlášení — BNL</title>
    <script>document.documentElement.setAttribute('data-theme',localStorage.getItem('bnl-theme')||'dark');</script>
    <meta name="theme-color" content="#0d0f14">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 64 64%22><rect width=%2264%22 height=%2264%22 rx=%2214%22 fill=%22%230d0f14%22/><text x=%2232%22 y=%2244%22 font-family=%22monospace%22 font-size=%2230%22 font-weight=%22700%22 text-anchor=%22middle%22 fill=%22%23f97316%22>01</text></svg>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="s.css/register.css">
</head>
<body>

<p class="site-tag">Binary Networking Lab</p>
<h1>Přihlášení</h1>

<form method="post" action="../backend/login_process.php">
    <label for="username">Uživatelské jméno</label>
    <input type="text" id="username" name="username" placeholder=" " required autofocus
           value="<?= htmlspecialchars($oldUsername) ?>">

    <label for="password">Heslo</label>
    <input type="password" id="password" name="password" placeholder=" " required>

    <input type="submit" value="Přihlásit se">

    <div>Nemáš účet? <a href="register.php">Zaregistrovat se</a></div>
</form>

<?php if (isset($_GET["error"])): ?>
    <p class="error">Nesprávné uživatelské jméno nebo heslo.</p>
<?php endif; ?>

<footer class="page-footer">
    <span>© 2026 Binary Networking Lab</span>
    <span class="sep">·</span>
    <a href="../index.php">Úvod</a>
    <span class="sep">·</span>
    <a href="register.php">Registrace</a>
</footer>

<script>
    // Optimistic submit: switch the button to a working state the moment you submit.
    const loginForm = document.querySelector('form');
    if (loginForm) loginForm.addEventListener('submit', () => {
        const b = loginForm.querySelector('input[type="submit"]');
        if (b) { b.value = 'Přihlašuji…'; b.classList.add('loading'); setTimeout(() => { b.disabled = true; }, 0); }
    });
    // Login failed: username is prefilled, so put the cursor on the password.
    <?php if (isset($_GET["error"])): ?>document.getElementById('password').focus();<?php endif; ?>
</script>
</body>
</html>