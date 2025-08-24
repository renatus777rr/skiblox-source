<?php
declare(strict_types=1);
require_once __DIR__ . '/common.php';

$user = current_user($pdo);
maintenance_gate($pdo, $user, false);

if (!$user) {
    redirect('login');
}

// Ban check
if (!empty($user)) {
    $stmt = $pdo->prepare('SELECT reason FROM bans WHERE user_id = ? AND active = 1 LIMIT 1');
    $stmt->execute([$user['id']]);
    $ban = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($ban) {
        echo '<div style="
            background-color:#ff4f4f;
            color:#fff;
            font-weight:bold;
            text-align:center;
            padding:10px;
            border-bottom:2px solid #b91c1c;
            font-family:Arial,sans-serif;
        ">
            Your account is currently banned. Reason: ' . htmlspecialchars($ban['reason'], ENT_QUOTES, 'UTF-8') . '
        </div>';
    }
}

enforce_not_banned($pdo, $user);
include 'topbar.php';

$cfg = get_config($pdo);
if (!empty($cfg['banner_enabled']) && !empty($cfg['banner_message'])) {
    echo '<div style="
        background-color:#ff9900;
        color:#ffffff;
        font-weight:600;
        text-align:center;
        padding:8px 0;
        font-family:Arial, sans-serif;
        border-bottom:2px solid #e67e22;
    ">' . htmlspecialchars($cfg['banner_message'], ENT_QUOTES, 'UTF-8') . '</div>';
}

// Escape helper
function h(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

// Format helpers
function fmt_dt(?string $v): string {
    if (!$v) return '—';
    return date('M j, Y H:i', strtotime($v));
}
function fmt_num($n): string {
    return number_format((int)$n, 0, '.', ' ');
}

// Accept ?u=username
$username = isset($_GET['u']) ? trim($_GET['u']) : '';
if ($username === '') {
    http_response_code(400);
    exit('Missing user parameter.');
}

$stmt = $pdo->prepare('SELECT id, username, date_join, sibux, tixs, last_online FROM users WHERE username = :u LIMIT 1');
$stmt->execute([':u' => $username]);
$profile = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$profile) {
    http_response_code(404);
    exit('User not found.');
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title><?= h($profile['username']) ?> · Profile · SKIBLOX</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="/styles.css" rel="stylesheet">
  <style>
    body { background:#000; color:#fff; margin:0; }
    .page {
      max-width: 800px;
      margin: 40px auto;
      padding: 0 16px;
      text-align: center;
    }
    .name {
      font-size: 32px;
      font-weight: 800;
      margin-bottom: 14px;
    }
    .meta {
      display: inline-grid;
      gap: 8px;
      text-align: left;
      margin-top: 8px;
      padding: 14px 18px;
      border-radius: 10px;
      background: rgba(255,255,255,0.06);
      min-width: 300px;
    }
    .meta .row { display: grid; grid-template-columns: 140px 1fr; gap: 8px; }
    .label { opacity: .8; }
    .val { font-weight: 600; }
  </style>
</head>
<body>

  <div class="page">
    <div class="name"><?= h($profile['username']) ?></div>
    <div class="meta">
      <div class="row">
        <div class="label">Date of join</div>
        <div class="val"><?= fmt_dt($profile['date_join']) ?></div>
      </div>
      <div class="row">
        <div class="label">Sibux</div>
        <div class="val"><?= fmt_num($profile['sibux']) ?></div>
      </div>
      <div class="row">
        <div class="label">Tixs</div>
        <div class="val"><?= fmt_num($profile['tixs']) ?></div>
      </div>
      <div class="row">
        <div class="label">Last online</div>
        <div class="val"><?= fmt_dt($profile['last_online'] ?? '') ?></div>
      </div>
    </div>
  </div>
</body>
</html>