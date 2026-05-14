<?php
// download.php
declare(strict_types=1);
require_once __DIR__ . '/common.php';

$user = current_user($pdo);
maintenance_gate($pdo, $user, false);
enforce_not_banned($pdo, $user);
if (!$user) {
  redirect('login');
}


$cfg = get_config($pdo);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Downloads · SKIBLOX</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="/styles.css" rel="stylesheet">
  <link href="//fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,600,700" rel="stylesheet">
  <style>
    body {
      background-color: #000;
      margin: 0;
      font-family: 'Source Sans Pro', sans-serif;
      color: white;
    }
    .page {
      max-width: 800px;
      margin: 40px auto;
      padding: 0 16px;
      text-align: center;
    }
    .page h1 {
      margin: 12px 0 22px;
      font-size: 32px;
      font-weight: 700;
    }
    .btn-wrap {
      display: grid;
      gap: 14px;
      justify-items: center;
    }
    /* Ensure anchor buttons render nicely */
    a.btn {
      display: inline-block;
      min-width: 280px;
      text-align: center;
    }
  </style>
</head>
<body>
  <?php include __DIR__ . '/topbar.php'; ?>
  <?php
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
  ?>


  <div class="page">
    <h1>Downloads</h1>

    <div class="btn-wrap">
      <a class="btn" href="https://www.mediafire.com/file/dc6gb7hkovthrlk/SkibloxLaunchernew.zip/file" target="_blank" rel="noopener">
        Download SKIBLOX Launcher
      </a>

      <a class="btn" href="https://www.mediafire.com/file/wvde59nulqz8wib/Working_games.zip/file" target="_blank" rel="noopener">
        Download Games Pack
      </a>
    </div>
  </div>
</body>
</html>
