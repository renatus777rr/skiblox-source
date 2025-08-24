<?php
// users.php
declare(strict_types=1);
require_once __DIR__ . '/common.php';

$user = current_user($pdo); // Ensure $user is always initialized

// Check if user is actively banned
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

maintenance_gate($pdo, $user, false);
enforce_not_banned($pdo, $user);

if (!$user) {
    redirect('login');
}

$cfg = get_config($pdo);

// Pull all users; tweak ORDER BY as needed
$stmt = $pdo->query('SELECT id, username FROM users ORDER BY username ASC');
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Escape helpers
function u(string $s): string {
    return urlencode($s);
}
function h(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Users · SKIBLOX</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="/styles.css" rel="stylesheet">
  <style>
    body { background:#000; color:#fff; margin:0; }
    .wrap {
      max-width: 960px;
      margin: 24px auto;
      padding: 0 16px;
      display: grid;
      grid-template-columns: 1fr;
      gap: 12px;
    }
    .title {
      text-align: left;
      font-size: 28px;
      font-weight: 700;
      margin: 8px 0 6px;
    }
    .list {
      display: grid;
      gap: 10px;
      max-width: 400px;
    }
    a.btn.user {
      display: block;
      width: 100%;
      text-align: left;
      padding: 12px 16px;
      font-weight: 600;
    }
  </style>
</head>
<body>
  <?php include __DIR__ . '/topbar.php'; ?>
  <?php
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
  ?>

  <div class="wrap">
    <div class="title">Users</div>
    <div class="list">
      <?php if (!$users): ?>
        <div style="opacity:.7">No users yet.</div>
      <?php else: ?>
        <?php foreach ($users as $u): ?>
          <a class="btn user" href="/profile.php?u=<?= u($u['username']); ?>">
            <?= h($u['username']); ?>
          </a>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</body>
</html>