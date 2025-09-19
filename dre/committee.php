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

include __DIR__ . '/../src/includes/header.php';
include __DIR__ . '/../src/includes/sidebar_dre.php';

$teachers = $pdo->query("SELECT id, name, email FROM users WHERE role='teacher'")->fetchAll();
$committee = $pdo->query("SELECT cp.id, u.id AS user_id, u.name, u.email FROM committee_pool cp JOIN users u ON cp.user_id = u.id ORDER BY cp.added_at DESC")->fetchAll();
?>

<div class="content-wrapper">
  <section class="content-header"><div class="container-fluid d-flex justify-content-between align-items-center"><h1>Committee</h1></div></section>
  <section class="content">
    <div class="card"><div class="card-body">
      <h5>Add from teachers</h5>
      <form method="post" class="form-inline mb-3">
        <select name="add_user_id" class="form-control mr-2" required>
          <option value="">Select teacher</option>
          <?php foreach($teachers as $t): ?>
            <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['name']) ?> (<?= htmlspecialchars($t['email']) ?>)</option>
          <?php endforeach; ?>
        </select>
        <button class="btn btn-primary">Add to Committee</button>
      </form>

      <h5>Current Committee</h5>
      <table class="table table-sm">
        <thead><tr><th>Name</th><th>Email</th><th>Action</th></tr></thead>
        <tbody>
          <?php foreach($committee as $c): ?>
            <tr>
              <td><?= htmlspecialchars($c['name']) ?></td>
              <td><?= htmlspecialchars($c['email']) ?></td>
              <td>
                <form method="post" style="display:inline" onsubmit="return confirm('Remove?')">
                  <input type="hidden" name="remove_id" value="<?= $c['id'] ?>">
                  <button class="btn btn-sm btn-danger">Remove</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>

    </div></div>
  </section>
</div>

<?php include __DIR__ . '/../src/includes/footer.php'; ?>
