-- N4IA LOVE U 4LL — leaderboard table.
-- Run ONE of the blocks below, for the database you use. Then put the connection details in db-config.php.

-- ---------- MySQL / MariaDB ----------
CREATE TABLE IF NOT EXISTS leaderboard (
  player_id  VARCHAR(64)       NOT NULL,
  name       VARCHAR(16)       NOT NULL,
  email      VARCHAR(254)      NULL,            -- for contacting prize winners; never shown in the game
  score      INT UNSIGNED      NOT NULL DEFAULT 0,
  hearts     INT UNSIGNED      NOT NULL DEFAULT 0,
  badges     SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  created_at DATETIME          NOT NULL,
  updated_at DATETIME          NOT NULL,
  PRIMARY KEY (player_id),
  KEY idx_score (score)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table created before the email column existed? Add it with:
-- ALTER TABLE leaderboard ADD COLUMN email VARCHAR(254) NULL AFTER name;

-- A database user that can only read and write this table (replace the password):
-- CREATE USER 'n4ia_game'@'localhost' IDENTIFIED BY 'CHANGE_ME';
-- GRANT SELECT, INSERT, UPDATE ON n4ia.leaderboard TO 'n4ia_game'@'localhost';

-- ---------- PostgreSQL ----------
-- CREATE TABLE IF NOT EXISTS leaderboard (
--   player_id  VARCHAR(64) PRIMARY KEY,
--   name       VARCHAR(16) NOT NULL,
--   email      VARCHAR(254),
--   score      INTEGER     NOT NULL DEFAULT 0 CHECK (score >= 0),
--   hearts     INTEGER     NOT NULL DEFAULT 0,
--   badges     SMALLINT    NOT NULL DEFAULT 0,
--   created_at TIMESTAMP   NOT NULL,
--   updated_at TIMESTAMP   NOT NULL
-- );
-- CREATE INDEX IF NOT EXISTS idx_leaderboard_score ON leaderboard (score);
-- (existing table: ALTER TABLE leaderboard ADD COLUMN email VARCHAR(254);)

-- ---------- SQLite (local testing) ----------
-- CREATE TABLE IF NOT EXISTS leaderboard (
--   player_id  TEXT PRIMARY KEY,
--   name       TEXT    NOT NULL,
--   email      TEXT,
--   score      INTEGER NOT NULL DEFAULT 0,
--   hearts     INTEGER NOT NULL DEFAULT 0,
--   badges     INTEGER NOT NULL DEFAULT 0,
--   created_at TEXT    NOT NULL,
--   updated_at TEXT    NOT NULL
-- );
-- CREATE INDEX IF NOT EXISTS idx_leaderboard_score ON leaderboard (score);
