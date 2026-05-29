<?php
declare(strict_types=1);

ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('session.use_strict_mode', '1');
ini_set('session.use_cookies', '1');
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax');

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

const SESSION_TIMEOUT_SECONDS = 1800;
const ACCOUNT_COOKIE_NAME = '_SKIBLOX_AUTH';

function fetch_env(string $key, string $default = ''): string {
    $value = getenv($key);
    return $value === false ? $default : $value;
}

function redirect(string $path): void {
    $path = trim($path);
    if ($path === '') {
        $path = '/';
    }

    if (preg_match('~^[a-z][a-z0-9+.-]*://~i', $path)) {
        $currentHost = $_SERVER['HTTP_HOST'] ?? '';
        $destination = parse_url($path, PHP_URL_HOST) ?: '';
        if ($destination !== '' && strcasecmp($destination, $currentHost) !== 0) {
            $path = '/';
        }
    }

    if (!str_starts_with($path, '/')) {
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

    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
    }
    header('X-Frame-Options: DENY');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: geolocation=(), microphone=(), camera=(), fullscreen=()');
    header('X-Permitted-Cross-Domain-Policies: none');
    header('X-Download-Options: noopen');
    header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com; img-src 'self' data:; frame-ancestors 'none'; base-uri 'self';");
}

