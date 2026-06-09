<?php
session_start();
if (isset($_SESSION["user_id"])) {
    header("Location: mainMenu.php");
    exit;
}

// Human-readable messages for each error code
$errorMessages = [
    "fields"          => "Vyplňte prosím všechna povinná pole.",
    "password"        => "Hesla se neshodují nebo nesplňují požadavky (min. 8 znaků, velké i malé písmeno, číslice).",
    "exists_username" => "Toto uživatelské jméno je již obsazeno.",
    "exists_email"    => "Tento e-mail je již registrovaný.",
];
$errorCode = $_GET["error"] ?? "";
?>
<!doctype html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrace</title>
    <link rel="stylesheet" href=".css/register.css">
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
    <input type="email" id="email" name="email" placeholder=" " maxlength="50" required>

    <label for="password">Heslo</label>
    <input type="password" id="password" name="password" placeholder=" " required>

    <label for="confirm_password">Potvrzení hesla</label>
    <input type="password" id="confirm_password" name="confirm_password" placeholder=" " required>

    <input type="submit" value="Zaregistrovat se">

    <div>
        Máš už účet? <a href="login.php">Přihlásit se</a>
    </div>

</form>

<?php if ($errorCode !== "" && isset($errorMessages[$errorCode])): ?>
    <p class="error"><?= htmlspecialchars($errorMessages[$errorCode]) ?></p>
<?php endif; ?>

</body>
</html>
