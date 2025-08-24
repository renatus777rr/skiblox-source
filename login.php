<?php
declare(strict_types=1);

require_once __DIR__ . '/common.php';

$user = current_user($pdo);
maintenance_gate($pdo, $user, false);

$cfg = get_config($pdo);

$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $err = 'Please enter username and password.';
    } else {
        $stmt = $pdo->prepare('SELECT id, password_hash FROM users WHERE username = ? LIMIT 1');
        $stmt->execute([$username]);
        $foundUser = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$foundUser || !password_verify($password, $foundUser['password_hash'])) {
            $err = 'Invalid credentials.';
        } else {
            $_SESSION['uid'] = (int)$foundUser['id'];
            redirect('home.php');
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Log in · SKIBLOX</title>
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
      <h1>Welcome back!</h1>
      <p>Welcome to SKIBLOX back.</p>
    </div>

    <div class="panel">
      <h2>Log in</h2>

      <?php if ($err !== ''): ?>
        <p class="error"><?= htmlspecialchars($err, ENT_QUOTES, 'UTF-8') ?></p>
      <?php endif; ?>

      <form method="post" class="input" autocomplete="off">
        <input type="text" name="username" placeholder="Username" required
               value="<?= htmlspecialchars($_POST['username'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        <input type="password" name="password" placeholder="Password" required>
        <button class="btn" type="submit">Log-In</button>
      </form>

      <div class="small">
        Don't have account? <a href="signup.php">Register it here.</a>
      </div>
    </div>
  </div>
</div>
</body>
</html>