function csrf_token(): string {
    if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validate_csrf(string $token): bool {
    return !empty($token) && isset($_SESSION['csrf_token']) && hash_equals((string)$_SESSION['csrf_token'], $token);
}

function valid_username(string $username): bool {
    return preg_match('/^[A-Za-z0-9_.]{3,32}$/', $username) === 1;
}

function valid_email(string $email): bool {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function valid_password(string $password): bool {
    return mb_strlen($password) >= 10;
}

function generate_secure_token(int $length = 32): string {
    return bin2hex(random_bytes($length));
}

function get_account_cookie_from_request(): ?string {
    return $_COOKIE[ACCOUNT_COOKIE_NAME] ?? null;
}

function set_account_cookie(string $cookieHash): void {
    if (headers_sent()) {
        return;
    }

    $secureCookie = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['SERVER_PORT'] ?? '') === '443';
    setcookie(
        ACCOUNT_COOKIE_NAME,
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

function clear_account_cookie(): void {
    if (headers_sent()) {
        return;
    }

    setcookie(ACCOUNT_COOKIE_NAME, '', [
        'expires' => time() - 3600,
        'path' => '/',
        'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['SERVER_PORT'] ?? '') === '443',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

function get_or_create_account_cookie_hash(PDO $pdo, int $userId): string {
    $stmt = $pdo->prepare('SELECT account_cookie_hash FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$userId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!empty($row['account_cookie_hash'])) {
        return $row['account_cookie_hash'];
    }

    $hash = generate_secure_token(32);
    $stmt = $pdo->prepare('UPDATE users SET account_cookie_hash = ? WHERE id = ?');
    $stmt->execute([$hash, $userId]);
    return $hash;
}

function find_user_by_account_cookie_hash(PDO $pdo, string $hash): ?array {
    if ($hash === '') {
        return null;
    }

    $stmt = $pdo->prepare('SELECT id, username, email, sibux, tixs, date_join, is_admin, last_online FROM users WHERE account_cookie_hash = ? LIMIT 1');
    $stmt->execute([$hash]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

function restore_session_from_cookie(PDO $pdo): ?array {
    if (!empty($_SESSION['uid']) && is_numeric($_SESSION['uid'])) {
        return null;
    }

    $cookieHash = get_account_cookie_from_request();
    if (!$cookieHash) {
        return null;
    }

    $user = find_user_by_account_cookie_hash($pdo, $cookieHash);
    if (!$user) {
        return null;
    }

    $_SESSION['uid'] = (int)$user['id'];
    $_SESSION['last_activity'] = time();
    return $user;
}

function current_user(PDO $pdo) {
    static $cache = null;
    static $hasLastOnline = null;

    if ($cache !== null) {
        return $cache;
    }

    if (empty($_SESSION['uid']) || !is_numeric($_SESSION['uid'])) {
        restore_session_from_cookie($pdo);
    }

    if (empty($_SESSION['uid']) || !is_numeric($_SESSION['uid'])) {
        $cache = null;
        return null;
    }

    $uid = (int)$_SESSION['uid'];
    $lastActivity = $_SESSION['last_activity'] ?? 0;
    if ($lastActivity > 0 && (time() - $lastActivity) > SESSION_TIMEOUT_SECONDS) {
        logout_user();
        $cache = null;
        return null;
    }

    $_SESSION['last_activity'] = time();

    if ($hasLastOnline === null) {
        $hasLastOnline = (bool)$pdo->query("SHOW COLUMNS FROM users LIKE 'last_online'")->fetch();
    }

    if ($hasLastOnline) {
        $stmt = $pdo->prepare('UPDATE users SET last_online = NOW() WHERE id = :id');
        $stmt->execute([':id' => $uid]);
    }

    $stmt = $pdo->prepare(
        'SELECT id, username, email, sibux, tixs, date_join, ' .
        ($hasLastOnline ? 'last_online' : 'NULL AS last_online') .
        ' FROM users WHERE id = :id LIMIT 1'
    );
    $stmt->execute([':id' => $uid]);

    $cache = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    return $cache;
}

function login_user(PDO $pdo, int $userId): void {
    session_regenerate_id(true);
    $_SESSION['uid'] = $userId;
    $_SESSION['last_activity'] = time();
    $cookieHash = get_or_create_account_cookie_hash($pdo, $userId);
    set_account_cookie($cookieHash);
}

function logout_user(): void {
    $_SESSION = [];

    if (session_status() === PHP_SESSION_ACTIVE) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 3600, $params['path'] ?? '/', $params['domain'] ?? '', $params['secure'] ?? false, $params['httponly'] ?? true);
        session_destroy();
    }

    clear_account_cookie();
}

function render_maintenance_page(string $text = 'Maintenance') {
    if (!headers_sent()) {
        http_response_code(503);
        header('Retry-After: 3600');
        header('Content-Type: text/html; charset=UTF-8');
    }

    echo '<!doctype html>'
        . '<html lang="en"><head><meta charset="utf-8"><title>Maintenance</title>'
        . '<meta name="viewport" content="width=device-width, initial-scale=1">'
        . '<style>html,body{height:100%;margin:0;background:#000;color:#fff;display:flex;align-items:center;justify-content:center;font-family:Arial,sans-serif}.msg{font-size:32px;font-weight:800;letter-spacing:.5px;padding:20px;text-align:center;max-width:90vw}</style>'
        . '</head><body><div class="msg">' . htmlspecialchars($text, ENT_QUOTES, 'UTF-8') . '</div></body></html>';
    exit;
}

function maintenance_gate(PDO $pdo, $user = null, bool $allowAdminBypass = false) {
    $cfg = get_config($pdo);
    $isAdmin = $allowAdminBypass && $user && !empty($user['is_admin']);

    if (!empty($cfg['maintenance_enabled']) && !$isAdmin) {
        $text = !empty($cfg['maintenance_message']) ? $cfg['maintenance_message'] : 'Maintenance';
        render_maintenance_page($text);
    }
}

function get_config($pdo) {
    static $cfg = null;
    if ($cfg !== null) {
        return $cfg;
    }

    $stmt = $pdo->query('SELECT * FROM configuration WHERE id = 1');
    $cfg = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$cfg) {
        $cfg = [
            'maintenance_enabled' => 0,
            'banner_enabled' => 0,
            'banner_message' => '',
        ];
    }

    return $cfg;
}

function get_active_ban(PDO $pdo, int $userId) {
    $stmt = $pdo->prepare('SELECT id, reason, banned_at FROM bans WHERE user_id = ? AND active = 1 ORDER BY id DESC LIMIT 1');
    $stmt->execute([$userId]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

function enforce_not_banned($pdo, $user) {
    if (!$user) {
        return;
    }

    $ban = get_active_ban($pdo, (int)$user['id']);
    if (!$ban) {
        return;
    }

    $isAdmin = !empty($user['is_admin']);
    header('Content-Type: text/html; charset=utf-8');
    header('X-Content-Type-Options: nosniff');
    http_response_code(403);
    $reason = htmlspecialchars($ban['reason'], ENT_QUOTES, 'UTF-8');
    $date = htmlspecialchars(date('M j, Y H:i', strtotime($ban['banned_at'])), ENT_QUOTES, 'UTF-8');

    echo '<!doctype html><html><head><meta charset="utf-8"><title>Banned</title><meta name="viewport" content="width=device-width, initial-scale=1">'
        . '<style>html,body{margin:0;height:100%;background:#000;color:#fff;font-family:system-ui,-apple-system,Segoe UI,Roboto,sans-serif}.wrap{min-height:100%;display:flex;align-items:center;justify-content:center;padding:24px}.card{max-width:560px;width:100%;background:#0d0e12;border:1px solid #2a2d36;border-radius:12px;padding:20px}h1{margin:0 0 10px;font-size:28px}.meta{opacity:.85;margin-top:6px}.btn{margin-top:14px;background:#374151;color:#fff;border:none;border-radius:10px;padding:10px 14px;cursor:pointer}</style></head><body><div class="wrap"><div class="card"><h1>Account banned</h1><div>Reason: ' . $reason . '</div><div class="meta">Date: ' . $date . '</div>';

    if ($isAdmin) {
        echo '<form method="post" action="/admin" style="margin-top:12px"><input type="hidden" name="action" value="unban"><input type="hidden" name="username" value="' . htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8') . '"><button class="btn" type="submit">Unban</button></form>';
    }

    echo '</div></div></body></html>';
    exit;
}

