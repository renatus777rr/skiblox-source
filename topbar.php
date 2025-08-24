<?php
// topbar.php
declare(strict_types=1);

if (!isset($user)) {
    $user = current_user($pdo);
}

// Safely escape user display values
$username = htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8');
$sibux    = (int)$user['sibux'];
$tixs     = (int)$user['tixs'];
?>
<div class="topbar">
  <div class="topbar-left">
    <img src="/assets/revival.png" alt="Revival Logo" class="topbar-logo">
    <a href="/home" class="topbar-link">Home</a>
    <a href="/users" class="topbar-link">Profiles</a>
    <a href="/download" class="topbar-link">Download</a>
    <a href="/chat" class="topbar-link">Chat</a>
  </div>
  <div class="topbar-right">
    <span><?= $sibux ?> ROBUX/Sibux |</span>
    <span><?= $tixs ?> Tixs |</span>
    <a href="/logout" class="topbar-link">Log out</a>
  </div>
</div>