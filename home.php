<?php
// home.php
require_once __DIR__ . '/common.php';

$user = current_user($pdo);
maintenance_gate($pdo, $user, false);

if (!$user) {
  redirect('login');
}

// Ban check
$stmt = $pdo->prepare("SELECT reason FROM bans WHERE user_id = ? AND active = 1 LIMIT 1");
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

// Daily SIBUX Grant Logic
$today = date('Y-m-d');
$joinDate = substr($user['date_join'], 0, 10);
$lastAward = $user['last_sibux_award_on'] ?? $joinDate;

// If never awarded or date invalid
if ($lastAward === '0000-00-00') {
  $stmt = $pdo->prepare('UPDATE users SET last_sibux_award_on = ? WHERE id = ?');
  $stmt->execute([$joinDate, $user['id']]);
  $user['last_sibux_award_on'] = $joinDate;
}

$eligibleFrom = date('Y-m-d', strtotime("$joinDate +1 day"));
if ($today > $lastAward && $today >= $eligibleFrom) {
  $stmt = $pdo->prepare('UPDATE users SET sibux = sibux + 10, last_sibux_award_on = ? WHERE id = ?');
  $stmt->execute([$today, $user['id']]);
  $user['sibux'] += 10;
  $user['last_sibux_award_on'] = $today;
}

// Recently joined users
$newUsersStmt = $pdo->query("
  SELECT username
  FROM users
  WHERE DATE(date_join) >= (CURDATE() - INTERVAL 1 DAY)
  ORDER BY date_join DESC
  LIMIT 50
");
$newUsers = $newUsersStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Home · SKIBLOX</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="/styles.css" rel="stylesheet">
  <link href="//fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,600,700" rel="stylesheet">
</head>
<body>
  <div class="page">
    <h1>Welcome, <?php echo htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8'); ?></h1>

    <div class="section">
      <h2>New Users</h2>
      <div class="hlist">
        <?php if (empty($newUsers)): ?>
          <span class="small">No new users yet.</span>
        <?php else: ?>
          <?php foreach ($newUsers as $nu): ?>
            <a class="pill" href="/profile/<?php echo rawurlencode($nu['username']); ?>">
              <?php echo htmlspecialchars($nu['username'], ENT_QUOTES, 'UTF-8'); ?>
            </a>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>
</body>
</html>