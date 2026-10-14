-- =========================================================
--  migration_unique_keys.sql
--  Lets the database itself refuse duplicates:
--   * user.username / user.email — register_process.php checks first, but two
--     requests at the same moment can both pass that check; an index can't be raced.
--   * achivements.Name — lets seed_achievements.sql update badges in place
--     instead of deleting them (which would wipe everyone's earned badges).
--
--  Run once per environment (phpMyAdmin SQL tab, or):
--    mysql -u root projekt_zwa < backend/migration_unique_keys.sql
--
--  The two statements are independent. If one fails with "Duplicate entry",
--  existing rows already clash — find them, fix the extras, re-run just that one:
--    SELECT username, COUNT(*) FROM user GROUP BY username HAVING COUNT(*) > 1;
--    SELECT email,    COUNT(*) FROM user GROUP BY email    HAVING COUNT(*) > 1;
--    SELECT Name,     COUNT(*) FROM achivements GROUP BY Name HAVING COUNT(*) > 1;
-- =========================================================

ALTER TABLE achivements
    ADD UNIQUE KEY uq_achivements_name (Name);

ALTER TABLE user
    ADD UNIQUE KEY uq_user_username (username),
    ADD UNIQUE KEY uq_user_email    (email);
