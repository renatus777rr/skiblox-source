<?php
declare(strict_types=1);
require_once __DIR__ . '/common.php';

$user = current_user($pdo);
maintenance_gate($pdo, $user, false);

if (!$user) {
    redirect('login.php');
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

function fmt_rel_time(?string $v, string $dbTz = 'UTC', string $displayTz = 'Europe/Minsk'): string {
    if (!$v) return '—';
    try {
        // try parsing as Y-m-d H:i:s in DB timezone
        $dt = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $v, new DateTimeZone($dbTz));
        if ($dt === false) {
            // fallback to generic parse (handles ISO)
            $dt = new DateTimeImmutable($v, new DateTimeZone($dbTz));
        }
    } catch (Exception $e) {
        $ts = strtotime($v);
        if ($ts === false) return '—';
        $dt = (new DateTimeImmutable('@' . $ts))->setTimezone(new DateTimeZone($dbTz));
    }

    $dtLocal = $dt->setTimezone(new DateTimeZone($displayTz));
    $now = new DateTimeImmutable('now', new DateTimeZone($displayTz));
    $diff = max(0, $now->getTimestamp() - $dtLocal->getTimestamp());

    if ($diff <= 60) return 'Online now';
    if ($diff <= 120) return 'Was now';
    if ($diff < 3600) {
        $m = (int)floor($diff / 60);
        return $m . ' minute' . ($m === 1 ? '' : 's') . ' ago';
    }
    if ($diff < 86400) {
        $h = (int)floor($diff / 3600);
        return $h . ' hour' . ($h === 1 ? '' : 's') . ' ago';
    }
    if ($diff < 172800) return 'Yesterday';
    return $dtLocal->format('M j, Y H:i');
}

// Accept ?u=username
// Accept ?u=username or /profile.php/username
$username = '';

// 1) standard query param
if (isset($_GET['u']) && trim((string)$_GET['u']) !== '') {
    $username = trim((string)$_GET['u']);
} else {
    // 2) PATH_INFO (works if PHP is configured to fill it)
    if (!empty($_SERVER['PATH_INFO'])) {
        $p = trim((string)$_SERVER['PATH_INFO'], "/ \t\n\r\0\x0B");
        if ($p !== '') $username = $p;
    }
    // 3) fallback: parse REQUEST_URI for /profile.php/username
    if ($username === '' && !empty($_SERVER['REQUEST_URI'])) {
        $uri = strtok($_SERVER['REQUEST_URI'], '?'); // strip query
        // remove leading / and split
        $parts = explode('/', trim($uri, "/"));
        // find profile.php in segments and take the next segment as username
        foreach ($parts as $i => $seg) {
            if (strcasecmp($seg, 'profile.php') === 0 && isset($parts[$i + 1]) && $parts[$i + 1] !== '') {
                $username = $parts[$i + 1];
                break;
            }
        }
    }
}

// final validation
$username = trim((string)$username);
if ($username === '') {
    http_response_code(400);
    exit('Missing user parameter.');
}


// Load profile including description (assumes `description` column exists)
$stmt = $pdo->prepare('SELECT id, username, date_join, sibux, tixs, last_online, COALESCE(description, "I am player of SKIBLOX") AS description FROM users WHERE username = :u LIMIT 1');
$stmt->execute([':u' => $username]);
$profile = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$profile) {
    http_response_code(404);
    exit('User not found.');
}

$isOwner = ($user && (int)$user['id'] === (int)$profile['id']);

// Handle AJAX update for description (must run before any output)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_description') {
    if (headers_sent()) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => 'Headers already sent']);
        exit;
    }

    header('Content-Type: application/json; charset=utf-8');

    $uname = $username;
    if ($uname === '') {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Missing user parameter']);
        exit;
    }

    $stmt = $pdo->prepare('SELECT id FROM users WHERE username = :u LIMIT 1');
    $stmt->execute([':u' => $uname]);
    $profileRow = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$profileRow) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'error' => 'User not found']);
        exit;
    }

    if (!($user && (int)$user['id'] === (int)$profileRow['id'])) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'error' => 'Forbidden']);
        exit;
    }

    $desc = isset($_POST['description']) ? (string)$_POST['description'] : '';
    if (mb_strlen($desc) > 1000) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Description too long (max 1000 chars)']);
        exit;
    }
    $desc = str_replace(["\0","\r"], '', $desc);

    try {
        $stmt = $pdo->prepare('UPDATE users SET description = :desc WHERE id = :id');
        $stmt->execute([':desc' => $desc, ':id' => $profileRow['id']]);
        echo json_encode(['ok' => true, 'description' => $desc]);
        exit;
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => 'Failed to update']);
        exit;
    }
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
  display: block;
  margin: 18px auto 0;
  text-align: left;
  padding: 14px 18px;
  border-radius: 10px;
  background: rgba(255,255,255,0.06);
  min-width: 300px;
  max-width: 680px;    /* match desc-frame max width */
}

.desc-frame {
  margin: 18px auto 0;
  text-align: left;
  padding: 12px 14px;
  border-radius: 10px;
  background: rgba(13,14,18,0.8);
  min-width: 300px;
  max-width: 680px;
  border: 1px solid rgba(255,255,255,0.04);
  color: #e6eef8;
  display: block;
}
      /* separator under user info */
