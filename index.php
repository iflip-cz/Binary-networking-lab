<?php
session_start();
if (isset($_SESSION["user_id"])) {
    header("Location: frontend/mainMenu.php");
    exit;
}
?>
<!doctype html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script>document.documentElement.setAttribute('data-theme',localStorage.getItem('bnl-theme')||'dark');</script>
    <meta name="theme-color" content="#0d0f14">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 64 64%22><rect width=%2264%22 height=%2264%22 rx=%2214%22 fill=%22%230d0f14%22/><text x=%2232%22 y=%2244%22 font-family=%22monospace%22 font-size=%2230%22 font-weight=%22700%22 text-anchor=%22middle%22 fill=%22%23f97316%22>01</text></svg>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="frontend/s.css/index.css">
    <title>Binary Networking Lab</title>
</head>
<body>

    <h1>Binary <span class="hi">Networking</span> Lab</h1>
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
        <input type="button" value="Přihlásit se"  onclick="window.location.href='frontend/login.php'">
        <input type="button" value="Zaregistrovat se" onclick="window.location.href='frontend/register.php'">
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
    <footer class="page-footer">
    <span>© 2026 Binary Networking Lab</span>
    <span class="sep">·</span>
    <a href="frontend/login.php">Přihlásit se</a>
    <span class="sep">·</span>
    <a href="frontend/register.php">Registrace</a>
</footer>
</body>
</html>
