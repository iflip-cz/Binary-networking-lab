# Binary Networking Lab (BNL)

Webová aplikace pro procvičování převodů mezi číselnými soustavami
(binární, hexadecimální, oktalová a decimální). Semestrální projekt do předmětu
**ZWA – Základy webových aplikací**.

Uživatel se zaregistruje, hraje herní módy na čas nebo na streak, sbírá skóre do
globálního i třídního žebříčku. Učitelé zakládají třídy a sledují statistiky svých
studentů.

## Funkce
- **Registrace / přihlášení** – hesla hashovaná přes `password_hash()` (bcrypt).
- **Herní módy** (otázky generuje JavaScript v prohlížeči):
  - *Time Attack* – 30/60/120 s na co nejvíce správných odpovědí.
  - *Streak Challenge* – buduj co nejdelší sérii, chyba ji resetuje.
  - *Training Lab* – bez limitu, po chybě se zobrazí postup řešení.
- **Žebříčky** – globální (Time Attack / Streak) i v rámci třídy.
- **Třídy** – učitel zakládá třídu s kódem, student se připojí kódem.
- **Profil** – highscore, přesnost, historie posledních her, achievementy.
- **Světlý / tmavý režim** – ukládá se do `localStorage` (`bnl-theme`).

## Technologie
- **Backend:** PHP + PDO, MySQL/MariaDB
- **Frontend:** server-rendered PHP + vanilla JavaScript, čisté CSS (bez frameworku)
- **Server:** XAMPP (Apache + MySQL)

## Struktura
```
project/
├── index.php          ← landing page (kořen webu = tato stránka)
├── frontend/          ← ostatní stránky, které vidí uživatel
│   ├── login.php  register.php
│   ├── mainMenu.php  profil.php  lesson.php  class_view.php
│   └── s.css/         ← CSS (jeden soubor na stránku)
├── backend/           ← PHP logika + přístup k DB
│   ├── funcDB.php     ← všechny databázové funkce (PDO)
│   ├── session.php    ← start session (bezpečná cookie) + CSRF tokeny — includuje ho každá stránka
│   ├── login_process.php  register_process.php  logout.php
│   └── save_game.php  ← uloží výsledek hry (volá JS přes fetch)
└── IMPORTANT_NOTES.md ← podrobné poznámky k nastavení + TODO
```

## Instalace / spuštění
1. Nainstaluj **XAMPP**, spusť **Apache** a **MySQL**.
2. Naklonuj/zkopíruj projekt do `htdocs` (např. `htdocs/project`).
3. V **phpMyAdmin** vytvoř databázi a importuj SQL schéma. Poté spusť opravy
   délek sloupců (jinak registrace tiše selže):
   ```sql
   ALTER TABLE `user` MODIFY COLUMN `password` varchar(255) NOT NULL;
   ALTER TABLE `user` MODIFY COLUMN `email`    varchar(100) NOT NULL;
   ```
4. Zkopíruj `backend/config.example.php` na `backend/config.php` a vyplň přístup
   k DB tohoto serveru (bez něj se použije lokální XAMPP: root / bez hesla / `projekt_zwa`).
5. Spusť jednou migrace a naplň achievementy (v tomto pořadí):
   `backend/migration_add_sys_type.sql`, `backend/migration_unique_keys.sql`,
   `backend/seed_achievements.sql`.
6. Otevři v prohlížeči: `http://localhost/project/` (kořen načte `index.php`)

## Session proměnné
| Klíč | Význam |
|------|--------|
| `user_id`  | `ID_user` přihlášeného uživatele |
| `username` | uživatelské jméno |
| `teacher`  | 1 = učitel, 0 = student (volí se při registraci — **není** to admin) |
| `anonym`   | 1 = skrýt jméno na žebříčku |
| `csrf`     | CSRF token pro formuláře a `save_game.php` (viz `backend/session.php`) |
| `games`    | rozehrané hry (token → mód, čas, start) — `save_game.php` podle nich ověřuje výsledek |

## Známá omezení / TODO
Kompletní seznam viz [`IMPORTANT_NOTES.md`](IMPORTANT_NOTES.md). Hlavní body:
- **Skóre je počítané na klientovi** – `save_game.php` ověřuje, že hra byla
  opravdu spuštěná, uloží se jen jednou a počet odpovědí odpovídá skutečně
  uplynulému času. Úplně podvodu zabránit nejde, dokud otázky generuje prohlížeč.
- **Role „učitel" je při registraci volitelná zaškrtávátkem** – kdokoli se může
  zaregistrovat jako učitel. Admin panel proto musí mít vlastní příznak, ne `teacher`.
- Nahrávání profilového obrázku a admin panel zatím nejsou hotové.
