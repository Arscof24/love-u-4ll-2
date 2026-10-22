N4IA_LOVE_U_4LL
===============

Main page: N4IA_LOVE_U_4LL.html
Upload the whole folder and keep the folder structure as it is.

Contents
  N4IA_LOVE_U_4LL.html                  the game (HTML, CSS and JavaScript in one file)
  links.js                              every hyperlink in the game                            <- edit to change links
  config.js                             site settings: where the leaderboard API is            <- server team edits
  api/db-config.php                     database connection (DSN, user, password)              <- server team edits
  api/leaderboard.php                   leaderboard API the game talks to (GET top 10, POST a score)
  api/schema.sql                        creates the leaderboard table (MySQL/MariaDB, PostgreSQL, SQLite)
  api/.htaccess                         Apache: blocks direct access to db-config.php and schema.sql
  assets/sprites.png                    character animation frames
  assets/world.jpg                      background
  assets/music.mp3                      background music (LOVE U 4LL 8-bit intro, 128 kbps)
  assets/fonts/MSW98UI-Bold.otf         title font (supplied for this project)
  assets/fonts/dm-mono-latin-*.woff2    body font, DM Mono
  assets/fonts/DM-Mono-OFL.txt          DM Mono license (SIL Open Font License 1.1)

Leaderboard setup (SQL database)
  The browser never talks to the database directly; it calls api/leaderboard.php, which does.
  1. Needs PHP 7.4 or newer with PDO and the driver for your database (pdo_mysql, pdo_pgsql or pdo_sqlite).
  2. Create the table: run the matching block of api/schema.sql. Ideally create a database user that can only
     SELECT, INSERT and UPDATE that table (example in schema.sql).
  3. Fill in api/db-config.php: dsn, user, password (and table name if you changed it).
     If your host allows it, move db-config.php outside the public web folder and update $CONFIG_FILE at the top
     of leaderboard.php.
  4. config.js: leaderboardApi is 'api/leaderboard.php' by default. If the API lives on another domain, use its
     full https:// address there and set allow_origin in db-config.php to the game's address.
     Set leaderboardApi to '' to switch the online leaderboard off.
  5. Check it: open api/leaderboard.php in a browser. It should show {"rows":[]} (or the current scores).
     {"error":"database_unavailable"} means the connection details in db-config.php are wrong; the exact reason
     is written to the PHP error log.
  If the API cannot be reached, the game keeps working and saves scores on the player's device, and says so.

  On nginx (no .htaccess), block those files in the site config, for example:
    location ~ ^/api/(db-config\.php|schema\.sql)$ { deny all; }

Leaderboard race and emails
  The start screen asks for a name and, optionally, an email. Only players who give a valid email take part in the
  leaderboard race: their scores are sent to the API. Players without an email still play; their scores stay on
  their own device. The API requires a valid email on every score it saves, stores it in the email column, and
  never sends emails back to the game. Read them straight from the database to contact winners, for example:
    SELECT name, email, score, updated_at FROM leaderboard ORDER BY score DESC LIMIT 20;
  Emails are personal data: keep the database private, and say on the rules page how they are used and kept.

How players are counted
  Each browser gets a random player id (kept in localStorage). The table keeps one row per player id with their
  best score and latest name. There are no accounts, so the scores are trust-based: anyone determined can post a
  fake score. The API checks the format, caps the score (max_score) and limits how often one player can post.

Serving notes
  - Open it through a web server (http:// or https://). Opened straight from disk (file://), music and the
    leaderboard API will not load.
  - If your server needs the start page to be index.html, rename N4IA_LOVE_U_4LL.html to index.html.
  - Player name, badges and language are kept in the visitor's browser (localStorage).
  - All links (website, race rules, release video, pause page, CONTINUE pages) are set in links.js.

Check before going public
  - The license of MSW98UI-Bold.otf (it is served to visitors).
