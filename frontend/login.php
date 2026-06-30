<?php
session_start();
if (isset($_SESSION["user_id"])) {
    header("Location: mainMenu.php");
    exit;
}
?>
<!doctype html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Přihlášení — BNL</title>
    <script>document.documentElement.setAttribute('data-theme',localStorage.getItem('bnl-theme')||'light');</script>
    <link rel="stylesheet" href="s.css/register.css">
</head>
<body>

<p class="site-tag">Binary Networking Lab</p>
<h2>Přihlášení</h2>

<form method="post" action="../backend/login_process.php">
    <label for="username">Uživatelské jméno</label>
    <input type="text" id="username" name="username" placeholder=" " required autofocus>

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
    <a href="index.php">Úvod</a>
    <span class="sep">·</span>
    <a href="register.php">Registrace</a>
</footer>
<script>
    // 1. Získání uloženého tématu a nastavení správného textu na tlačítku
    const currentSavedTheme = localStorage.getItem('bnl-theme') || 'light';
    const themeToggleBtn = document.getElementById('theme-toggle');

    if (themeToggleBtn) {
        // Hned po načtení nastavíme správný text tlačítka
        themeToggleBtn.textContent = currentSavedTheme === 'dark' ? '[ light ]' : '[ dark ]';

        // 2. Přidání akce pro kliknutí (přepnutí a uložení)
        themeToggleBtn.addEventListener('click', function() {
            // Zjistíme aktuální stav tagu <html>
            const isCurrentlyDark = document.documentElement.getAttribute('data-theme') === 'dark';
            const newTheme = isCurrentlyDark ? 'light' : 'dark';
            
            // Nastavíme nový režim na <html>
            document.documentElement.setAttribute('data-theme', newTheme);
            
            // TADY SE REŽIM UKLÁDÁ DO PAMĚTI PROHLÍŽEČE:
            localStorage.setItem('bnl-theme', newTheme);
            
            // Změníme text tlačítka
            this.textContent = newTheme === 'dark' ? '[ light ]' : '[ dark ]';
        });
    }
</script>
</body>
</html>