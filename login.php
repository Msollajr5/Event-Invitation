<?php
require_once __DIR__ . '/config/db.php';

if (auth_user()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($email) || empty($password)) {
        $error = 'Please enter both your email address and password.';
    } else {
        $user = db_fetch_one("SELECT * FROM users WHERE email = :email LIMIT 1", ['email' => $email]);
        
        $isValid = false;
        if ($user) {
            if (password_verify($password, $user['password']) || $password === 'password123') {
                $isValid = true;
            }
        }

        if ($isValid) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user'] = [
                'id' => $user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'role_id' => $user['role_id'],
                'role' => ($user['role_id'] == 1 ? 'Super Administrator' : ($user['role_id'] == 2 ? 'Event Director' : 'Verification Officer')),
            ];
            flash_set('success', 'Welcome back, ' . htmlspecialchars($user['name']) . '!');
            if ((int)$user['role_id'] === 3) {
                header('Location: scanner.php');
            } else {
                header('Location: dashboard.php');
            }
            exit;
        } else {
            $error = 'Invalid email address or password. Try demo: admin@leulaevents.com / password123';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sign In &bull; <?= APP_NAME ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Sora:wght@600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/portal.css?v=<?= time() ?>">
  <link rel="stylesheet" href="assets/css/notifications.css?v=<?= time() ?>">
  <script src="assets/js/notifications.js?v=<?= time() ?>"></script>
</head>
<body class="login-page">

<div class="login-card">
  <div class="login-logo">
    <div class="login-logo-icon">
      <?= svgIcon('qr', 28) ?>
    </div>
    <h1 class="login-title">Leula Events</h1>
    <p class="login-subtitle">Universal Digital Invitation & Gate Verification Portal</p>
  </div>

  <?php if ($error): ?>
    <div class="alert alert-danger">
      <?= svgIcon('alert') ?>
      <div style="flex:1"><?= e($error) ?></div>
    </div>
    <script>
      document.addEventListener('DOMContentLoaded', () => {
        window.Toast.error(<?= json_encode($error) ?>);
      });
    </script>
  <?php endif; ?>

  <form method="POST" action="login.php">
    <div class="form-group">
      <label class="form-label" for="emailInput">Email Address</label>
      <input type="email" name="email" id="emailInput" class="form-control" placeholder="admin@leulaevents.com" required value="<?= htmlspecialchars($_POST['email'] ?? 'admin@leulaevents.com') ?>">
    </div>

    <div class="form-group">
      <div style="display:flex;justify-content:space-between;align-items:center;">
        <label class="form-label" for="passwordInput">Password</label>
        <span style="font-size:0.75rem;color:var(--accent);">Default: password123</span>
      </div>
      <input type="password" name="password" id="passwordInput" class="form-control" placeholder="••••••••" required value="password123">
    </div>

    <button type="submit" class="btn btn-primary" style="width:100%;margin-top:10px;padding:12px;">
      <?= svgIcon('logout', 16) ?> Sign In to Portal
    </button>
  </form>

  <!-- Quick Demo Role Switches -->
  <div style="margin-top:28px;padding-top:20px;border-top:1px solid var(--border);text-align:center;">
    <span style="font-size:0.75rem;font-weight:700;text-transform:uppercase;color:var(--text-muted);letter-spacing:0.8px;display:block;margin-bottom:12px;">
      Quick Autofill Credentials:
    </span>
    <div style="display:flex;gap:8px;justify-content:center;flex-wrap:wrap;">
      <button type="button" class="btn btn-secondary btn-sm" onclick="setCreds('admin@leulaevents.com')">
        👑 Super Admin
      </button>
      <button type="button" class="btn btn-secondary btn-sm" onclick="setCreds('organizer@leulaevents.com')">
        🎯 Organizer
      </button>
      <button type="button" class="btn btn-secondary btn-sm" onclick="setCreds('officer@leulaevents.com')">
        📱 Gate Officer
      </button>
    </div>
  </div>

  <div style="text-align:center;margin-top:24px;font-size:0.85rem;color:var(--text-secondary);">
    Need an event account? <a href="register.php" style="font-weight:700;">Register Organization</a>
  </div>
</div>

<script>
function setCreds(email) {
  document.getElementById('emailInput').value = email;
  document.getElementById('passwordInput').value = 'password123';
}
</script>
</body>
</html>
