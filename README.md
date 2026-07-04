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
4. V `backend/funcDB.php` nastav v `connectDB()` správný název databáze
   (`dbname=...`), případně uživatele a heslo.
5. Otevři v prohlížeči: `http://localhost/project/` (kořen načte `index.php`)

## Session proměnné
| Klíč | Význam |
|------|--------|
| `user_id`  | `ID_user` přihlášeného uživatele |
| `username` | uživatelské jméno |
| `teacher`  | 1 = učitel/admin, 0 = student |
| `anonym`   | 1 = skrýt jméno na žebříčku |

## Známá omezení / TODO
Kompletní seznam viz [`IMPORTANT_NOTES.md`](IMPORTANT_NOTES.md). Hlavní body:
- **Skóre je počítané na klientovi** – `save_game.php` věří datům z prohlížeče,
  takže žebříček lze teoreticky podvést. Případně doplnit validaci/clamp.
- **Role „učitel" je při registraci volitelná zaškrtávátkem** – kdokoli se může
  zaregistrovat jako učitel.
- Achievementy jsou připravené v DB, ale zatím se nikde neudělují.
- Nahrávání profilového obrázku a admin panel zatím nejsou hotové.
