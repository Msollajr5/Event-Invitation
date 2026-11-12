<?php
$pageTitle = 'Team User Management';
$currentPage = 'users';
require_once __DIR__ . '/config/db.php';
require_role(1);

// Handle Add User
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_user') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $roleId = (int)($_POST['role_id'] ?? 3);
    $phone = trim($_POST['phone'] ?? '');

    if (!empty($name) && !empty($email)) {
        db_insert('users', [
            'name' => $name,
            'email' => $email,
            'password' => password_hash('password123', PASSWORD_BCRYPT),
            'role_id' => $roleId,
            'phone' => $phone
        ]);
        flash_set('success', 'User "' . $name . '" added successfully! Default password: password123');
        header('Location: users.php');
        exit;
    }
}

// Handle Delete User
if (isset($_GET['delete_id'])) {
    $delId = (int)$_GET['delete_id'];
    if ($delId > 1) { // Prevent deleting default super admin
        $targetUser = db_fetch_one("SELECT name, email FROM users WHERE id = :id", ['id' => $delId]);
        $targetName = $targetUser['name'] ?? "ID #{$delId}";
        db_query("DELETE FROM users WHERE id = :id", ['id' => $delId]);
        audit_log('TEAM_MANAGEMENT', 'DELETE_USER', "Removed team member {$targetName} (ID #{$delId})");
        flash_set('success', 'Team member removed.');
    } else {
        flash_set('danger', 'Primary Super Admin account cannot be removed.');
    }
    header('Location: users.php');
    exit;
}

$teamUsers = db_fetch_all("SELECT * FROM users ORDER BY id ASC");

require_once __DIR__ . '/includes/header.php';
?>

<!-- Page Header -->
<div class="page-header">
  <div>
    <h1 class="page-title">Team User Management</h1>
    <p class="page-subtitle">Configure administrative roles, gate officer verification credentials, and staff access</p>
  </div>
  <div class="page-actions">
    <button type="button" class="btn btn-primary" data-modal-open="addUserModal">
      <?= svgIcon('plus', 16) ?> Add Team Member
    </button>
  </div>
</div>

<!-- Table Card -->
<div class="card">
  <div class="table-responsive">
    <table class="data-table">
      <thead>
        <tr>
          <th>Team Member</th>
          <th>Role Privilege</th>
          <th>Work Contact</th>
          <th>Status</th>
          <th style="text-align:right;">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($teamUsers as $u): ?>
          <tr>
            <td>
              <div style="display:flex;align-items:center;gap:12px;">
                <div class="sidebar-avatar" style="width:36px;height:36px;font-size:0.85rem;">
                  <?= strtoupper(substr($u['name'], 0, 1)) ?>
                </div>
                <div>
                  <strong><?= e($u['name']) ?></strong>
                  <div style="font-size:0.75rem;color:var(--text-muted);"><?= e($u['email']) ?></div>
                </div>
              </div>
            </td>
            <td>
              <?php if ($u['role_id'] == 1): ?>
                <span class="badge badge-primary">Super Administrator</span>
              <?php elseif ($u['role_id'] == 2): ?>
                <span class="badge badge-warning">Event Director</span>
              <?php else: ?>
                <span class="badge badge-secondary">Gate Officer</span>
              <?php endif; ?>
            </td>
            <td>
              <div style="font-size:0.84rem;"><?= e($u['phone'] ?: '—') ?></div>
            </td>
            <td>
              <span class="badge badge-success">Active</span>
            </td>
            <td style="text-align:right;">
              <?php if ($u['id'] > 1): ?>
                <button type="button" class="btn btn-secondary btn-sm" style="color:var(--danger)" onclick="confirmDeleteUser(<?= $u['id'] ?>, '<?= addslashes(e($u['name'])) ?>')">
                  <?= svgIcon('trash', 14) ?> Remove
                </button>
              <?php else: ?>
                <span class="badge badge-secondary">Protected</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Modal: Add User -->
<div class="modal-backdrop" id="addUserModal">
  <div class="modal">
    <div class="modal-header">
      <h3 class="modal-title">Add Team Member</h3>
      <button type="button" class="modal-close" data-modal-close>&times;</button>
    </div>
    <form method="POST" action="users.php">
      <input type="hidden" name="action" value="add_user">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Full Name</label>
          <input type="text" name="name" class="form-control" placeholder="Amina Bakari" required>
        </div>
        <div class="form-group">
          <label class="form-label">Email Address</label>
          <input type="email" name="email" class="form-control" placeholder="officer@leulaevents.com" required>
        </div>
        <div class="form-group">
          <label class="form-label">Role Privilege</label>
          <select name="role_id" class="form-control">
            <option value="3">Gate Verification Officer (Scanner Only)</option>
            <option value="2">Event Director (Manage Events & Guests)</option>
            <option value="1">Super Administrator (Full System Control)</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Phone Number</label>
          <input type="text" name="phone" class="form-control" placeholder="+255 712 000 000">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
        <button type="submit" class="btn btn-primary"><?= svgIcon('check', 16) ?> Add Member</button>
      </div>
    </form>
  </div>
</div>

<script>
async function confirmDeleteUser(userId, userName) {
  const confirmed = await window.showConfirmDialog({
    title: 'REMOVE TEAM MEMBER?',
    subtitle: 'Access revocation & account termination',
    message: `Are you sure you want to remove <strong>${userName}</strong> from the team? They will no longer have access to the platform.`,
    confirmText: 'Remove Member',
    cancelText: 'Cancel',
    type: 'danger'
  });

  if (confirmed) {
    location.href = `users.php?delete_id=${userId}`;
  }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
