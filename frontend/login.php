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
    <title>Přihlášení</title>
    <link rel="stylesheet" href=".css/register.css">
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

    <div>
        Nemáš účet? <a href="register.php">Zaregistrovat se</a>
    </div>

</form>

<?php if (isset($_GET["error"])): ?>
    <p class="error">Nesprávné uživatelské jméno nebo heslo.</p>
<?php endif; ?>

</body>
</html>
