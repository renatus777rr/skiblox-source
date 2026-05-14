<?php
declare(strict_types=1);
// common.php
ini_set('session.use_strict_mode', '1');

session_name('SKIBLOXSESSID');
$secureCookie = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['SERVER_PORT'] ?? '') === '443';
session_set_cookie_params([
  'lifetime' => 0,
  'path' => '/',
  'secure' => $secureCookie,
  'httponly' => true,
  'samesite' => 'Lax',
]);

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

send_security_headers();
require_once __DIR__ . '/db.php';

date_default_timezone_set('Europe/Minsk');

function redirect(string $path): void {
  $path = trim($path);
  if (!preg_match('~^https?://~i', $path)) {
    $path = '/' . ltrim($path, '/');
  }
  $safePath = str_replace(["\r", "\n"], '', $path);
  header('Location: ' . $safePath);
  exit;
}

function send_security_headers(): void {
  if (headers_sent()) {
    return;
  }
  header('X-Frame-Options: DENY');
  header('X-Content-Type-Options: nosniff');
  header('Referrer-Policy: strict-origin-when-cross-origin');
  header('Permissions-Policy: geolocation=(), microphone=(), camera=(), fullscreen=()');
  header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://fonts.googleapis.com; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; img-src 'self' data:; font-src 'self' https://fonts.gstatic.com; frame-ancestors 'none'; base-uri 'self';");
}

function csrf_token(): string {
  if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
  }
  return $_SESSION['csrf_token'];
}

function validate_csrf(string $token): bool {
  return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function generate_account_cookie_hash(): string {
  return bin2hex(random_bytes(32));
}

function set_account_cookie(string $cookieHash): void {
  if (headers_sent()) {
    return;
  }
  $secureCookie = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['SERVER_PORT'] ?? '') === '443';
  setcookie(
    '_DO_NOT_SHARE_THIS_COOKIE_',
    $cookieHash,
    [
      'expires' => 0,
      'path' => '/',
      'secure' => $secureCookie,
      'httponly' => true,
      'samesite' => 'Lax',
    ]
  );
}

function get_account_cookie_from_request(): ?string {
  return $_COOKIE['_DO_NOT_SHARE_THIS_COOKIE_'] ?? null;
}

function verify_account_cookie(PDO $pdo, int $userId): bool {
  $cookieFromRequest = get_account_cookie_from_request();
  if (!$cookieFromRequest) {
    return false;
  }
  
  $stmt = $pdo->prepare('SELECT account_cookie_hash FROM users WHERE id = ? LIMIT 1');
  $stmt->execute([$userId]);
  $user = $stmt->fetch(PDO::FETCH_ASSOC);
  
  if (!$user || !$user['account_cookie_hash']) {
    return false;
  }
  
  return hash_equals($user['account_cookie_hash'], $cookieFromRequest);
}



