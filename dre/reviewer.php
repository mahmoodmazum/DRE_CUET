<?php
require __DIR__ . '/../src/lib/Auth.php';
require __DIR__ . '/../src/db.php';
Auth::requireLogin();
$user = $_SESSION['user'];
if ($user['role'] !== 'dre_admin') { http_response_code(403); exit('Access denied'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_POST['add_user_id'])) {
        $uid = intval($_POST['add_user_id']);
        $pdo->prepare("INSERT IGNORE INTO reviewer_pool (user_id, added_by) VALUES (?, ?)")->execute([$uid, $user['sub'] ?? $user['id']]);
    } elseif (!empty($_POST['external_name']) && !empty($_POST['external_email'])) {
        $ename  = trim($_POST['external_name']);
        $eemail = trim($_POST['external_email']);
        $pdo->prepare("INSERT INTO reviewer_pool (external_name, external_email, added_by) VALUES (?, ?, ?)")->execute([$ename, $eemail, $user['sub'] ?? $user['id']]);
    } elseif (!empty($_POST['remove_id'])) {
        $id = intval($_POST['remove_id']);
        $pdo->prepare("DELETE FROM reviewer_pool WHERE id = ?")->execute([$id]);
    }
    header('Location: reviewer.php');
    exit;
}

include __DIR__ . '/../src/includes/custom_header.php';
include __DIR__ . '/../src/includes/sidebar_dre.php';

$teachers  = $pdo->query("SELECT id, name, email FROM users WHERE role='teacher' ORDER BY name")->fetchAll();
$reviewers = $pdo->query("
    SELECT rp.id, rp.user_id, rp.external_name, rp.external_email,
           u.name AS user_name, u.email AS user_email
    FROM reviewer_pool rp
    LEFT JOIN users u ON rp.user_id = u.id
    ORDER BY rp.added_at DESC
")->fetchAll();
$internalIds = array_column(array_filter($reviewers, fn($r) => $r['user_id']), 'user_id');
?>

<div class="main-content">
  <div class="page-header">
    <h1>Reviewer Pool</h1>
    <div class="breadcrumb">Dashboard / Reviewer Pool</div>
  </div>

  <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px;">
    <!-- Add Internal Reviewer -->
    <div class="card">
      <div class="card-header"><h3>Add Internal Reviewer</h3></div>
      <div class="card-body">
        <form method="post" style="display:flex;gap:10px;align-items:flex-end;">
          <div class="form-group mb-0" style="flex:1;">
            <label class="field-label">Select Teacher</label>
            <select name="add_user_id">
              <option value="">Select a teacher...</option>
              <?php foreach($teachers as $t): ?>
                <?php if (!in_array($t['id'], $internalIds)): ?>
                  <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['name']) ?> — <?= htmlspecialchars($t['email']) ?></option>
                <?php endif; ?>
              <?php endforeach; ?>
            </select>
          </div>
          <button type="submit" class="btn btn-primary" style="flex-shrink:0;">
            <span class="material-icons-round">add</span> Add
          </button>
        </form>
      </div>
    </div>

    <!-- Add External Reviewer -->
    <div class="card">
      <div class="card-header"><h3>Add External Reviewer</h3></div>
      <div class="card-body">
        <form method="post">
          <div class="form-group">
            <label class="field-label">Full Name <span style="color:var(--c-danger)">*</span></label>
            <input type="text" name="external_name" placeholder="Dr. John Doe" required>
          </div>
          <div class="form-group mb-0">
            <label class="field-label">Email Address <span style="color:var(--c-danger)">*</span></label>
            <input type="email" name="external_email" placeholder="reviewer@university.edu" required>
          </div>
          <div style="margin-top:14px;">
            <button type="submit" class="btn btn-success">
              <span class="material-icons-round">person_add</span> Add External Reviewer
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-header">
      <h3>Current Reviewers</h3>
      <span class="badge badge-primary"><?= count($reviewers) ?> total</span>
    </div>
    <div class="card-body" style="padding:0;">
      <?php if (empty($reviewers)): ?>
        <div style="padding:24px;text-align:center;color:var(--c-text-muted);">
          No reviewers in the pool yet.
        </div>
      <?php else: ?>
        <table class="data-table">
          <thead>
            <tr>
              <th>#</th>
              <th>Name</th>
              <th>Email</th>
              <th>Type</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($reviewers as $i => $r):
              $name  = $r['user_id'] ? $r['user_name']  : $r['external_name'];
              $email = $r['user_id'] ? $r['user_email'] : $r['external_email'];
              $type  = $r['user_id'] ? 'Internal' : 'External';
            ?>
              <tr>
                <td><?= $i + 1 ?></td>
                <td style="font-weight:500;"><?= htmlspecialchars($name) ?></td>
                <td class="text-muted"><?= htmlspecialchars($email) ?></td>
                <td>
                  <span class="badge <?= $r['user_id'] ? 'badge-primary' : 'badge-success' ?>">
                    <?= $type ?>
                  </span>
                </td>
                <td>
                  <form method="post" style="display:inline" onsubmit="return confirm('Remove <?= htmlspecialchars(addslashes($name)) ?> from reviewer pool?')">
                    <input type="hidden" name="remove_id" value="<?= $r['id'] ?>">
                    <button class="btn btn-danger btn-sm">
                      <span class="material-icons-round">person_remove</span> Remove
                    </button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../src/includes/custom_footer.php'; ?>
<script>
$(function(){
  $('select:not(.no-select2)').select2({
    theme:'default', width:'100%', allowClear:true,
    placeholder:'Select a teacher...'
  });
});
</script>
