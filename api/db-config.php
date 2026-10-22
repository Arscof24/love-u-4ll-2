<?php
/*
 * N4IA LOVE U 4LL — leaderboard database connection.
 *
 * THIS IS THE FILE THE SERVER TEAM EDITS. Fill in the connection details below.
 * Never put real passwords in a public repository. If the host allows it, move this
 * file outside the public web folder and update the path at the top of leaderboard.php.
 */
return [
    // PDO connection string. Pick the line for your database and fill in host, port and database name.
    //   MySQL / MariaDB : 'mysql:host=localhost;port=3306;dbname=n4ia;charset=utf8mb4'
    //   PostgreSQL      : 'pgsql:host=localhost;port=5432;dbname=n4ia'
    //   SQLite (testing): 'sqlite:' . __DIR__ . '/leaderboard.sqlite'
    'dsn'      => 'mysql:host=localhost;port=3306;dbname=n4ia;charset=utf8mb4',
    'user'     => 'n4ia_game',
    'password' => 'CHANGE_ME',

    // Table name (create it with schema.sql).
    'table'    => 'leaderboard',

    // Only needed when the game and this API are on DIFFERENT domains, e.g. 'https://game.papionofficial.com'.
    // Leave '' when both are served from the same site.
    'allow_origin' => '',

    // Basic sanity limits on submissions.
    'max_score'                 => 5000000,  // highest score accepted
    'min_seconds_between_posts' => 5,        // per player, to slow down spam
    'max_rows_returned'         => 50,       // cap for ?limit=
];
