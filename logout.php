<?php
// logout.php
declare(strict_types=1);
if (session_status() === PHP_SESSION_NONE) {
    session_name('SKIBLOXSESSID');
    session_start();
}
$_SESSION = [];
setcookie(session_name(), '', time() - 3600, '/');
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 3600, $params['path'] ?? '/', $params['domain'] ?? '', $params['secure'] ?? false, $params['httponly'] ?? true);
}
session_destroy();
header('Location: /login');
exit;
