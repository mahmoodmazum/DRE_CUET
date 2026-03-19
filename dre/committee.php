<?php
require __DIR__ . '/../src/lib/Auth.php';
require __DIR__ . '/../src/db.php';
Auth::requireLogin();
$user = $_SESSION['user'];
if ($user['role'] !== 'dre_admin') { http_response_code(403); exit('Access denied'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_user_id'])) {
        $uid = intval($_POST['add_user_id']);
        $ins = $pdo->prepare("INSERT IGNORE INTO committee_pool (user_id, added_by) VALUES (?, ?)");
        $ins->execute([$uid, $user['sub'] ?? $user['id']]);
    } elseif (isset($_POST['remove_id'])) {
        $id = intval($_POST['remove_id']);
        $pdo->prepare("DELETE FROM committee_pool WHERE id = ?")->execute([$id]);
    }
    header('Location: committee.php');
    exit;
}

include __DIR__ . '/../src/includes/custom_header.php';
include __DIR__ . '/../src/includes/sidebar_dre.php';

$teachers  = $pdo->query("SELECT id, name, email FROM users WHERE role='teacher' ORDER BY name")->fetchAll();
$committee = $pdo->query("SELECT cp.id, u.id AS user_id, u.name, u.email FROM committee_pool cp JOIN users u ON cp.user_id = u.id ORDER BY cp.added_at DESC")->fetchAll();
$committeeIds = array_column($committee, 'user_id');
?>

<div class="main-content">
  <div class="page-header">
    <h1>Committee</h1>
    <div class="breadcrumb">Dashboard / Committee</div>
  </div>

  <div class="card" style="max-width:600px;">
    <div class="card-header"><h3>Add Committee Member</h3></div>
    <div class="card-body">
      <form method="post" style="display:flex;gap:10px;align-items:flex-end;">
        <div class="form-group mb-0" style="flex:1;">
          <label class="field-label">Select Teacher</label>
          <select name="add_user_id" required>
            <option value="">Select a teacher...</option>
            <?php foreach($teachers as $t): ?>
              <?php if (!in_array($t['id'], $committeeIds)): ?>
                <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['name']) ?> — <?= htmlspecialchars($t['email']) ?></option>
              <?php endif; ?>
            <?php endforeach; ?>
          </select>
        </div>
        <button type="submit" class="btn btn-primary" style="white-space:nowrap;flex-shrink:0;">
          <span class="material-icons-round">person_add</span> Add to Committee
        </button>
      </form>
    </div>
  </div>

  <div class="card">
    <div class="card-header">
      <h3>Current Committee</h3>
      <span class="badge badge-primary"><?= count($committee) ?> members</span>
    </div>
    <div class="card-body" style="padding:0;">
      <?php if (empty($committee)): ?>
        <div style="padding:24px;text-align:center;color:var(--c-text-muted);">
          No committee members yet. Add one above.
        </div>
      <?php else: ?>
        <table class="data-table">
          <thead>
            <tr>
              <th>#</th>
              <th>Name</th>
              <th>Email</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($committee as $i => $c): ?>
              <tr>
                <td><?= $i + 1 ?></td>
                <td style="font-weight:500;"><?= htmlspecialchars($c['name']) ?></td>
                <td class="text-muted"><?= htmlspecialchars($c['email']) ?></td>
                <td>
                  <form method="post" style="display:inline" onsubmit="return confirm('Remove <?= htmlspecialchars(addslashes($c['name'])) ?> from committee?')">
                    <input type="hidden" name="remove_id" value="<?= $c['id'] ?>">
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
