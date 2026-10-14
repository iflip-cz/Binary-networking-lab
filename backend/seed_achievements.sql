-- =========================================================
--  seed_achievements.sql
--  Fills the `achivements` table with the badge set that
--  backend/funcDB.php → checkAndAwardAchievements() knows how
--  to award. Matched by Name, so keep these in sync with the
--  $rules array in that function.
--
--  Safe to re-run: existing badges are updated in place, matched by
--  the UNIQUE Name from migration_unique_keys.sql (run that first —
--  without it a re-run would insert duplicates). It never deletes:
--  deleting an achievement also deletes every award of it (FK CASCADE),
--  so badges players already earned are kept.
--
--  Run in phpMyAdmin (SQL tab) or:
--    mysql -u root projekt_zwa < backend/seed_achievements.sql
-- =========================================================

INSERT INTO achivements (Name, rarity, if_condition) VALUES
('První hra',     'common',    'Dokonči svou první hru'),
('Stovka otázek', 'common',    'Zodpověz celkem 100 otázek'),
('Tisíc otázek',  'epic',      'Zodpověz celkem 1000 otázek'),
('Přesná muška',  'rare',      '90% přesnost (min. 20 zodpovězených)'),
('Série 10',      'rare',      'Dosáhni série 10 správných v řadě'),
('Série 25',      'epic',      'Dosáhni série 25 správných v řadě'),
('Rychloprsty',   'rare',      '20+ bodů v jedné hře Time Attack'),
('Mistr převodů', 'legendary', '40+ bodů v jedné hře Time Attack'),
('Bez chybičky',  'rare',      'Dohraj hru bez jediné chyby (min. 10 správně)')
ON DUPLICATE KEY UPDATE
    rarity       = VALUES(rarity),
    if_condition = VALUES(if_condition);