.info-sep {
  border: none;
  height: 0.1px;
  background: rgba(255,255,255,0.06);
  margin: 18px auto;
  width: 680px;        /* match meta/desc max-width */
  max-width: calc(100% - 32px);
  border-radius: 1px;
}

/* keep separator aligned with centered blocks on small screens */
@media (max-width: 720px) {
  .info-sep { width: 100%; max-width: 680px; margin-left: auto; margin-right: auto; }
}


    .desc-header { display:flex; justify-content:space-between; align-items:center; gap:8px; }
    .desc-title { font-weight:700; color:#cfe9ff; }
    .desc-edit-link { color:#6fb0ff; cursor:pointer; font-weight:600; text-decoration:underline; }
    .desc-text { margin-top:8px; white-space:pre-wrap; color:#e6eef8; }
    .desc-form { margin-top:8px; display:flex; flex-direction:column; gap:8px; }
    .desc-input { width:100%; padding:8px; border-radius:8px; border:1px solid #2a2d36; background:#0d0e12; color:#fff; min-height:80px; resize:vertical; }
    .btn { background:#1b74ff; color:#fff; border:none; padding:8px 12px; border-radius:8px; cursor:pointer; font-weight:700; }
    .btn.secondary { background:#2a2d36; color:#ddd; }
    .notice { margin-top:8px; color:#9be6a6; font-weight:600; display:none; }
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
      <hr class="info-sep">
      <div class="row">
        <div class="label">Sibux</div>
        <div class="val"><?= fmt_num($profile['sibux']) ?></div>
      </div>
      <hr class="info-sep">
      <div class="row">
        <div class="label">Tixs</div>
        <div class="val"><?= fmt_num($profile['tixs']) ?></div>
      </div>
      <hr class="info-sep">
      <div class="row">
        <div class="label">Last online</div>
        <div class="val"><?= h(fmt_rel_time($profile['last_online'] ?? '')) ?></div>
      </div>
    </div>
    <div class="desc-frame" id="descFrame">
      <div class="desc-header">
        <div class="desc-title">Description</div>
        <?php if ($isOwner): ?>
          <div id="editTrigger" class="desc-edit-link">Edit</div>
        <?php endif; ?>
      </div>

      <div id="descView" class="desc-text"><?= h($profile['description']) ?></div>

      <?php if ($isOwner): ?>
        <div id="descForm" style="display:none;" class="desc-form">
          <textarea id="descInput" class="desc-input" maxlength="1000"><?= h($profile['description']) ?></textarea>
          <div style="display:flex; gap:8px;">
            <button id="saveBtn" class="btn">Update</button>
            <button id="cancelBtn" class="btn secondary" type="button">Cancel</button>
          </div>
          <div id="descMsg" class="notice"></div>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <script>
    (function(){
      const isOwner = <?= $isOwner ? 'true' : 'false' ?>;
      if (!isOwner) return;

      const editTrigger = document.getElementById('editTrigger');
      const descView = document.getElementById('descView');
      const descForm = document.getElementById('descForm');
      const descInput = document.getElementById('descInput');
      const saveBtn = document.getElementById('saveBtn');
      const cancelBtn = document.getElementById('cancelBtn');
      const descMsg = document.getElementById('descMsg');

      function showForm() {
        descView.style.display = 'none';
        descForm.style.display = 'block';
        descMsg.style.display = 'none';
        descInput.focus();
      }
      function hideForm() {
        descForm.style.display = 'none';
        descView.style.display = 'block';
        descMsg.style.display = 'none';
      }

      editTrigger.addEventListener('click', showForm);
      cancelBtn.addEventListener('click', hideForm);

      saveBtn.addEventListener('click', function(){
        const val = descInput.value;
        saveBtn.disabled = true;
        descMsg.style.display = 'none';

        const fd = new FormData();
        fd.append('action', 'update_description');
        fd.append('description', val);

        fetch(location.pathname + '?u=' + encodeURIComponent('<?= rawurlencode($profile['username']) ?>'), {
          method: 'POST',
          body: fd,
          credentials: 'same-origin',
          headers: { 'X-Requested-With': 'XMLHttpRequest' }
        }).then(async res => {
          saveBtn.disabled = false;
          const text = await res.text();
          let json = null;
          try { json = JSON.parse(text); } catch(e) { /* not JSON */ }
          if (!res.ok) {
            descMsg.style.display = 'block';
            descMsg.textContent = (json && json.error) ? json.error : ('Server error: ' + res.status + ' ' + text.slice(0,200));
            return;
          }
          if (!json || !json.ok) {
            descMsg.style.display = 'block';
            descMsg.textContent = (json && json.error) ? json.error : 'Update failed.';
            return;
          }
          descView.textContent = json.description || '';
          hideForm();
          descMsg.style.display = 'block';
          descMsg.textContent = 'Updated.';
          setTimeout(()=> descMsg.style.display = 'none', 2500);
        }).catch(err => {
          saveBtn.disabled = false;
          descMsg.style.display = 'block';
          descMsg.textContent = 'Network error: ' + (err && err.message ? err.message : 'unknown');
        });
      });
    })();
  </script>
</body>
</html>
