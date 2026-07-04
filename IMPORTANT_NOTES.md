# Projekt – Poznámky (aktuální stav)

Kompletní setup a spuštění je v [README.md](README.md).
Tento soubor shrnuje, co je hotové a co ještě zbývá.

---

## Stav databáze
- ✅ `user.password` je `varchar(255)` a `user.email` `varchar(100)` — kritická
  oprava délek sloupců **už je aplikovaná** (dřív hrozilo tiché selhání registrace).
- ✅ Název databáze `projekt_zwa` odpovídá `backend/funcDB.php` → `connectDB()`.
- ✅ Tabulka `achivements` je naplněná — viz `backend/seed_achievements.sql`.
- ⚠️ Po nasazení spusť `backend/migration_add_sys_type.sql` — přidá sloupec
  `game_history.sys_type`, aby šel žebříček filtrovat podle soustavy (bin/hex/oct).

---

## Co už funguje
- Registrace / přihlášení / odhlášení (hesla přes `password_hash()` / `password_verify()`).
- Herní módy: **Time Attack**, **Streak Challenge**, **Training Lab** (otázky generuje JS).
- Globální i třídní žebříčky, třídy (učitel zakládá kódem, student se připojuje).
- Profil: statistiky, historie posledních her, získané achievementy.
- CSS pro všechny stránky + **konzistentní světlý/tmavý režim** (výchozí je tmavý,
  přepínač `[ dark ]/[ light ]` ukládá volbu do `localStorage`).
- **Udělování achievementů** po dohrání hry — `save_game.php` volá
  `checkAndAwardAchievements()` a nové odznaky ukáže na obrazovce konce hry.
- **Ochrana skóre (anti-cheat)** — `save_game.php` odmítá výsledky, které jsou
  nemožné (záporné, součet nesedí, streak > správně) nebo nereálné (příliš mnoho
  odpovědí za daný čas). Nezabrání to všemu, ale zastaví triviální podvod se skóre.

---

## Achievementy – jak to funguje
1. Seznam odznaků žije v tabulce `achivements`. Nasadíš/resetuješ ho souborem
   `backend/seed_achievements.sql` (v phpMyAdmin nebo `mysql -u root projekt_zwa < ...`).
2. Podmínky se vyhodnocují v `checkAndAwardAchievements()` v `backend/funcDB.php`
   a párují se s odznaky **podle sloupce `Name`**.
3. Přidat nový odznak = přidat řádek do seed SQL **a** pravidlo (se stejným `Name`)
   do pole `$rules` v té funkci.

---

## Session proměnné (používají je všechny stránky)
| Klíč | Obsah |
|------|-------|
| `$_SESSION["user_id"]`  | `ID_user` z tabulky user |
| `$_SESSION["username"]` | uživatelské jméno |
| `$_SESSION["teacher"]`  | 1 = učitel/admin, 0 = student |
| `$_SESSION["anonym"]`   | 1 = skrýt jméno na žebříčku |

---

## Co ještě zbývá (TODO)
- **Nahrávání profilového obrázku** — potřebuje `move_uploaded_file()` a sloupec
  `profile_pic varchar(255)` v tabulce `user` (teď tam není).
- **Admin panel** (`admin.php`) — CRUD achievementů a správa uživatelů. Funkce
  `showUsers()`, `deleteUser()`, `deleteAchievement()` už v `funcDB.php` čekají.
- Sloupec `Q_AS` v `game_history` se zatím posílá jako `0` (q_skipped) — buď
  doplnit „přeskočené" otázky do hry, nebo sloupec odstranit.
- (Ke zvážení) Role „učitel" jde při registraci zvolit zaškrtávátkem. Je to záměr,
  ale pro ostrý provoz by dávalo smysl schvalování učitelů.
