<?php
require_once __DIR__ . '/config/db.php';

if (auth_user()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $org = trim($_POST['organization'] ?? 'Primary Organization');

    if (empty($name) || empty($email) || empty($password)) {
        $error = 'Please fill in all required fields.';
    } else {
        $existing = db_fetch_one("SELECT id FROM users WHERE email = :email LIMIT 1", ['email' => $email]);
        if ($existing) {
            $error = 'An account with this email already exists.';
        } else {
            $userId = db_insert('users', [
                'name' => $name,
                'email' => $email,
                'password' => password_hash($password, PASSWORD_BCRYPT),
                'role_id' => 2
            ]);

            $_SESSION['user_id'] = $userId;
            $_SESSION['user'] = [
                'id' => $userId,
                'name' => $name,
                'email' => $email,
                'role_id' => 2,
                'role' => 'Event Director'
            ];
            flash_set('success', 'Welcome to Leula Events, ' . htmlspecialchars($name) . '! Your organization account has been created.');
            header('Location: dashboard.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Create Organization Account &bull; <?= APP_NAME ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Sora:wght@600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/portal.css?v=<?= time() ?>">
</head>
<body class="login-page">

<div class="login-card" style="max-width:480px;">
  <div class="login-logo">
    <div class="login-logo-icon">
      <?= svgIcon('building', 28) ?>
    </div>
    <h1 class="login-title">Register Organization</h1>
    <p class="login-subtitle">Setup your team access and digital invitation portal</p>
  </div>

  <?php if ($error): ?>
    <div class="alert alert-danger">
      <?= svgIcon('alert') ?>
      <div style="flex:1"><?= e($error) ?></div>
    </div>
  <?php endif; ?>

  <form method="POST" action="register.php">
    <div class="form-group">
      <label class="form-label">Full Name</label>
      <input type="text" name="name" class="form-control" placeholder="Sarah Mwangi" required value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
    </div>

    <div class="form-group">
      <label class="form-label">Organization Name</label>
      <input type="text" name="organization" class="form-control" placeholder="Innovations Africa Ltd" required value="<?= htmlspecialchars($_POST['organization'] ?? '') ?>">
    </div>

    <div class="form-group">
      <label class="form-label">Work Email</label>
      <input type="email" name="email" class="form-control" placeholder="director@organization.com" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
    </div>

    <div class="form-group">
      <label class="form-label">Password</label>
      <input type="password" name="password" class="form-control" placeholder="Create strong password" required>
    </div>

    <button type="submit" class="btn btn-primary" style="width:100%;margin-top:10px;padding:12px;">
      <?= svgIcon('check', 16) ?> Create Portal Account
    </button>
  </form>

  <div style="text-align:center;margin-top:24px;font-size:0.85rem;color:var(--text-secondary);">
    Already registered? <a href="login.php" style="font-weight:700;">Sign in to account</a>
  </div>
</div>

</body>
</html>
