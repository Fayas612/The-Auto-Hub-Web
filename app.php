<?php
declare(strict_types=1);
ini_set('session.use_strict_mode', '1');
session_set_cookie_params(['httponly'=>true,'samesite'=>'Lax','secure'=>!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off']);
session_start();
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('X-Frame-Options: DENY');
function config(): array { return is_file(__DIR__.'/config.php') ? require __DIR__.'/config.php' : []; }
function db(): PDO {
 $c=config();
 if (!$c) throw new RuntimeException('Database is not configured.');
 return new PDO($c['dsn'], $c['user'], $c['password'], [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false]);
}
function h(mixed $s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function csrf(): string { return $_SESSION['csrf'] ??= bin2hex(random_bytes(32)); }
function verify_csrf(): void { if (!hash_equals(csrf(), (string)($_POST['csrf']??''))) { http_response_code(403); exit('Session expired. Reload the page and try again.'); } }
const SERVICES = ['nano'=>'Nano coating','windscreen'=>'Windscreen Polishing','headlights'=>'Headlight polishing'];
// Preserve original service names on historical bookings.
const SERVICE_LABELS = SERVICES + ['ceramic'=>'Ceramic coating'];


