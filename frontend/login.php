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
    <link rel="stylesheet" href="s.css/register.css">
</head>
<body>

<p class="site-tag">Binary Networking Lab</p>
<h2>Přihlášení</h2>

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