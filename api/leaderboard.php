<?php
/*
 * N4IA LOVE U 4LL — leaderboard API.
 *
 *   GET  leaderboard.php?limit=10&player=<player id>
 *        -> {"rows":[{"name":"N4IA Fan","score":1305,"me":true}, ...]}
 *   POST leaderboard.php   body: {"player_id":"...","name":"...","email":"fan@example.com","score":1305,"hearts":27,"badges":5}
 *        -> {"ok":true,"best":1305}
 *
 * Each player (a random id the game keeps in the browser) has one row holding their best score.
 * Only players who gave an email take part in the leaderboard race, so POST requires a valid email.
 * Emails are stored for contacting prize winners and are NEVER returned by GET.
 * The database connection lives in db-config.php. Needs PHP 7.4+ with PDO and the driver for your database.
 */
declare(strict_types=1);

$CONFIG_FILE = __DIR__ . '/db-config.php';   // change this path if you move db-config.php out of the web folder

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
date_default_timezone_set('UTC');

$cfg = require $CONFIG_FILE;

if (!empty($cfg['allow_origin'])) {
    header('Access-Control-Allow-Origin: ' . $cfg['allow_origin']);
    header('Vary: Origin');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
}
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($method === 'OPTIONS') { http_response_code(204); exit; }

function respond(int $code, array $body): void {
    http_response_code($code);
    echo json_encode($body, JSON_UNESCAPED_UNICODE);
    exit;
}
function clean_name(string $v): string {
    $v = preg_replace('/[\x00-\x1F\x7F<>]/u', '', $v) ?? '';
    $v = trim(preg_replace('/\s+/u', ' ', $v) ?? '');
    return function_exists('mb_substr') ? mb_substr($v, 0, 16, 'UTF-8') : substr($v, 0, 16);
}
function clean_email(string $v): string {
    $v = strtolower(trim($v));
    if (strlen($v) > 254 || filter_var($v, FILTER_VALIDATE_EMAIL) === false) return '';
    return $v;
}
function valid_player_id(string $v): bool {
    return (bool) preg_match('/^[A-Za-z0-9-]{8,64}$/', $v);
}

try {
    $pdo = new PDO($cfg['dsn'], $cfg['user'] ?? null, $cfg['password'] ?? null, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (Throwable $e) {
    error_log('[n4ia leaderboard] database connection failed: ' . $e->getMessage());
    respond(503, ['error' => 'database_unavailable']);
}
$table = preg_replace('/[^A-Za-z0-9_]/', '', (string) ($cfg['table'] ?? 'leaderboard'));

try {
    if ($method === 'GET') {
        $cap   = (int) ($cfg['max_rows_returned'] ?? 50);
        $limit = max(1, min($cap, (int) ($_GET['limit'] ?? 10)));
        $me    = (string) ($_GET['player'] ?? '');
        $stmt  = $pdo->query("SELECT player_id, name, score FROM $table ORDER BY score DESC, updated_at ASC LIMIT $limit");
        $rows  = [];
        foreach ($stmt as $r) {
            $rows[] = [
                'name'  => $r['name'],
                'score' => (int) $r['score'],
                'me'    => $me !== '' && hash_equals((string) $r['player_id'], $me),
            ];
        }
        respond(200, ['rows' => $rows]);
    }

    if ($method === 'POST') {
        $raw = file_get_contents('php://input', false, null, 0, 4096);
        $in  = json_decode($raw ?: '', true);
        if (!is_array($in)) respond(400, ['error' => 'bad_json']);

        $id     = (string) ($in['player_id'] ?? '');
        $name   = clean_name((string) ($in['name'] ?? ''));
        $email  = clean_email((string) ($in['email'] ?? ''));
        $score  = filter_var($in['score'] ?? null, FILTER_VALIDATE_INT,
                    ['options' => ['min_range' => 0, 'max_range' => (int) ($cfg['max_score'] ?? 5000000)]]);
        $hearts = max(0, min(1000000, (int) ($in['hearts'] ?? 0)));
        $badges = max(0, min(255, (int) ($in['badges'] ?? 0)));
        if (!valid_player_id($id) || $name === '' || $score === false) respond(422, ['error' => 'invalid_entry']);
        if ($email === '') respond(422, ['error' => 'invalid_email']);

        $now = gmdate('Y-m-d H:i:s');
        $pdo->beginTransaction();
        $sel = $pdo->prepare("SELECT score, updated_at FROM $table WHERE player_id = ?");
        $sel->execute([$id]);
        $row = $sel->fetch();

        if (!$row) {
            $ins = $pdo->prepare("INSERT INTO $table (player_id, name, email, score, hearts, badges, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $ins->execute([$id, $name, $email, $score, $hearts, $badges, $now, $now]);
            $pdo->commit();
            respond(200, ['ok' => true, 'best' => $score]);
        }

        $wait = (int) ($cfg['min_seconds_between_posts'] ?? 5);
        if ($wait > 0 && strtotime((string) $row['updated_at'] . ' UTC') > time() - $wait) {
            $pdo->commit();
            respond(429, ['error' => 'too_many_requests']);
        }
        if ($score > (int) $row['score']) {
            $upd = $pdo->prepare("UPDATE $table SET name = ?, email = ?, score = ?, hearts = ?, badges = ?, updated_at = ? WHERE player_id = ?");
            $upd->execute([$name, $email, $score, $hearts, $badges, $now, $id]);
            $best = $score;
        } else {
            // not a new best: keep the score, but keep the latest name and email
            $upd = $pdo->prepare("UPDATE $table SET name = ?, email = ?, updated_at = ? WHERE player_id = ?");
            $upd->execute([$name, $email, $now, $id]);
            $best = (int) $row['score'];
        }
        $pdo->commit();
        respond(200, ['ok' => true, 'best' => $best]);
    }

    respond(405, ['error' => 'method_not_allowed']);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('[n4ia leaderboard] ' . $e->getMessage());
    respond(500, ['error' => 'server_error']);
}
