# Projekt – Poznámky k nastavení

## ⚠️ KRITICKÁ OPRAVA DATABÁZE – udělej hned!

Sloupec `password` v tabulce `user` je definovaný jako `varchar(30)`,
ale PHP funkce `password_hash()` generuje hashované heslo délky **~60 znaků**.
Pokud to neopravíš, registrace skončí tichým selháním (heslo se zkrátí, přihlášení pak nikdy nebude fungovat).

**Spusť v phpMyAdmin → SQL:**
```sql
ALTER TABLE `user`
    MODIFY COLUMN `password` varchar(255) NOT NULL;

-- A e-mail je také moc krátký (jen 20 znaků):
ALTER TABLE `user`
    MODIFY COLUMN `email` varchar(100) NOT NULL;
```

---

## 1. Název databáze

V souboru `backend/funcDB.php` změň řádek:
```php
$dsn = "mysql:host=localhost;dbname=zwa_projekt;charset=utf8mb4";
```
Na skutečný název tvé databáze, např. `projektfilip1` nebo cokoliv, cos zvolil v phpMyAdmin.

---

## 2. Struktura souborů

```
projekt/
│
├── frontend/               ← HTML stránky (co vidí uživatel)
│   ├── index.php           ← Úvodní/landing stránka
│   ├── login.php           ← Přihlašovací formulář
│   ├── register.php        ← Registrační formulář
│   ├── mainMenu.php        ← Hlavní menu + žebříček (jen pro přihlášené)
│   ├── profil.php          ← Profil uživatele (statistiky, achievementy, PfP)
│   ├── lesson.php          ← Herní stránka (JS generuje otázky)
│   └── .css/               ← CSS soubory (vytvoř si sám)
│       ├── index.css
│       ├── register.css
│       ├── mainMenu.css
│       ├── profil.css
│       └── lesson.css
│
└── backend/                ← PHP logika + databáze (uživatel sem nechodí)
    ├── funcDB.php          ← VŠECHNY databázové funkce (PDO)
    ├── login_process.php   ← Zpracování přihlášení
    ├── register_process.php← Zpracování registrace
    ├── save_game.php       ← Uložení výsledku hry (volá ho JS via fetch)
    └── logout.php          ← Odhlášení
```

---

## 3. Co každý soubor dělá

### `backend/funcDB.php`
Obsahuje **všechny** funkce pro práci s databází:
- `connectDB()` – PDO připojení
- `insertNewUser()` – registrace
- `getUserByUsername()` / `getUserByEmail()` / `getUserById()` – hledání uživatele
- `showUsers()` / `deleteUser()` – správa uživatelů (admin panel)
- `updateHighscore()` – aktualizace highscore po hře
- `addUserStats()` – přičtení statistik
- `getLeaderboard()` – top 10 pro žebříček
- `insertGameHistory()` / `getUserHistory()` – záznamy her
- `getAllAchievements()` / `getUserAchievements()` / `awardAchievement()` / `deleteAchievement()` – achievementy

### `backend/login_process.php`
- Přijme POST z `login.php`
- Ověří username + `password_verify()`
- Uloží `$_SESSION["user_id"]`, `username`, `teacher`, `anonym`
- Přesměruje na `mainMenu.php` nebo zpět s `?error=1`

### `backend/register_process.php`
- Přijme POST z `register.php`
- Ověří všechna pole a sílu hesla
- Zkontroluje unikátnost username a e-mailu
- Hashuje heslo (`password_hash()`)
- Vloží uživatele, nastaví session, přesměruje na `mainMenu.php`

### `backend/save_game.php`
- Volá ho `lesson.php` po skončení hry přes `fetch()`
- Uloží záznam do `game_history`
- Aktualizuje `Q_answerd`, `Q_correct`, `highscore_1gm`/`highscore_2gm`

### `frontend/lesson.php`
- Herní stránka, otázky generuje JavaScript (žádná DB = žádný cheat)
- Mód 1 (Time Attack): odpočítává čas, hodnotí rychlost
- Mód 2 (Training Lab): bez limitu, jediná chyba ukončí streak
- Po skončení odešle výsledek na `save_game.php`

---

## 4. Session proměnné (používají je všechny stránky)

| Klíč | Obsah |
|------|-------|
| `$_SESSION["user_id"]` | `ID_user` z tabulky user |
| `$_SESSION["username"]` | uživatelské jméno |
| `$_SESSION["teacher"]` | 1 = admin/učitel, 0 = student |
| `$_SESSION["anonym"]` | 1 = skrýt jméno na žebříčku |

---

## 5. Co ještě chybí (TODO pro tebe)

- **CSS soubory** – vytvoř `.css/` složku a nastyluj stránky
- **Nahrávání profilového obrázku** (PfP na profil.php) – potřebuješ `move_uploaded_file()` 
  a sloupec `profile_pic varchar(255)` v tabulce user (teď tam není!)
- **Admin panel** (`admin.php`) – CRUD pro achievementy (přidej/uprav/smaž badge)
- **Check achievements** – po každé hře v `save_game.php` zkontroluj podmínky a případně zavolej `awardAchievement()`
- Sloupec `Q_AS` v `game_history` – v kódu se předává jako `q_skipped = 0`, upřesni jeho význam

---

## 6. Jak spustit

1. Otevři XAMPP, nastartuj Apache + MySQL
2. Importuj SQL (z Gemini chatu) do phpMyAdmin → spusť ALTER TABLE výše
3. Nastav název DB v `backend/funcDB.php`
4. V prohlížeči otevři: `http://localhost/TVOJE_SLOŽKA/frontend/index.php`
