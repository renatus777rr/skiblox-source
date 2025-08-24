<?php
// signup.php
declare(strict_types=1);

require_once __DIR__ . '/common.php';

$user = current_user($pdo);
maintenance_gate($pdo, $user, false);

$cfg = get_config($pdo);

// Maintenance gate for signups
if (!empty($cfg['maintenance_enabled'])) {
    http_response_code(503);
    echo '<!doctype html><meta charset="utf-8"><title>Maintenance</title><body style="background:#0b0b0c;color:#e9e9f0;font-family:sans-serif;display:grid;place-items:center;height:100vh"><div><h1>Maintenance</h1><p>Signups are temporarily disabled.</p></div></body>';
    exit;
}

$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Basic validations
    if ($username === '' || $email === '' || $password === '') {
        $err = 'Please fill in all fields.';
    } elseif (!preg_match('/^[A-Za-z0-9_\.]{3,32}$/', $username)) {
        $err = 'Username must be 3-32 chars, alphanumeric, underscore, or dot.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $err = 'Invalid email.';
    } else {
        // Uniqueness checks (separate to avoid LIMIT 1 masking the other field)
        $uStmt = $pdo->prepare('SELECT 1 FROM users WHERE username = ? LIMIT 1');
        $uStmt->execute([$username]);
        $usernameTaken = (bool)$uStmt->fetchColumn();

        $eStmt = $pdo->prepare('SELECT 1 FROM users WHERE email = ? LIMIT 1');
        $eStmt->execute([$email]);
        $emailTaken = (bool)$eStmt->fetchColumn();

        if ($usernameTaken) {
            $err = 'Username already taken.';
        } elseif ($emailTaken) {
            $err = 'Email already registered.';
        } else {
            // Create user
            $hash = password_hash($password, PASSWORD_DEFAULT);

            try {
                $ins = $pdo->prepare('
                    INSERT INTO users (username, email, password_hash, sibux, tixs)
                    VALUES (?, ?, ?, 0, 0)
                ');
                $ins->execute([$username, $email, $hash]);

                $_SESSION['uid'] = (int)$pdo->lastInsertId();
                redirect('home.php');
            } catch (PDOException $ex) {
                // Handle race conditions with unique constraints gracefully
                if ($ex->getCode() === '23000') {
                    $err = 'Username or email already in use.';
                } else {
                    $err = 'An unexpected error occurred. Please try again.';
                }
            }
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Sign up · SKIBLOX</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="styles.css" rel="stylesheet">
  <link href="//fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,600,700" rel="stylesheet">
</head>
<body>
  <?php if (!empty($cfg['banner_enabled']) && !empty($cfg['banner_message'])): ?>
    <div style="
        background-color:#ff9900;
        color:#ffffff;
        font-weight:600;
        text-align:center;
        padding:8px 0;
        font-family:Arial, sans-serif;
        border-bottom:2px solid #e67e22;
    ">
      <?= htmlspecialchars($cfg['banner_message'], ENT_QUOTES, 'UTF-8') ?>
    </div>
  <?php endif; ?>

  <div class="center-wrap">
    <div>
      <div class="hero">
        <h1>SKIBLOX, Launcher of old ROBLOX.</h1>
        <p>Hmph, you not OG.</p>
      </div>

      <div class="panel">
        <h2>Sign up</h2>

        <?php if ($err !== ''): ?>
          <p class="error"><?= htmlspecialchars($err, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>

        <form method="post" class="input" autocomplete="off">
          <input type="text" name="username" placeholder="Username" maxlength="32" required
                 value="<?= htmlspecialchars($_POST['username'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
          <input type="email" name="email" placeholder="Email" required
                 value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
          <input type="password" name="password" placeholder="Password" required>
          <button class="btn" type="submit">Create Account</button>
        </form>

        <div class="small">
          Already having an account? <a href="login.php">Log in</a>
        </div>
      </div>
    </div>
  </div>
</body>
</html>