<?php
// chat.php
require_once __DIR__ . '/common.php';

$user = current_user($pdo);
maintenance_gate($pdo, $user, false);
enforce_not_banned($pdo, $user);
if (!$user) {
  redirect('login');
}

// Flash message setup
if (session_status() !== PHP_SESSION_ACTIVE) { @session_start(); }
$flash = $_SESSION['flash'] ?? '';
unset($_SESSION['flash']);
$err = '';

/**
 * Save uploaded chat file (image or non-image) to /uploads/chat/YYYY/MM/randomname.ext
 * Returns an array [filePath, fileName, isImage] where:
 *  - filePath: public relative URL (/uploads/...), or null if no file
 *  - fileName: sanitized original filename (for display), or null if no file
 *  - isImage: 1 if image (jpg/png/gif/webp), 0 otherwise
 */
function save_chat_file(array $file, string &$err): array {
  if (empty($file) || !isset($file['error'])) return [null, null, 0];

  if ($file['error'] === UPLOAD_ERR_NO_FILE) return [null, null, 0];

  if ($file['error'] !== UPLOAD_ERR_OK) {
    $err = 'File upload failed. Please try again.';
    return [null, null, 0];
  }

  // Size limit: 5 MB
  $maxBytes = 5 * 1024 * 1024;
  if ($file['size'] > $maxBytes) {
    $err = 'File too large (max 5 MB).';
    return [null, null, 0];
  }

  // Detect MIME
  $finfo = finfo_open(FILEINFO_MIME_TYPE);
  $mime = finfo_file($finfo, $file['tmp_name']);
  finfo_close($finfo);

  // Image allowlist
  $imageExtByMime = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/gif'  => 'gif',
    'image/webp' => 'webp',
  ];

  $isImage = isset($imageExtByMime[$mime]);
  if ($isImage) {
    // Double-check it’s an image
    if (!@getimagesize($file['tmp_name'])) {
      $isImage = false; // fall back to generic file if probe fails
    }
  }

  // Build storage dir: /uploads/chat/YYYY/MM
  $y  = date('Y');
  $m  = date('m');
  $relDir = "/uploads/chat/$y/$m";
  $absDir = __DIR__ . $relDir;

  if (!is_dir($absDir)) {
    if (!@mkdir($absDir, 0775, true)) {
      $err = 'Server cannot create upload directory.';
      return [null, null, 0];
    }
  }

  // Random basename
  try {
    $rand = bin2hex(random_bytes(16));
  } catch (Throwable $e) {
    $rand = uniqid('file_', true);
  }

  // Decide extension
  $ext = null;
  if ($isImage) {
    $ext = $imageExtByMime[$mime];
  } else {
    $origExt = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
    // Keep a conservative extension for non-images
    if ($origExt && preg_match('/^[a-z0-9]{1,8}$/', $origExt)) {
      $ext = $origExt;
    } else {
      $ext = 'bin';
    }
  }

  // Sanitize display filename
  $origName = (string)($file['name'] ?? 'file');
  $origName = str_replace(["\0", "\r", "\n"], '', $origName);
  $origName = preg_replace('/[^\w\s.\-()+]/u', '_', $origName);
  if ($origName === '' || $origName === '.' || $origName === '..') {
    $origName = 'file.' . $ext;
  }
  if (mb_strlen($origName) > 80) {
    // keep start and extension
    $base = pathinfo($origName, PATHINFO_FILENAME);
    $base = mb_substr($base, 0, 60);
    $origName = $base . '.' . $ext;
  }

  $filename = $rand . '.' . $ext;
  $absPath = $absDir . DIRECTORY_SEPARATOR . $filename;
  $relPath = $relDir . '/' . $filename;

  if (!@move_uploaded_file($file['tmp_name'], $absPath)) {
    $err = 'Failed to save uploaded file.';
    return [null, null, 0];
  }

  @chmod($absPath, 0644);

  return [$relPath, $origName, $isImage ? 1 : 0];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $msg = isset($_POST['message']) ? trim($_POST['message']) : '';

  if ($msg !== '' && strlen($msg) > 500) {
    $err = 'Message must be between 1 and 500 characters.';
  }

  // Censor words by replacing with ###
  if ($err === '') {
    $filteredWords = ['fart', 'noob', 'ugly', 'skid', 'nigga', 'fuck', 'bitch', 'retard', 'faggot'];
    foreach ($filteredWords as $badWord) {
      $pattern = '/' . preg_quote($badWord, '/') . '/i';
      $msg = preg_replace_callback($pattern, function($m) {
        return str_repeat('#', strlen($m[0]));
      }, $msg);
    }
  }

  // Handle file upload (optional)
  $filePath = null;
  $fileName = null;
  $isImage  = 0;

  if ($err === '') {
    list($filePath, $fileName, $isImage) = save_chat_file(isset($_FILES['file']) ? $_FILES['file'] : [], $err);
  }

  // Require at least text or a file
  if ($err === '' && $msg === '' && !$filePath) {
    $err = 'Please enter a message or attach a file.';
  }

  if ($err === '') {
    try {
      $pdo->beginTransaction();

      // Insert chat row with optional file fields
      $stmt = $pdo->prepare('
        INSERT INTO chat_messages (user_id, message, file_path, file_name, is_image)
        VALUES (?, ?, ?, ?, ?)
      ');
      $stmt->execute([
        $user['id'],
        $msg !== '' ? $msg : null,
        $filePath,
        $fileName,
        $isImage
      ]);

      // Reward logic (every 3 successful posts)
      $stmt = $pdo->prepare('SELECT chat_msg_mod3 FROM users WHERE id = ? FOR UPDATE');
      $stmt->execute([$user['id']]);
      $u = $stmt->fetch(PDO::FETCH_ASSOC);
      if (!$u) throw new RuntimeException('User not found for reward update.');
      $mod = (int)$u['chat_msg_mod3'];

      if ($mod >= 2) {
        $stmt = $pdo->prepare('UPDATE users SET sibux = sibux + 1, chat_msg_mod3 = 0 WHERE id = ?');
        $stmt->execute([$user['id']]);
        $_SESSION['flash'] = '+1 Sibux earned!';
      } else {
        $stmt = $pdo->prepare('UPDATE users SET chat_msg_mod3 = chat_msg_mod3 + 1 WHERE id = ?');
        $stmt->execute([$user['id']]);
      }

      $pdo->commit();
      header('Location: /chat');
      exit;
    } catch (Throwable $e) {
      if ($pdo->inTransaction()) $pdo->rollBack();
      $err = 'Unexpected error. Please try again.';
    }
  }
}

