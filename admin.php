<?php
declare(strict_types=1);

require_once __DIR__ . '/common.php';

// Ensure session is active with the expected name
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name('SKIBLOXSESSID');
    session_start();
}

$user = current_user($pdo);
if (!$user) {
    http_response_code(403);
    exit('Forbidden: Not authenticated');
}

// Enforce admin-only access (users.is_admin must be 1)
$stmt = $pdo->prepare('SELECT is_admin FROM users WHERE id = ? LIMIT 1');
$stmt->execute([(int)$user['id']]);
$adminRow = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$adminRow || (int)$adminRow['is_admin'] !== 1) {
    http_response_code(403);
    exit("Forbidden, you don't have admin access");
}

function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

$cfg    = get_config($pdo);
$notice = '';
$error  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'ban') {
        $uname  = trim($_POST['username'] ?? '');
        $reason = trim($_POST['reason'] ?? '');

        if ($uname === '' || $reason === '') {
            $error = 'Username and Reason are required.';
        } elseif (mb_strtolower($uname, 'UTF-8') === 'onion') {
            $error = 'User "onion" cannot be banned.';
        } else {
            $stmt = $pdo->prepare('SELECT id, username FROM users WHERE username = ? LIMIT 1');
            $stmt->execute([$uname]);
            $target = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$target) {
                $error = 'User not found.';
            } else {
                $stmt = $pdo->prepare('SELECT id FROM bans WHERE user_id = ? AND active = 1 LIMIT 1');
                $stmt->execute([(int)$target['id']]);
                $activeBan = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($activeBan) {
                    $error = 'User is already banned.';
                } else {
                    $stmt = $pdo->prepare('INSERT INTO bans (user_id, reason, banned_by, active) VALUES (?, ?, ?, 1)');
                    $stmt->execute([(int)$target['id'], $reason, (int)$user['id']]);
                    $notice = 'User ' . h($target['username']) . ' has been banned.';
                }
            }
        }
    } elseif ($action === 'unban') {
        $uname = trim($_POST['username'] ?? '');

        if ($uname === '') {
            $error = 'Username is required.';
        } else {
            $stmt = $pdo->prepare('SELECT id, username FROM users WHERE username = ? LIMIT 1');
            $stmt->execute([$uname]);
            $target = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$target) {
                $error = 'User not found.';
            } else {
                $stmt = $pdo->prepare('UPDATE bans SET active = 0, unbanned_at = NOW() WHERE user_id = ? AND active = 1');
                $stmt->execute([(int)$target['id']]);
                if ($stmt->rowCount() > 0) {
                    $notice = 'User ' . h($target['username']) . ' has been unbanned.';
                } else {
                    $notice = 'No active ban found for this user.';
                }
            }
        }
    } elseif ($action === 'banner') {
        $msg   = trim($_POST['banner_message'] ?? '');
        $on    = isset($_POST['banner_enabled']) ? 1 : 0;
        $maint = isset($_POST['maintenance_enabled']) ? 1 : 0;

        $stmt = $pdo->prepare('UPDATE configuration SET banner_message = ?, banner_enabled = ?, maintenance_enabled = ?');
        $stmt->execute([$msg, $on, $maint]);

        $cfg['banner_message']      = $msg;
        $cfg['banner_enabled']      = $on;
        $cfg['maintenance_enabled'] = $maint;

        $notice = 'Banner and maintenance status updated.';
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Admin</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    html, body { background:#fff; color:#000; margin:0; font-family: system-ui, -apple-system, Segoe UI, Roboto, sans-serif; }
    .page { max-width: 760px; margin: 40px auto; padding: 0 16px; }
    h1 { font-size: 28px; margin: 0 0 16px; }
    .row { display:flex; gap:12px; align-items:center; flex-wrap:wrap; }
    .card { border:1px solid #e5e7eb; border-radius:12px; padding:16px; margin: 12px 0; }
    .btn { background:#111827; color:#fff; border:none; border-radius:10px; padding:10px 14px; cursor:pointer; }
    .btn.secondary { background:#374151; }
    .btn.danger { background:#b91c1c; }
    .btn.success { background:#15803d; }
    .field { display:flex; flex-direction:column; gap:6px; margin:8px 0; }
    .field input[type="text"] { padding:10px 12px; border:1px solid #d1d5db; border-radius:10px; }
    label { font-weight:600; }
    .notice { color:#065f46; background:#ecfdf5; border:1px solid #a7f3d0; border-radius:10px; padding:10px 12px; margin:12px 0; }
    .error { color:#991b1b; background:#fef2f2; border:1px solid #fecaca; border-radius:10px; padding:10px 12px; margin:12px 0; }
    details > summary { cursor:pointer; font-weight:700; }
  </style>
</head>
<body>
  <div class="page">
    <h1>Admin</h1>

    <?php if ($notice): ?>
      <div class="notice"><?= $notice ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
      <div class="error"><?= h($error) ?></div>
    <?php endif; ?>

    <div class="card">
      <details>
        <summary><span class="btn">Ban user</span></summary>
        <form method="post" style="margin-top:12px;">
          <input type="hidden" name="action" value="ban">
          <div class="field">
            <label>Username</label>
            <input type="text" name="username" required>
          </div>
          <div class="field">
            <label>Reason</label>
            <input type="text" name="reason" maxlength="255" required>
          </div>
          <button class="btn danger" type="submit">Ban</button>
        </form>
      </details>
    </div>

    <div class="card">
      <details>
        <summary><span class="btn secondary">Unban user</span></summary>
        <form method="post" style="margin-top:12px;">
          <input type="hidden" name="action" value="unban">
          <div class="field">
            <label>Username</label>
            <input type="text" name="username" required>
          </div>
          <button class="btn success" type="submit">Unban</button>
        </form>
      </details>
    </div>

    <div class="card">
      <h2 style="margin:0 0 8px;">Set Banner</h2>
      <form method="post">
        <input type="hidden" name="action" value="banner">
        <div class="field">
          <label>Set Message</label>
          <input type="text" name="banner_message" maxlength="255" value="<?= h($cfg['banner_message'] ?? '') ?>">
        </div>
        <div class="row" style="margin:8px 0;">
          <label class="row" style="gap:8px;">
            <input type="checkbox" name="banner_enabled" <?= !empty($cfg['banner_enabled']) ? 'checked' : '' ?>>
            Turn on
          </label>
          <label class="row" style="gap:8px;">
            <input type="checkbox" name="maintenance_enabled" <?= !empty($cfg['maintenance_enabled']) ? 'checked' : '' ?>>
            Maintenance
          </label>
        </div>
        <button class="btn" type="submit">Set</button>
      </form>
    </div>
  </div>
</body>
</html>