function render_maintenance_page(string $text = 'Maintenance, Best Music: Dave Blunts - The Cup') {
    if (!headers_sent()) {
        http_response_code(503);           // Service Unavailable
        header('Retry-After: 3600');       // Hint: try again in 1 hour
        header('Content-Type: text/html; charset=UTF-8');
    }
    echo '<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Maintenance</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
  html, body { height:100%; margin:0; }
  body { background:#000; color:#fff; display:flex; align-items:center; justify-content:center; font-family:Arial, sans-serif; }
  .msg { font-size:32px; font-weight:800; letter-spacing:0.5px; }
</style>
</head>
<body>
  <div class="msg">'.htmlspecialchars($text, ENT_QUOTES, 'UTF-8').'</div>
</body>
</html>';
    exit; // hard stop
}

/**
 * Call this early on every page, before any output.
 * If you want admins to bypass maintenance, pass $user and set $allowAdminBypass = true.
 */
function maintenance_gate(PDO $pdo, $user = null, bool $allowAdminBypass = false) {
    $cfg = get_config($pdo);

    // Optional: admin bypass (adapt the predicate to your schema)
    $isAdmin = $allowAdminBypass && $user && !empty($user['is_admin']); // or user_has_role($user, 'admin')

    if (!empty($cfg['maintenance_enabled']) && !$isAdmin) {
        $text = !empty($cfg['maintenance_message']) ? $cfg['maintenance_message'] : 'Maintenance';
        render_maintenance_page($text);
    }
}

function get_config($pdo) {
  static $cfg = null;
  if ($cfg !== null) return $cfg;
  $stmt = $pdo->query('SELECT * FROM configuration WHERE id = 1');
  $cfg = $stmt->fetch();
  if (!$cfg) {
    $cfg = array(
      'maintenance_enabled' => 0,
      'banner_enabled' => 0,
      'banner_message' => ''
    );
  }
  return $cfg;
}

function current_user(PDO $pdo) {
  if (empty($_SESSION['uid']) || !is_numeric($_SESSION['uid'])) {
    return null;
  }

  static $cache = null;
  static $hasLastOnline = null;

  if ($cache !== null) return $cache;

  $uid = (int)$_SESSION['uid'];

  // Detect last_online column once per request
  if ($hasLastOnline === null) {
    $hasLastOnline = (bool)$pdo->query("SHOW COLUMNS FROM users LIKE 'last_online'")->fetch();
  }

  // Heartbeat: update last_online if the column exists
  if ($hasLastOnline) {
    $stmt = $pdo->prepare('UPDATE users SET last_online = NOW() WHERE id = :id');
    $stmt->execute([':id' => $uid]);
  }

  // Fetch the user
  $stmt = $pdo->prepare(
    'SELECT id, username, email, sibux, tixs, date_join, ' .
    ($hasLastOnline ? 'last_online' : 'NULL AS last_online') .
    ' FROM users WHERE id = :id LIMIT 1'
  );
  $stmt->execute([':id' => $uid]);
  $user = $stmt->fetch(PDO::FETCH_ASSOC);

  $cache = $user ?: null;
  return $cache;
}

function get_active_ban(PDO $pdo, int $userId) {
  $stmt = $pdo->prepare('
    SELECT id, reason, banned_at
    FROM bans
    WHERE user_id = ? AND active = 1
    ORDER BY id DESC
    LIMIT 1
  ');
  $stmt->execute([$userId]);
  return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

function enforce_not_banned($pdo, $user) {
  if (!$user) return;

  $ban = get_active_ban($pdo, (int)$user['id']);
  if (!$ban) return;

  $isAdmin = !empty($user['is_admin']);

  // Render a minimal full-screen ban page and stop
  header('Content-Type: text/html; charset=utf-8');
  http_response_code(403);
  $reason = htmlspecialchars($ban['reason'], ENT_QUOTES, 'UTF-8');
  $date   = htmlspecialchars(date('M j, Y H:i', strtotime($ban['banned_at'])), ENT_QUOTES, 'UTF-8');

  echo '<!doctype html><html><head><meta charset="utf-8"><title>Banned</title>'
     . '<meta name="viewport" content="width=device-width, initial-scale=1">'
     . '<style>html,body{margin:0;height:100%;background:#000;color:#fff;font-family:system-ui,-apple-system,Segoe UI,Roboto,sans-serif}'
     . '.wrap{min-height:100%;display:flex;align-items:center;justify-content:center;padding:24px}'
     . '.card{max-width:560px;width:100%;background:#0d0e12;border:1px solid #2a2d36;border-radius:12px;padding:20px}'
     . 'h1{margin:0 0 10px;font-size:28px} .meta{opacity:.85;margin-top:6px}'
     . '.btn{margin-top:14px;background:#374151;color:#fff;border:none;border-radius:10px;padding:10px 14px;cursor:pointer}'
     . '</style></head><body><div class="wrap"><div class="card">'
     . '<h1>You Banned!</h1>'
     . '<div>Reason: ' . $reason . '</div>'
     . '<div class="meta">Date: ' . $date . '</div>';

  if ($isAdmin) {
    // Allow admins to unban this user directly
    echo '<form method="post" action="/admin" style="margin-top:12px">'
       . '<input type="hidden" name="action" value="unban">'
       . '<input type="hidden" name="username" value="' . htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8') . '">'
       . '<button class="btn" type="submit">Unban</button></form>';
  }

  echo '</div></div></body></html>';
  exit;
}

