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
    <link rel="stylesheet" href="s.css/index.css">
    <title>Binary Networking Lab</title>
</head>
<body>

    <h2>Binary <span class="hi">Networking</span> Lab</h2>
    <p class="tagline">// learn · convert · compete</p>

    <div class="picture-space"></div>

    <article class="content-container">
        <button class="arrow prev" onclick="zmenSlide(-1)">&#10094;</button>

        <div class="slides-wrapper">
            <div class="slide active">
                <h3>Nauč se binární soustavu</h3>
                <p>Procvič převody mezi binární, hexadecimální a oktalovou soustavou – zábavně a efektivně.</p>
            </div>
            <div class="slide">
                <h3>⚡ Time Attack</h3>
                <p>30, 60 nebo 120 sekund čistého adrenalinu. Odpovídej co nejrychleji a šplhej na žebříček!</p>
            </div>
            <div class="slide">
                <h3>🧪 Training Lab</h3>
                <p>Bez časového tlaku. Soustřeď se na přesnost a buduj co nejdelší streak správných odpovědí.</p>
            </div>
            <div class="slide">
                <h3>🏆 Achievementy &amp; Žebříček</h3>
                <p>Odemykej odznaky, porovnej se s ostatními a vystoupej na vrchol globálního žebříčku.</p>
            </div>
        </div>

        <button class="arrow next" onclick="zmenSlide(1)">&#10095;</button>
    </article>

    <footer>
        <input type="button" value="Přihlásit se"  onclick="window.location.href='login.php'">
        <input type="button" value="Zaregistrovat se" onclick="window.location.href='register.php'">
    </footer>

    <script>
        let aktualniSlide = 0;
        const slidy = document.querySelectorAll('.slide');

        function ukazSlide(index) {
            slidy[aktualniSlide].classList.remove('active');
            aktualniSlide = (index + slidy.length) % slidy.length;
            slidy[aktualniSlide].classList.add('active');
        }
        function zmenSlide(smer) { ukazSlide(aktualniSlide + smer); }

        setInterval(() => zmenSlide(1), 5000);
    </script>
</body>
</html>
