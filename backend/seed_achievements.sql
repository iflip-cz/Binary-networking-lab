-- =========================================================
--  seed_achievements.sql
--  Fills the `achivements` table with the badge set that
--  backend/funcDB.php → checkAndAwardAchievements() knows how
--  to award. Matched by Name, so keep these in sync with the
--  $rules array in that function.
--
--  Safe to re-run: it clears the table first. Run in phpMyAdmin
--  (SQL tab) or:  mysql -u root projekt_zwa < backend/seed_achievements.sql
-- =========================================================

DELETE FROM user_achivements;
DELETE FROM achivements;
ALTER TABLE achivements AUTO_INCREMENT = 1;

INSERT INTO achivements (Name, rarity, if_condition) VALUES
('První hra',     'common',    'Dokonči svou první hru'),
('Stovka otázek', 'common',    'Zodpověz celkem 100 otázek'),
('Tisíc otázek',  'epic',      'Zodpověz celkem 1000 otázek'),
('Přesná muška',  'rare',      '90% přesnost (min. 20 zodpovězených)'),
('Série 10',      'rare',      'Dosáhni série 10 správných v řadě'),
('Série 25',      'epic',      'Dosáhni série 25 správných v řadě'),
('Rychloprsty',   'rare',      '20+ bodů v jedné hře Time Attack'),
('Mistr převodů', 'legendary', '40+ bodů v jedné hře Time Attack'),
('Bez chybičky',  'rare',      'Dohraj hru bez jediné chyby (min. 10 správně)');