function h($s){ return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Chat · SKIBLOX</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="/styles.css" rel="stylesheet">
  <link href="//fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,600,700" rel="stylesheet">
  <style>
    body { background:#000; color:#fff; margin:0; font-family:'Source Sans Pro',sans-serif; }
    .page { max-width: 800px; margin: 24px auto 40px; padding: 0 16px; }
    .title { text-align:center; font-size:32px; font-weight:800; margin: 16px 0 20px; }
    .chat-box {
      display: flex;
      flex-direction: column;
      gap: 10px;
      background: rgba(255,255,255,0.06);
      border-radius: 12px;
      padding: 14px;
      max-height: 60vh;
      overflow-y: auto;
    }
    .msg { padding: 6px 8px; border-bottom: 1px solid rgba(255,255,255,0.07); }
    .msg:last-child { border-bottom: none; }
    .msg .meta { opacity:.8; font-size: 12px; margin-left: 6px; }
    .chat-image {
      display: block;
      max-width: 100%;
      max-height: 320px;
      width: auto;
      height: auto;
      border-radius: 10px;
      margin-top: 6px;
      border: 1px solid rgba(255,255,255,0.1);
      background: #0d0e12;
    }
    .chat-download {
      display: inline-block;
      margin-top: 6px;
      color: #8cc4ff;
      text-decoration: underline;
      word-break: break-all;
    }
    .composer {
      margin-top: 14px;
      display: grid;
      grid-template-columns: 1fr auto;
      gap: 10px;
      align-items: center;
    }
    .composer input[type="text"] {
      width: 100%;
      padding: 12px 14px;
      background: #0d0e12;
      color: #fff;
      border: 1px solid #2a2d36;
      border-radius: 10px;
      outline: none;
    }
    .composer .right {
      display: flex; gap: 10px; align-items: center;
    }
    .composer input[type="file"] {
      color: #ddd; font-size: 12px;
      max-width: 280px;
    }
    .composer button.btn { padding: 12px 16px; }
    .error { color: #ff5d5d; font-weight: 600; margin: 10px 0; text-align:center; }
    .flash { color: #74ff8f; font-weight: 700; margin: 10px 0; text-align:center; }
  </style>
  <script>
    function fetchMessages() {
      fetch('/get_chat_messages.php')
        .then(res => res.json())
        .then(data => {
          const box = document.querySelector('.chat-box');
          box.innerHTML = '';

          for (const msg of data) {
            const div = document.createElement('div');
            div.className = 'msg';

            const name = escapeHtml(msg.username || 'user');
            const text = msg.message ? `'${escapeHtml(msg.message)}'` : '';
            const meta = ` <span class="meta">(${formatDate(msg.created_at)})</span>`;

            const head = document.createElement('div');
            head.innerHTML = `(<strong>${name}</strong>)${text ? ': ' + text : ''}${meta}`;
            div.appendChild(head);

            // Attachment rendering
            if (msg.file_path) {
              if (Number(msg.is_image) === 1) {
                const img = document.createElement('img');
                img.className = 'chat-image';
                img.src = msg.file_path; // relative URL like /uploads/chat/...
                img.alt = msg.file_name || 'image';
                div.appendChild(img);
              } else {
                const link = document.createElement('a');
                link.className = 'chat-download';
                link.href = msg.file_path;
                const fname = msg.file_name ? escapeHtml(msg.file_name) : 'file';
                link.textContent = `(${fname}) Download`;
                link.setAttribute('download', msg.file_name || '');
                link.rel = 'noopener';
                link.target = '_blank';
                div.appendChild(link);
              }
            }

            box.appendChild(div);
          }
        })
        .catch(() => {});
    }

    function escapeHtml(text) {
      const div = document.createElement('div');
      div.textContent = text == null ? '' : String(text);
      return div.innerHTML;
    }

    function formatDate(iso) {
      const d = new Date((iso || '').replace(' ', 'T') + 'Z');
      if (isNaN(d.getTime())) return iso || '';
      return d.toLocaleString();
    }

    document.addEventListener('DOMContentLoaded', () => {
      fetchMessages();
      setInterval(fetchMessages, 3000);
    });
  </script>
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
    <div class="title">Chat</div>

    <?php if ($flash): ?>
      <div class="flash"><?php echo h($flash); ?></div>
    <?php endif; ?>

    <?php if ($err): ?>
      <div class="error"><?php echo h($err); ?></div>
    <?php endif; ?>

    <div class="chat-box"></div>

    <!-- enctype is required for file uploads -->
    <form class="composer" method="post" enctype="multipart/form-data" autocomplete="off">
      <input type="text" name="message" placeholder="Type a message..." maxlength="500">
      <div class="right">
        <input type="file" name="file">
        <button class="btn" type="submit">Send</button>
      </div>
    </form>
  </div>
</body>
</html>