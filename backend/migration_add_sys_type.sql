-- =========================================================
--  migration_add_sys_type.sql
--  Records which number system a game was played in so the
--  leaderboards can be filtered by bin / hex / oct / all.
--  Existing rows default to 'all'.
--
--  Run once per environment (phpMyAdmin SQL tab, or):
--    mysql -u root projekt_zwa < backend/migration_add_sys_type.sql
-- =========================================================

ALTER TABLE game_history
    ADD COLUMN sys_type VARCHAR(4) NOT NULL DEFAULT 'all' AFTER Gm;
