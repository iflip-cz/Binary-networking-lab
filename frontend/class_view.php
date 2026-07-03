<?php
session_start();
if (!isset($_SESSION["user_id"])) { header("Location: login.php"); exit; }
require "../backend/funcDB.php";
$pdo = connectDB();

$id_class  = (int)($_GET["id"] ?? 0);
$isTeacher = (int)$_SESSION["teacher"] === 1;
$userId    = (int)$_SESSION["user_id"];

if (!$id_class) { header("Location: mainMenu.php"); exit; }

$class = getClassById($pdo, $id_class);
if (!$class) { header("Location: mainMenu.php"); exit; }

// Check permission: teacher owns it OR student is member
$isOwner = (int)$class["ID_teacher"] === $userId;
if (!$isOwner) {
    $memberCheck = $pdo->prepare("SELECT 1 FROM class_members WHERE ID_class = ? AND ID_user = ?");
    $memberCheck->execute([$id_class, $userId]);
    if (!$memberCheck->rowCount()) { header("Location: mainMenu.php"); exit; }
}

// Actions
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_POST["remove_user"]) && $isOwner) {
        removeFromClass($pdo, $id_class, (int)$_POST["remove_user"], $userId);
        header("Location: class_view.php?id=$id_class");
        exit;
    }
    if (isset($_POST["leave"]) && !$isOwner) {
        leaveClass($pdo, $id_class, $userId);
        header("Location: mainMenu.php");
        exit;
    }
}

$lbTA   = getClassLeaderboard($pdo, $id_class, 'time_attack', 30);
$lbST   = getClassLeaderboard($pdo, $id_class, 'streak',      30);
$lbTOT  = getClassLeaderboard($pdo, $id_class, 'total_correct', 30);
$members = $isOwner ? getClassMembersDetailed($pdo, $id_class) : [];
?>
<!doctype html>
<html lang="cs" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($class["name"]) ?> — BNL</title>
    <script>document.documentElement.setAttribute('data-theme',localStorage.getItem('bnl-theme')||'light');</script>
    <link rel="stylesheet" href="s.css/class.css">
</head>
<body>

<header>
    <a href="mainMenu.php" class="back-link">← Menu</a>
    <span class="header-title">
        <span class="prompt">//</span>
        <?= htmlspecialchars($class["name"]) ?>
    </span>
    <div class="header-right">
        <button class="theme-btn" id="theme-toggle">[ light ]</button>
        <?php if ($isOwner): ?>
            <span class="class-code-badge"><?= htmlspecialchars($class["code"]) ?></span>
        <?php else: ?>
            <form method="post" style="display:inline;">
                <button type="submit" name="leave" value="1" class="leave-btn"
                        onclick="return confirm('Opustit třídu?')">Odejít</button>
            </form>
        <?php endif; ?>
    </div>
</header>

<main>

    <!-- ── Leaderboards ──────────────────────────────── -->
    <section>
        <div class="lb-header">
            <h2>Žebříček třídy</h2>
            <div class="lb-tabs">
                <button class="lb-tab active" data-board="ta">Time Attack</button>
                <button class="lb-tab" data-board="st">Streak</button>
                <button class="lb-tab" data-board="tot">Celkem správně</button>
            </div>
        </div>

        <?php
        $boards = ['ta' => $lbTA, 'st' => $lbST, 'tot' => $lbTOT];
        $labels = ['ta' => 'Skóre', 'st' => 'Streak', 'tot' => 'Správně'];
        foreach ($boards as $key => $rows):
        ?>
        <div id="lb-<?= $key ?>" class="lb-table-wrap" <?= $key !== 'ta' ? 'style="display:none;"' : '' ?>>
            <?php if ($rows): ?>
            <table>
                <thead><tr><th>#</th><th>Hráč</th><th><?= $labels[$key] ?></th></tr></thead>
                <tbody>
                    <?php foreach ($rows as $i => $r): ?>
                    <tr <?= $r["username"] === $_SESSION["username"] ? 'class="highlight"' : '' ?>>
                        <td><?= $i+1 ?></td>
                        <td><?= ($r["anonym"] && !$isOwner) ? "anonym" : htmlspecialchars($r["username"]) ?></td>
                        <td><?= (int)$r["score"] ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?><p class="empty-state">Zatím žádné záznamy.</p><?php endif; ?>
        </div>
        <?php endforeach; ?>
    </section>

    <!-- ── Teacher: full student stats ──────────────── -->
    <?php if ($isOwner && $members): ?>
    <section>
        <h2>Všichni studenti</h2>
        <div class="members-table-wrap">
            <table class="members-table">
                <thead>
                    <tr>
                        <th>Uživatel</th>
                        <th>Jméno</th>
                        <th>Time Attack</th>
                        <th>Streak</th>
                        <th>Zodpovězeno</th>
                        <th>Správně</th>
                        <th>Přesnost</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($members as $m): ?>
                    <tr>
                        <td class="mono"><?= htmlspecialchars($m["username"]) ?></td>
                        <td><?= htmlspecialchars($m["name"]." ".$m["surname"]) ?></td>
                        <td class="mono num"><?= (int)$m["highscore_1gm"] ?></td>
                        <td class="mono num"><?= (int)$m["highscore_2gm"] ?></td>
                        <td class="mono num"><?= (int)$m["Q_answerd"] ?></td>
                        <td class="mono num ok"><?= (int)$m["Q_correct"] ?></td>
                        <td class="mono num"><?= $m["accuracy"] ?>%</td>
                        <td>
                            <form method="post" onsubmit="return confirm('Odebrat ze třídy?')">
                                <input type="hidden" name="remove_user" value="<?= $m["ID_user"] ?>">
                                <button type="submit" class="rm-btn">odebrat</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
    <?php endif; ?>

</main>

<footer class="page-footer">
    <span>Binary Networking Lab</span>
    <span class="sep">·</span>
    <a href="mainMenu.php">menu</a>
</footer>

<script>
(function() {
    const saved = localStorage.getItem('bnl-theme') || 'light';
    document.getElementById('theme-toggle').textContent = saved === 'dark' ? '[ light ]' : '[ dark ]';
})();
document.getElementById('theme-toggle').addEventListener('click', function() {
    const curr = document.documentElement.getAttribute('data-theme');
    const next = curr === 'dark' ? 'light' : 'dark';
    document.documentElement.setAttribute('data-theme', next);
    localStorage.setItem('bnl-theme', next);
    this.textContent = next === 'dark' ? '[ light ]' : '[ dark ]';
});

document.querySelectorAll('.lb-tab').forEach(tab => {
    tab.addEventListener('click', () => {
        document.querySelectorAll('.lb-tab').forEach(t => t.classList.remove('active'));
        tab.classList.add('active');
        ['ta','st','tot'].forEach(k => {
            document.getElementById('lb-'+k).style.display = k===tab.dataset.board ? '' : 'none';
        });
    });
});
</script>
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